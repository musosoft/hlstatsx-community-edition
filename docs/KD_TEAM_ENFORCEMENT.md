# HLstatsX KD-Based Team Enforcement System

## Overview

This document describes a complete KD-based team enforcement system for HLstatsX + CS:S that fetches player KD from HLstats and pushes it to the game server via RCON for team balancing decisions.

## Architecture

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  CS:S Server    │────▶│  SourceMod       │◀────│  HLstats Daemon │────▶│  HLstats DB     │
│                 │     │  Plugin          │     │  (Perl)         │     │                 │
│  - Player KD    │     │  - KD Cache      │     │  - KD Provider  │     │  - hlstats_     │
│  - Team Balance │     │  - TTL Manager   │     │  - RCON Sender  │     │    Players      │
└─────────────────┘     └──────────────────┘     └──────────────────┘     └─────────────────┘
```

---

## 1. HLstats Data Source

### Database Query (Recommended)

**Recommended Approach**: Direct DB query via Perl script (faster than API, no HTTP overhead)

**Table**: `hlstats_Players`

**Fields Needed**:
```sql
SELECT 
    playerId,
    lastName,
    kills,
    deaths,
    -- Calculated KD ratio
    ROUND(kills / IF(deaths = 0, 1, deaths), 2) AS kd_ratio,
    -- Additional context fields
    skill,
    connection_time,
    last_event,
    hideranking
FROM hlstats_Players
WHERE uniqueId = ? OR steamId = ? OR playerId = ?
```

**Player ID Lookup**:
```sql
SELECT playerId FROM hlstats_PlayerUniqueIds 
WHERE uniqueId = ? AND game = ?
```

### KD Calculation Logic

```perl
sub calculate_kd {
    my ($kills, $deaths) = @_;
    if ($deaths == 0) {
        return sprintf("%.2f", $kills);  # No deaths = KD = kills
    }
    return sprintf("%.2f", $kills / $deaths);
}
```

---

## 2. Caching Strategy

### KD Provider Cache

**Key Format**:
```
kd_cache:{steamid}:{game}
kd_cache:STEAM_0:1:12345:css
kd_cache:76561198012345678:css
```

**TTL Configuration**:
- **Default TTL**: 60 minutes (1 hour)
- **Min TTL**: 30 minutes (for high-activity servers)
- **Max TTL**: 120 minutes (2 hours)
- **Stale TTL**: 5 minutes (allow stale data while fetching new)

**Cache Structure**:
```perl
my %kd_cache = (
    'STEAM_0:1:12345' => {
        kd          => 2.45,
        kills       => 1500,
        deaths      => 612,
        cached_at   => time(),
        expires_at  => time() + 3600,  # 1 hour
        game        => 'css',
        is_new      => 0,              # 0 = existing player, 1 = new
    },
);
```

---

## 3. RCON Command Format

### Command Definition

```
sm_kd_set "<steamid>" "<kd>" "<ttl_seconds>"
```

**Example**:
```
sm_kd_set "STEAM_0:1:12345" "2.45" "3600"
```

### RCON Execution from HLstats Daemon

```perl
sub push_kd_to_server {
    my ($server, $steamid, $kd, $ttl) = @_;
    
    my $command = sprintf(
        'sm_kd_set "%s" "%s" "%d"',
        $steamid, $kd, $ttl
    );
    
    $server->dorcon($command);
}
```

### Command Flow

1. **Connection Event** → `doEvent_Connect()`
2. **Fetch KD** → Query DB with SteamID
3. **Cache Result** → Store in memory cache
4. **Push via RCON** → Send `sm_kd_set` command
5. **SourceMod Plugin** → Receives and caches KD
6. **Team Balance** → Plugin makes enforcement decisions

---

## 4. HLstats Daemon KD Provider

### Perl Script: `kd_provider.pl`

```perl
#!/usr/bin/perl
# HLstatsX KD Provider Service
# Fetches KD from HLstats DB and pushes to game servers via RCON

package HLstats::KDProvider;

use strict;
use warnings;
use DBI;
use Storable qw(dclone);
use Time::HiRes qw(sleep);

# Configuration
my %config = (
    # Database settings
    db_host     => 'localhost',
    db_port     => 3306,
    db_name     => 'hlstats',
    db_user     => 'hlstats',
    db_pass     => 'your_password_here',
    
    # Cache settings
    cache_ttl       => 3600,      # 1 hour in seconds
    cache_min_ttl   => 1800,      # 30 minutes
    cache_max_ttl   => 7200,      # 2 hours
    
    # Provider settings
    fetch_timeout   => 5,         # seconds
    batch_size      => 50,        # players per batch
    poll_interval   => 0.5,       # seconds between player checks
    
    # High KD threshold
    high_kd_threshold => 2.0,
    
    # Logging
    log_level       => 1,         # 0=error, 1=info, 2=debug
);

# Cache structure: { steamid => { kd, kills, deaths, cached_at, expires_at, game } }
my %kd_cache;
my %pending_fetches;
my @fetch_queue;

# Database handle
my $dbh;

# Server connections: { server_addr => { rcon_obj, game, enabled } }
my %servers;

# ----------------------------------------------------------------------
# Database Functions
# ----------------------------------------------------------------------

sub connect_db {
    my $dsn = "DBI:mysql:host=$config{db_host};port=$config{db_port};database=$config{db_name}";
    $dbh = DBI->connect($dsn, $config{db_user}, $config{db_pass}, {
        RaiseError => 1,
        PrintError => 0,
        AutoCommit => 1,
    });
    
    log_msg("Connected to database $config{db_name} at $config{db_host}");
}

sub disconnect_db {
    if ($dbh) {
        $dbh->disconnect();
        log_msg("Disconnected from database");
    }
}

sub get_player_kd {
    my ($steamid, $game) = @_;
    
    # Normalize SteamID
    my $normalized_id = normalize_steamid($steamid);
    
    # Check cache first
    if (exists $kd_cache{$normalized_id}) {
        my $cached = $kd_cache{$normalized_id};
        if (time() < $cached->{expires_at}) {
            log_msg("Cache hit for $normalized_id: KD=$cached->{kd}") if $config{log_level} >= 2;
            return dclone($cached);
        }
    }
    
    # Check if already being fetched
    if (exists $pending_fetches{$normalized_id}) {
        log_msg("Pending fetch for $normalized_id, waiting...") if $config{log_level} >= 2;
        return undef;  # Will be populated when fetch completes
    }
    
    # Add to fetch queue
    push @fetch_queue, { steamid => $normalized_id, game => $game };
    $pending_fetches{$normalized_id} = 1;
    
    log_msg("Queued fetch for $normalized_id") if $config{log_level} >= 1;
    return undef;
}

sub normalize_steamid {
    my ($steamid) = @_;
    
    # Handle SteamID64
    if ($steamid =~ /^\[U:1:(\d+)\]$/) {
        my $auth_id = $1;
        my $account_type = $auth_id % 2;  # 0 = individual, 1 = individual (console)
        my $account_number = int($auth_id / 2);
        return "STEAM_0:$account_type:$account_number";
    }
    
    # Already in STEAM_X:Y:Z format
    if ($steamid =~ /^STEAM_\d+:\d+:\d+$/) {
        return $steamid;
    }
    
    # SteamID64 numeric (76561198012345678)
    if ($steamid =~ /^(\d{17})$/) {
        my $steam64 = $1;
        # Convert Steam64 to Steam2
        my $universe = int(($steam64 - 76561197960265728) / 2);
        my $account_type = 0;
        my $account_number = $universe;
        return "STEAM_0:$account_type:$account_number";
    }
    
    return $steamid;
}

sub fetch_kd_batch {
    return unless @fetch_queue;
    
    my @batch;
    my $batch_size = $config{batch_size};
    
    while (@fetch_queue && $batch_size > 0) {
        push @batch, shift @fetch_queue;
        $batch_size--;
    }
    
    return unless @batch;
    
    # Build IN clause for batch query
    my @ids = map { $_->{steamid} } @batch;
    my $placeholders = join(',', ('?') x @ids);
    
    my $query = qq{
        SELECT 
            p.playerId,
            COALESCE(pui.uniqueId, 'UNKNOWN') as steamid,
            p.kills,
            p.deaths,
            ROUND(p.kills / IF(p.deaths = 0, 1, p.deaths), 2) as kd_ratio,
            p.connection_time,
            p.last_event,
            p.hideranking,
            COALESCE(pui.game, ?) as game
        FROM hlstats_Players p
        LEFT JOIN hlstats_PlayerUniqueIds pui ON p.playerId = pui.playerId
        WHERE pui.uniqueId IN ($placeholders)
            OR p.playerId IN (
                SELECT playerId FROM hlstats_PlayerUniqueIds 
                WHERE uniqueId IN ($placeholders)
            )
    };
    
    eval {
        my $sth = $dbh->prepare($query);
        $sth->execute(@ids, @ids);
        
        while (my $row = $sth->fetchrow_hashref()) {
            my $steamid = $row->{steamid};
            my $is_new = (!exists $kd_cache{$steamid} || 
                          $kd_cache{$steamid}{is_new} eq '1') ? 1 : 0;
            
            my $ttl = calculate_ttl($row);
            
            $kd_cache{$steamid} = {
                playerid     => $row->{playerId},
                steamid      => $steamid,
                kills        => $row->{kills},
                deaths       => $row->{deaths},
                kd           => $row->{kd_ratio},
                game         => $row->{game} || 'unknown',
                cached_at    => time(),
                expires_at   => time() + $ttl,
                is_new       => $is_new,
                last_update  => time(),
            };
            
            delete $pending_fetches{$steamid};
            log_msg("Fetched KD for $steamid: $row->{kills}:$row->{deaths} = $row->{kd_ratio}") 
                if $config{log_level} >= 2;
        }
        $sth->finish();
    };
    
    if ($@) {
        log_msg("Error fetching KD batch: $@", 0);
        # Clear pending flags on error so we retry
        foreach my $item (@batch) {
            delete $pending_fetches{$item->{steamid}};
        }
    }
}

sub calculate_ttl {
    my ($player) = @_;
    
    my $base_ttl = $config{cache_ttl};
    
    # Reduce TTL for new players (less history)
    if ($player->{kills} < 10) {
        return $config{cache_min_ttl} / 2;  # 15 minutes
    }
    
    # Increase TTL for established players
    if ($player->{kills} > 1000 && $player->{deaths} > 0) {
        my $kd = $player->{kills} / $player->{deaths};
        if ($kd > $config{high_kd_threshold}) {
            return $config{cache_max_ttl};  # 2 hours for high KD players
        }
    }
    
    return $base_ttl;
}

# ----------------------------------------------------------------------
# Server Management
# ----------------------------------------------------------------------

sub register_server {
    my ($server_addr, $rcon_pass, $game, $port) = @_;
    
    require TRcon;
    
    my $server = {
        address    => $server_addr,
        port       => $port || 27015,
        rcon       => $rcon_pass,
        game       => $game,
        rcon_obj   => undef,
        enabled    => 1,
    };
    
    # Initialize RCON connection
    eval {
        $server->{rcon_obj} = TRcon->new($server);
        if ($server->{rcon_obj}->execute("echo HLstatsX KD Provider Connected")) {
            log_msg("RCON connected to $server_addr:$server->{port}");
        }
    };
    
    if ($@) {
        log_msg("Failed to connect RCON to $server_addr: $@", 0);
        $server->{enabled} = 0;
    }
    
    $servers{$server_addr} = $server;
}

sub push_kd_to_servers {
    my ($steamid, $kd, $game, $ttl) = @_;
    
    foreach my $addr (keys %servers) {
        my $server = $servers{$addr};
        next unless $server->{enabled};
        next unless $server->{game} eq $game;
        
        my $command = sprintf('sm_kd_set "%s" "%s" "%d"', $steamid, $kd, $ttl);
        
        eval {
            my $result = $server->{rcon_obj}->execute($command);
            log_msg("Pushed KD to $addr: $steamid=$kd (TTL=$ttl)") 
                if $config{log_level} >= 1;
        };
        
        if ($@) {
            log_msg("Failed to push KD to $addr: $@", 0);
        }
    }
}

# ----------------------------------------------------------------------
# Player Event Hook Integration
# ----------------------------------------------------------------------

sub on_player_connect {
    my ($player_id, $steamid, $ip_addr, $game) = @_;
    
    log_msg("Player connect: $steamid (game=$game)") if $config{log_level} >= 1;
    
    # Fetch KD from database
    my $kd_data = get_player_kd($steamid, $game);
    
    if ($kd_data) {
        # Check if player is new (no existing stats)
        if ($kd_data->{is_new}) {
            log_msg("New player $steamid, skipping KD push") if $config{log_level} >= 1;
            return;
        }
        
        # Push KD to all servers for this game
        push_kd_to_servers(
            $kd_data->{steamid},
            $kd_data->{kd},
            $kd_data->{game},
            $kd_data->{expires_at} - time()
        );
    }
}

# ----------------------------------------------------------------------
# Main Loop
# ----------------------------------------------------------------------

sub run {
    connect_db();
    
    log_msg("HLstatsX KD Provider started");
    
    while (1) {
        # Fetch pending KD requests
        if (@fetch_queue) {
            fetch_kd_batch();
        }
        
        # Clean up expired cache entries
        cleanup_cache();
        
        # Sleep between cycles
        sleep($config{poll_interval});
    }
}

sub cleanup_cache {
    my $now = time();
    foreach my $steamid (keys %kd_cache) {
        if ($kd_cache{$steamid}{expires_at} < $now) {
            delete $kd_cache{$steamid};
            log_msg("Cache expired for $steamid") if $config{log_level} >= 2;
        }
    }
}

# ----------------------------------------------------------------------
# Logging
# ----------------------------------------------------------------------

sub log_msg {
    my ($msg, $level) = @_;
    $level //= 1;
    
    return if $level > $config{log_level};
    
    my $timestamp = localtime();
    print "[$timestamp] [KDProvider] $msg\n";
}

# Start the provider
run() unless caller;

1;
```

---

## 5. SourceMod Plugin

### SourcePawn Script: `kd_enforcement.sp`

```sp
/**
 * HLstatsX KD-Based Team Enforcement Plugin
 * 
 * This plugin receives KD data from HLstatsX and uses it for team balancing.
 * Players with high KD (>2.0) can be flagged for special team balance handling.
 */

#include <sourcemod>
#include <sdktools>

#pragma semicolon 1
#pragma newdecls required

// Plugin information
public Plugin myinfo = {
    name        = "HLstatsX KD Enforcement",
    author      = "HLstatsX Community",
    description = "KD-based team enforcement using HLstatsX data",
    version     = "1.0.0",
    url         = "https://hlxcommunity.com"
};

// ----------------------------------------------------------------------
// Constants
// ----------------------------------------------------------------------

#define PLUGIN_NAME       "HLstatsX KD Enforcement"
#define PLUGIN_VERSION    "1.0.0"
#define PLUGIN_TAG        "[KD-Enforcement]"

// High KD threshold for special handling
#define HIGH_KD_THRESHOLD 2.0

// Maximum cache entries
#define MAX_CACHE_ENTRIES 512

// Default TTL for KD data (seconds)
#define DEFAULT_TTL       3600

// ----------------------------------------------------------------------
// Global Variables
// ----------------------------------------------------------------------

// KD Cache: Key = SteamID, Value = KDData
ArrayList g_hKDCache;

// Player tracking: Key = Client Index, Value = PlayerData
ArrayList g_hPlayerData;

// Configuration cvars
ConVar g_cvEnabled;
ConVar g_cvHighKDThreshold;
ConVar g_cvDefaultTTL;
ConVar g_cvDebugMode;

// Plugin state
bool g_bEnabled = true;
float g_fHighKDThreshold = HIGH_KD_THRESHOLD;
int g_iDefaultTTL = DEFAULT_TTL;
bool g_bDebugMode = false;

// ----------------------------------------------------------------------
// KD Data Structure
// ----------------------------------------------------------------------

enum struct KDData {
    char steamid[64];
    float kd;
    int kills;
    int deaths;
    int timestamp;
    int expires_at;
    bool isNewPlayer;
}

enum struct PlayerData {
    int clientIndex;
    char steamid[64];
    float kd;
    bool isHighKD;
    int team;
    int connectTime;
}

// ----------------------------------------------------------------------
// Plugin Lifecycle
// ----------------------------------------------------------------------

public void OnPluginStart() {
    // Create cvars
    g_cvEnabled = CreateConVar("sm_kdenable", "1", "Enable KD-based team enforcement");
    g_cvHighKDThreshold = CreateConVar("sm_kdhighthreshold", "2.0", "KD threshold for high-KD player classification");
    g_cvDefaultTTL = CreateConVar("sm_kddefaultttl", "3600", "Default TTL for KD cache (seconds)");
    g_cvDebugMode = CreateConVar("sm_kddebug", "0", "Enable debug logging");
    
    // Hook cvar changes
    g_cvEnabled.AddChangeHook(OnCvarChanged);
    g_cvHighKDThreshold.AddChangeHook(OnCvarChanged);
    g_cvDefaultTTL.AddChangeHook(OnCvarChanged);
    g_cvDebugMode.AddChangeHook(OnCvarChanged);
    
    // Initialize arrays
    g_hKDCache = new ArrayList(sizeof(KDData));
    g_hPlayerData = new ArrayList(sizeof(PlayerData));
    
    // Register commands
    RegConsoleCmd("sm_kd_set", Command_KDSet, "Receive KD data from HLstatsX");
    RegConsoleCmd("sm_kd_check", Command_KDCheck, "Check player's cached KD");
    RegConsoleCmd("sm_kd_debug", Command_KDDebug, "Debug KD cache");
    
    // Register admin commands
    RegAdminCmd("sm_kd_reload", Command_KDReload, ADMFLAG_GENERIC, "Reload KD data for a player");
    RegAdminCmd("sm_kd_clear", Command_KDClear, ADMFLAG_GENERIC, "Clear KD cache");
    
    // Hook events
    HookEvent("player_connect_full", Event_PlayerConnectFull, EventHookMode_Post);
    HookEvent("player_team", Event_PlayerTeam, EventHookMode_Pre);
    HookEvent("player_disconnect", Event_PlayerDisconnect, EventHookMode_Pre);
    
    // Print plugin info
    PrintToServer("[%s] %s v%s loaded", PLUGIN_TAG, PLUGIN_NAME, PLUGIN_VERSION);
    PrintToServer("[%s] High KD threshold: %.2f", PLUGIN_TAG, g_fHighKDThreshold);
    PrintToServer("[%s] Default TTL: %d seconds", PLUGIN_TAG, g_iDefaultTTL);
}

public void OnCvarChanged(ConVar cvar, const char[] oldValue, const char[] newValue) {
    if (cvar == g_cvEnabled) {
        g_bEnabled = view_as<bool>(StringToInt(newValue));
        LogMessage("KD Enforcement %s", g_bEnabled ? "ENABLED" : "DISABLED");
    }
    else if (cvar == g_cvHighKDThreshold) {
        g_fHighKDThreshold = StringToFloat(newValue);
        LogMessage("High KD threshold changed to %.2f", g_fHighKDThreshold);
    }
    else if (cvar == g_cvDefaultTTL) {
        g_iDefaultTTL = StringToInt(newValue);
        LogMessage("Default TTL changed to %d seconds", g_iDefaultTTL);
    }
    else if (cvar == g_cvDebugMode) {
        g_bDebugMode = view_as<bool>(StringToInt(newValue));
        LogMessage("Debug mode %s", g_bDebugMode ? "ENABLED" : "DISABLED");
    }
}

public void OnPluginEnd() {
    LogMessage("HLstatsX KD Enforcement plugin unloaded");
}

// ----------------------------------------------------------------------
// KD Set Command (called from HLstatsX via RCON)
// ----------------------------------------------------------------------

public Action Command_KDSet(int client, int args) {
    if (!g_bEnabled) {
        return Plugin_Handled;
    }
    
    // Check argument count
    if (args < 3) {
        ReplyToCommand(client, "[%s] Usage: sm_kd_set <steamid> <kd> <ttl>", PLUGIN_TAG);
        return Plugin_Handled;
    }
    
    // Get arguments
    char steamid[64];
    char kdStr[16];
    char ttlStr[16];
    
    GetCmdArg(1, steamid, sizeof(steamid));
    GetCmdArg(2, kdStr, sizeof(kdStr));
    GetCmdArg(3, ttlStr, sizeof(ttlStr));
    
    float kd = StringToFloat(kdStr);
    int ttl = StringToInt(ttlStr);
    
    if (ttl <= 0) {
        ttl = g_iDefaultTTL;
    }
    
    // Store KD data in cache
    StoreKDData(steamid, kd, ttl, client == 0);  // client==0 means from RCON
    
    if (g_bDebugMode) {
        PrintToServer("[%s] KD set: SteamID=%s, KD=%.2f, TTL=%d", 
            PLUGIN_TAG, steamid, kd, ttl);
    }
    
    return Plugin_Handled;
}

// ----------------------------------------------------------------------
// KD Check Command (for players)
// ----------------------------------------------------------------------

public Action Command_KDCheck(int client, int args) {
    if (!g_bEnabled) {
        return Plugin_Handled;
    }
    
    char steamid[64];
    
    if (args >= 1) {
        // Check specific player
        GetCmdArg(1, steamid, sizeof(steamid));
    } else {
        // Check self
        GetClientAuthId(client, AuthId_Steam2, steamid, sizeof(steamid));
    }
    
    // Check if player exists
    int target = FindClientBySteamID(steamid);
    
    if (target > 0) {
        // Get cached KD for player
        KDData data;
        if (GetCachedKD(steamid, data)) {
            ReplyToCommand(client, "[%s] Your KD: %.2f (%d:%d)", 
                PLUGIN_TAG, data.kd, data.kills, data.deaths);
            
            if (data.isNewPlayer) {
                ReplyToCommand(client, "[%s] (New player - stats still accumulating)", PLUGIN_TAG);
            }
            
            if (data.kd > g_fHighKDThreshold) {
                ReplyToCommand(client, "[%s] High KD player detected!", PLUGIN_TAG);
            }
        } else {
            ReplyToCommand(client, "[%s] No KD data available for this player", PLUGIN_TAG);
        }
    } else {
        ReplyToCommand(client, "[%s] Player not found on server", PLUGIN_TAG);
    }
    
    return Plugin_Handled;
}

// ----------------------------------------------------------------------
// Debug Command
// ----------------------------------------------------------------------

public Action Command_KDDebug(int client, int args) {
    if (!g_bDebugMode) {
        ReplyToCommand(client, "[%s] Debug mode is disabled", PLUGIN_TAG);
        return Plugin_Handled;
    }
    
    ReplyToCommand(client, "[%s] KD Cache Contents (%d entries):", PLUGIN_TAG, g_hKDCache.Length);
    
    char steamid[64];
    KDData data;
    
    for (int i = 0; i < g_hKDCache.Length; i++) {
        g_hKDCache.GetArray(i, data, sizeof(KDData));
        
        int remaining = data.expires_at - GetTime();
        if (remaining > 0) {
            ReplyToClient(client, "  %s: KD=%.2f (%d:%d), expires in %ds", 
                data.steamid, data.kd, data.kills, data.deaths, remaining);
        }
    }
    
    ReplyToCommand(client, "[%s] Player Tracking (%d players):", PLUGIN_TAG, g_hPlayerData.Length);
    
    PlayerData pData;
    char playerName[MAX_NAME_LENGTH];
    
    for (int i = 0; i < g_hPlayerData.Length; i++) {
        g_hPlayerData.GetArray(i, pData, sizeof(PlayerData));
        
        int clientIndex = pData.clientIndex;
        if (clientIndex > 0 && clientIndex <= MaxClients && IsClientInGame(clientIndex)) {
            GetClientName(clientIndex, playerName, sizeof(playerName));
            ReplyToClient(client, "  %s (%s): KD=%.2f, HighKD=%s", 
                playerName, pData.steamid, pData.kd, pData.isHighKD ? "Yes" : "No");
        }
    }
    
    return Plugin_Handled;
}

// ----------------------------------------------------------------------
// Admin Commands
// ----------------------------------------------------------------------

public Action Command_KDReload(int client, int args) {
    if (!g_bEnabled) {
        return Plugin_Handled;
    }
    
    if (args < 1) {
        ReplyToCommand(client, "[%s] Usage: sm_kd_reload <steamid>", PLUGIN_TAG);
        return Plugin_Handled;
    }
    
    char steamid[64];
    GetCmdArg(1, steamid, sizeof(steamid));
    
    // Remove from cache
    RemoveFromCache(steamid);
    
    // Request new data
    // This would typically trigger an HTTP request or RCON query back to HLstatsX
    
    ReplyToCommand(client, "[%s] KD data for %s cleared. Request new data.", PLUGIN_TAG, steamid);
    
    return Plugin_Handled;
}

public Action Command_KDClear(int client, int args) {
    g_hKDCache.Clear();
    g_hPlayerData.Clear();
    
    ReplyToCommand(client, "[%s] KD cache cleared", PLUGIN_TAG);
    LogMessage("KD cache cleared by admin %N", client);
    
    return Plugin_Handled;
}

// ----------------------------------------------------------------------
// Event Hooks
// ----------------------------------------------------------------------

public void Event_PlayerConnectFull(Event event, const char[] name, bool dontBroadcast) {
    if (!g_bEnabled) return;
    
    int client = GetClientOfUserId(event.GetInt("userid"));
    if (client <= 0 || !IsClientInGame(client)) return;
    
    // Skip bots
    if (IsFakeClient(client)) return;
    
    char steamid[64];
    if (!GetClientAuthId(client, AuthId_Steam2, steamid, sizeof(steamid))) {
        return;
    }
    
    // Create player tracking entry
    PlayerData pData;
    pData.clientIndex = client;
    strcopy(pData.steamid, sizeof(pData.steamid), steamid);
    pData.kd = 0.0;
    pData.isHighKD = false;
    pData.team = 0;
    pData.connectTime = GetTime();
    
    // Add to tracking
    AddPlayerTracking(pData);
    
    // Try to get cached KD
    KDData kdData;
    if (GetCachedKD(steamid, kdData)) {
        UpdatePlayerKD(client, kdData);
        
        if (g_bDebugMode) {
            PrintToServer("[%s] Player %N connected with KD=%.2f", 
                PLUGIN_TAG, client, kdData.kd);
        }
    } else {
        if (g_bDebugMode) {
            PrintToServer("[%s] Player %N connected, no KD data cached", 
                PLUGIN_TAG, client);
        }
    }
}

public Action Event_PlayerTeam(Event event, const char[] name, bool dontBroadcast) {
    if (!g_bEnabled) return Plugin_Continue;
    
    int client = GetClientOfUserId(event.GetInt("userid"));
    if (client <= 0 || !IsClientInGame(client)) return Plugin_Continue;
    
    char steamid[64];
    if (!GetClientAuthId(client, AuthId_Steam2, steamid, sizeof(steamid))) {
        return Plugin_Continue;
    }
    
    // Update player team
    int newTeam = event.GetInt("team");
    UpdatePlayerTeam(steamid, newTeam);
    
    // If high KD player, consider team balance
    if (IsHighKDPlayer(steamid)) {
        // Team  logic would go here
        // For example: prevent high KD players from stacking teams
        
        if (g_bDebugMode) {
            PrintToServer("[%s] High KD player %N joined team %d", 
                PLUGIN_TAG, client, newTeam);
        }
    }
    
    return Plugin_Continue;
}

public Action Event_PlayerDisconnect(Event event, const char[] name, bool dontBroadcast) {
    int client = GetClientOfUserId(event.GetInt("userid"));
    if (client <= 0) return Plugin_Continue;
    
    char steamid[64];
    if (GetClientAuthId(client, AuthId_Steam2, steamid, sizeof(steamid))) {
        RemovePlayerTracking(steamid);
    }
    
    return Plugin_Continue;
}

// ----------------------------------------------------------------------
// KD Cache Functions
// ----------------------------------------------------------------------

void StoreKDData(const char[] steamid, float kd, int ttl, bool fromRCON = false) {
    // Check if already exists
    int index = FindInCache(steamid);
    
    KDData data;
    if (index >= 0) {
        g_hKDCache.GetArray(index, data, sizeof(KDData));
    }
    
    // Update data
    strcopy(data.steamid, sizeof(data.steamid), steamid);
    data.kd = kd;
    data.timestamp = GetTime();
    data.expires_at = GetTime() + ttl;
    data.isNewPlayer = (kd <= 0.0);  // New players have no KD yet
    
    // Estimate kills/deaths from KD (not exact, but sufficient for display)
    if (data.isNewPlayer) {
        data.kills = 0;
        data.deaths = 0;
    } else if (data.kills == 0 && data.deaths == 0) {
        // Estimate from KD
        data.deaths = 100;  // Base value
        data.kills = round(data.kd * data.deaths);
    }
    
    if (index >= 0) {
        g_hKDCache.SetArray(index, data, sizeof(KDData));
    } else {
        g_hKDCache.PushArray(data, sizeof(KDData));
        
        // Enforce max cache size
        while (g_hKDCache.Length > MAX_CACHE_ENTRIES) {
            g_hKDCache.Erase(0);
        }
    }
    
    // Update player tracking if player is online
    UpdatePlayerKDBySteamID(steamid, data);
    
    if (g_bDebugMode || fromRCON) {
        LogMessage("KD stored: %s = %.2f (TTL=%d)", steamid, kd, ttl);
    }
}

bool GetCachedKD(const char[] steamid, KDData data) {
    int index = FindInCache(steamid);
    if (index < 0) return false;
    
    g_hKDCache.GetArray(index, data, sizeof(KDData));
    
    // Check expiration
    if (data.expires_at < GetTime()) {
        // Data expired
        g_hKDCache.Erase(index);
        return false;
    }
    
    return true;
}

int FindInCache(const char[] steamid) {
    KDData data;
    for (int i = 0; i < g_hKDCache.Length; i++) {
        g_hKDCache.GetArray(i, data, sizeof(KDData));
        if (StrEqual(data.steamid, steamid)) {
            return i;
        }
    }
    return -1;
}

void RemoveFromCache(const char[] steamid) {
    int index = FindInCache(steamid);
    if (index >= 0) {
        g_hKDCache.Erase(index);
    }
}

// ----------------------------------------------------------------------
// Player Tracking Functions
// ----------------------------------------------------------------------

void AddPlayerTracking(const PlayerData pData) {
    // Check if already tracking
    if (FindPlayerTrackingBySteamID(pData.steamid) >= 0) {
        return;
    }
    
    g_hPlayerData.PushArray(pData, sizeof(PlayerData));
}

void UpdatePlayerKD(int client, const KDData kdData) {
    char steamid[64];
    if (!GetClientAuthId(client, AuthId_Steam2, steamid, sizeof(steamid))) {
        return;
    }
    
    UpdatePlayerKDBySteamID(steamid, kdData);
}

void UpdatePlayerKDBySteamID(const char[] steamid, const KDData kdData) {
    int index = FindPlayerTrackingBySteamID(steamid);
    if (index < 0) return;
    
    PlayerData pData;
    g_hPlayerData.GetArray(index, pData, sizeof(pData));
    
    pData.kd = kdData.kd;
    pData.isHighKD = (kdData.kd > g_fHighKDThreshold);
    
    g_hPlayerData.SetArray(index, pData, sizeof(pData));
}

void UpdatePlayerTeam(const char[] steamid, int team) {
    int index = FindPlayerTrackingBySteamID(steamid);
    if (index < 0) return;
    
    PlayerData pData;
    g_hPlayerData.GetArray(index, pData, sizeof(pData));
    
    pData.team = team;
    g_hPlayerData.SetArray(index, pData, sizeof(pData));
}

void RemovePlayerTracking(const char[] steamid) {
    int index = FindPlayerTrackingBySteamID(steamid);
    if (index >= 0) {
        g_hPlayerData.Erase(index);
    }
}

int FindPlayerTrackingBySteamID(const char[] steamid) {
    PlayerData pData;
    for (int i = 0; i < g_hPlayerData.Length; i++) {
        g_hPlayerData.GetArray(i, pData, sizeof(pData));
        if (StrEqual(pData.steamid, steamid)) {
            return i;
        }
    }
    return -1;
}

int FindClientBySteamID(const char[] steamid) {
    for (int i = 1; i <= MaxClients; i++) {
        if (IsClientInGame(i)) {
            char clientSteamID[64];
            if (GetClientAuthId(i, AuthId_Steam2, clientSteamID, sizeof(clientSteamID))) {
                if (StrEqual(clientSteamID, steamid)) {
                    return i;
                }
            }
        }
    }
    return -1;
}

bool IsHighKDPlayer(const char[] steamid) {
    int index = FindPlayerTrackingBySteamID(steamid);
    if (index < 0) return false;
    
    PlayerData pData;
    g_hPlayerData.GetArray(index, pData, sizeof(pData));
    
    return pData.isHighKD;
}

// ----------------------------------------------------------------------
// Cache Cleanup (OnGameFrame)
// ----------------------------------------------------------------------

public void OnGameFrame() {
    // Clean expired entries periodically
    static int lastCleanup = 0;
    
    if (GetTime() - lastCleanup > 60) {  // Every 60 seconds
        lastCleanup = GetTime();
        CleanupExpiredCache();
    }
}

void CleanupExpiredCache() {
    int now = GetTime();
    KDData data;
    
    for (int i = g_hKDCache.Length - 1; i >= 0; i--) {
        g_hKDCache.GetArray(i, data, sizeof(data));
        
        if (data.expires_at < now) {
            g_hKDCache.Erase(i);
            
            if (g_bDebugMode) {
                LogMessage("KD cache expired: %s", data.steamid);
            }
        }
    }
}

// ----------------------------------------------------------------------
// Team Balance Integration
// ----------------------------------------------------------------------

// This function can be called by other plugins or called periodically
// to enforce team balance based on KD
public Action ProcessTeamBalance() {
    if (!g_bEnabled) return Plugin_Continue;
    
    // Implementation would go here
    // Example: If high KD players are heavily unbalanced, swap them
    
    return Plugin_Continue;
}
```

---

## 6. Example Data Flow

### Player Connect Sequence

```
1. Player connects to CS:S server
   └─> "L 02/01/2026 - 16:00:00: STEAM_0:1:12345 connected"

2. HLstatsX daemon receives connect event
   └─> doEvent_Connect() in HLstats_EventHandlers.plib
       └─> Calls kd_provider.pl or integrated KD fetch

3. KD Provider queries HLstats DB
   └─> SELECT kills, deaths FROM hlstats_Players 
       WHERE playerId IN (SELECT playerId FROM hlstats_PlayerUniqueIds 
                          WHERE uniqueId = 'STEAM_0:1:12345')
       └─> Returns: { kills: 1500, deaths: 612, kd: 2.45 }

4. Cache result with TTL
   └─> kd_cache:STEAM_0:1:12345 = {
           kd: 2.45,
           kills: 1500,
           deaths: 612,
           cached_at: 1735830400,
           expires_at: 1735834000,
           is_new: 0
       }

5. Push KD to game server via RCON
   └─> rcon.execute('sm_kd_set "STEAM_0:1:12345" "2.45" "3600"')

6. SourceMod plugin receives command
   └─> Command_KDSet() callback
       └─> StoreKDData() in g_hKDCache array
           └─> Update player tracking
               └─> Mark player as "highKD" (KD > 2.0)

7. Team balance decision
   └─> On player team change or round end
       └─> Check IsHighKDPlayer()
           └─> If high KD, apply balance rules
```

### Reconnection Handling

```
1. Player disconnects
   └─> Event_PlayerDisconnect
       └─> RemovePlayerTracking(steamid)

2. Player reconnects (same session or new)
   └─> Event_PlayerConnectFull
       └─> AddPlayerTracking(steamid)
           └─> Check cache
               └─> If cached and not expired:
                   └─> Use cached KD immediately
               └─> If expired:
                   └─> Request fresh data from HLstatsX

3. Map change
   └─> KD cache persists (in-memory)
       └─> TTL continues counting
           └─> Expired entries cleaned on next access
```

---

## 7. Configuration Files

### HLstatsX KD Provider Config (`hlstatsxd/kd_provider.conf`)

```ini
# HLstatsX KD Provider Configuration

[database]
host     = localhost
port     = 3306
name     = hlstatsx
user     = hlstatsxd
password = your_secure_password

[cache]
ttl_min        = 1800      # 30 minutes minimum
ttl_default    = 3600      # 1 hour default
ttl_max        = 7200      # 2 hours maximum
batch_size     = 50        # Players per batch query

[thresholds]
high_kd        = 2.0       # KD considered "high"
new_player_kills = 10      # Players with <10 kills are "new"

[servers]
# Server definitions for RCON pushing
# format: address:port:rcon_password:game
# example: 192.168.1.100:27015:rconpass:css

[logging]
level          = 1         # 0=error, 1=info, 2=debug
file           = /var/log/hlstatsxd/kd_provider.log
```

### SourceMod Plugin Config (`addons/sourcemod/configs/kd_enforcement.cfg`)

```c
// HLstatsX KD Enforcement Plugin Configuration

"KDEnforcement"
{
    // Enable/disable the plugin
    "Enabled"             "1"
    
    // KD threshold for "high KD" classification
    "HighKDThreshold"     "2.0"
    
    // Default TTL for KD cache (seconds)
    "DefaultTTL"          "3600"
    
    // Enable debug logging
    "DebugMode"           "0"
    
    // Team balance settings
    "BalanceEnabled"      "1"
    
    // Max KD difference allowed between teams
    "MaxTeamKDDiff"       "0.5"
    
    // Enable high KD player restrictions
    "HighKDRestrictions"  "1"
}
```

---

## 8. Installation Steps

### Step 1: Install KD Provider

```bash
# Copy KD provider script
cp kd_provider.pl /opt/hlstatsxd/plugins/
chmod +x /opt/hlstatsxd/plugins/kd_provider.pl

# Create config
cp kd_provider.conf /opt/hlstatsxd/etc/kd_provider.conf

# Add to daemon startup
echo "plugins/kd_provider.pl" >> /opt/hlstatsxd/hlstatsdaemon.conf
```

### Step 2: Install SourceMod Plugin

```bash
# Compile plugin
cd /path/to/sourcemod-scripting
spcomp kd_enforcement.sp

# Copy to plugins folder
cp kd_enforcement.smx /path/to/sourcemod/plugins/

# Copy config
cp kd_enforcement.cfg /path/to/sourcemod/configs/

# Restart server or reload plugin
sm plugins load kd_enforcement
```

### Step 3: Configure Database Access

```sql
-- Create read-only user for KD provider
CREATE USER 'hlstats_kd'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT ON hlstatsx.hlstats_Players TO 'hlstats_kd'@'localhost';
GRANT SELECT ON hlstatsx.hlstats_PlayerUniqueIds TO 'hlstats_kd'@'localhost';
FLUSH PRIVILEGES;
```

### Step 4: Test Installation

```bash
# Test KD provider
perl -I/opt/hlstatsxd/lib /opt/hlstatsxd/plugins/kd_provider.pl --test

# Test SourceMod plugin
sm plugins list
sm_kd_debug
```

---

## 9. Troubleshooting

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| KD not showing | RCON not connected | Check `sm_kd_debug` output |
| Stale KD data | TTL expired | Wait for refresh or manually reload |
| Player marked new incorrectly | No kills yet | Wait for player to get kills |
| RCON command rejected | Wrong format | Verify `sm_kd_set` command format |
| Cache growing too large | No cleanup | Enable cache cleanup in config |

### Debug Commands

```bash
# HLstatsX side
perl kd_provider.pl --debug --steamid STEAM_0:1:12345

# SourceMod side
sm_kd_debug
sm_kd_check STEAM_0:1:12345
sm logs
```

---

## 10. Performance Considerations

- **Batch Queries**: Fetch multiple players at once to reduce DB load
- **In-Memory Cache**: Avoids repeated DB queries for same SteamID
- **Async RCON**: Non-blocking RCON sends to avoid server lag
- **TTL Management**: Prevents cache bloat with automatic expiration

---

## Conclusion

This implementation provides:
1. **Low-latency KD delivery** via RCON push on connect
2. **Resilient caching** with TTL-based expiration
3. **SteamID handling** for all formats (Steam2, SteamID64)
4. **SourceMod integration** for team balance decisions
5. **Complete fallback** if KD provider is unavailable
