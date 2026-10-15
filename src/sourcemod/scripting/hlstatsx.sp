/**
 * HLstatsX Community Edition - SourceMod plugin to display ingame messages
 * http://www.hlxcommunity.com/
 * Copyright (C) 2008-2009 Nicholas Hastings
 * Copyright (C) 2007-2009 TTS Oetzel & Goerz GmbH
 * Modified by Nicholas Hastings (psychonic) for use with HLstatsX Community Edition
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

#pragma semicolon 1

#include <sourcemod>
#include <sdktools>
#include <loghelper>
#include <cstrike>
#include <clientprefs>
#undef REQUIRE_PLUGIN
#undef REQUIRE_EXTENSIONS
#include <hlstatsx_api>
#define REQUIRE_EXTENSIONS
#define REQUIRE_PLUGIN

// Minimal upstream HLstatsX playerinfo protocol, owned by this plugin.
#define HLX_LANGUAGE_TIMEOUT 30.0
int g_iLanguageRequestId;
int g_iLanguageRequest[MAXPLAYERS + 1];
int g_iLanguageSerial[MAXPLAYERS + 1];
Handle g_hLanguageTimer[MAXPLAYERS + 1];

#define VERSION "1.11.4"
#define HLXTAG "HLstatsX:CE"

enum GameType {
	Game_Unknown = -1,
	Game_CSS,
	Game_DODS,
	Game_L4D,
	Game_TF,
	Game_HL2MP,
	Game_INSMOD,
	Game_FF,
	Game_ZPS,
	Game_AOC,
	Game_FOF,
	Game_GES,
	Game_PVKII,
	Game_CSP,
	Game_ND,
	Game_DDD,
};

new GameType:gamemod = Game_Unknown;

new Handle: hlx_block_chat_commands;
new Handle: hlx_message_prefix;
new Handle: hlx_protect_address;
new Handle: hlx_server_tag;
new Handle: sv_tags;
new Handle: message_recipients;
new const String: blocked_commands[][] = { "rank", "skill", "points", "place", "session", "session_data", 
                                     "kpd", "kdratio", "kdeath", "next", "load", "status", "servers", 
                                     "top20", "top10", "top5", "clans", "bans", "cheaters", "statsme", "weapons", 
                                     "weapon", "action", "actions", "accuracy", "targets", "target", "kills", 
                                     "kill", "player_kills", "cmd", "cmds", "command", "hlx_display 0", 
                                     "hlx_display 1", "hlx_teams 0", "hlx_teams 1", "hlx_hideranking", 
                                     "hlx_chat 0", "hlx_chat 1", "hlx_menu", "servers 1", "servers 2", 
                                     "servers 3", "hlx", "hlstatsx", "help" };

new Handle:HLstatsXMenuMain;
new Handle:HLstatsXMenuAuto;
new Handle:HLstatsXMenuEvents;

new Handle: PlayerColorArray;
new ColorSlotArray[] = { -1, -1, -1, -1, -1, -1 };

new const String: modnamelist[][] = {
	"Counter-Strike: Source",
	"Day of Defeat: Source",
	"Left 4 Dead (1 or 2)",
	"Team Fortress 2",
	"Half-Life 2 Deathmatch",
	"Insurgency",
	"Fortress Forever",
	"Zombie Panic: Source",
	"Age of Chivalry",
	"Fistful of Frags",
	"GoldenEye: Source",
	"Pirates, Vikings, and Knights",
	"CSPromod",
	"Nuclear Dawn",
	"Dino D-Day",
	"Counter-Strike: Global Offensive"
};

new String: message_prefix[32];
new bool:g_bPlyrCanDoMotd[MAXPLAYERS+1];
new bool:g_bGameCanDoMotd = true;
new bool:g_bTrackColors4Chat;
EngineVersion g_evEngineVersion = Engine_Unknown;
new Handle:g_cvarTeamPlay = INVALID_HANDLE;
new bool:g_bTeamPlay;
new bool:g_bLateLoad = false;
new bool:g_bIgnoreNextTagChange = false;
new Handle:g_hCustomTags;
new Handle:fade_msg;
new Handle:g_hKDCache	= INVALID_HANDLE;	/* key: steamid string (prefer Steam3), value: FloatToCell(kd) */
new Handle:g_hKDExpiry	= INVALID_HANDLE;	/* key: steamid string (prefer Steam3), value: expiry epoch (int) */
new bool:g_bDominationDebug = false;
new bool:g_bDominationAutoRepro = false;
new String:g_sDominationDebugLog[PLATFORM_MAX_PATH];
new String:g_sDominationAutoReproFlag[PLATFORM_MAX_PATH];

#define SVTAGSIZE 128

public Plugin:myinfo = {
	name = "HLstatsX CE Ingame Plugin",
	author = "psychonic",
	description = "Provides ingame functionality for interaction from an HLstatsX CE installation",
	version = VERSION,
	url = "http://www.hlxcommunity.com"
};


public APLRes:AskPluginLoad2(Handle:myself, bool:late, String:error[], err_max)
{
	g_bLateLoad = late;
	MarkNativeAsOptional("CS_SwitchTeam");
	MarkNativeAsOptional("SetCookieMenuItem");
	MarkNativeAsOptional("HLStatsX_Api_HandleResponse");

	CreateNative("HLX_KD_GetClient", Native_HLX_KD_GetClient);
	CreateNative("HLX_GetPreferredTeam", Native_HLX_GetPreferredTeam);
	RegPluginLibrary("hlstatsx_kd");
	
	return APLRes_Success;
}


public OnPluginStart() 
{
	RegServerCmd("hlx_api_response", HLX_ClientLanguageResponse);
	g_evEngineVersion = GetEngineVersion();

	if (g_evEngineVersion == Engine_CSGO) {
		SetFailState("This plugin is not for CS:GO, please use hlstatsx-csgo plugin instead.");
	}

	get_server_mod();

	CreateHLstatsXMenuMain(HLstatsXMenuMain);
	CreateHLstatsXMenuAuto(HLstatsXMenuAuto);
	CreateHLstatsXMenuEvents(HLstatsXMenuEvents);

	RegServerCmd("hlx_sm_psay",          hlx_sm_psay);
	RegServerCmd("hlx_sm_psay2",         hlx_sm_psay2);
	RegServerCmd("hlx_sm_bulkpsay",      hlx_sm_psay);
	RegServerCmd("hlx_sm_csay",          hlx_sm_csay);
	RegServerCmd("hlx_sm_msay",          hlx_sm_msay);
	RegServerCmd("hlx_sm_tsay",          hlx_sm_tsay);
	RegServerCmd("hlx_sm_hint",          hlx_sm_hint);
	RegServerCmd("hlx_sm_browse",        hlx_sm_browse);
	RegServerCmd("hlx_sm_swap",          hlx_sm_swap);
	RegAdminCmd("sm_swap", sm_swap, ADMFLAG_BAN, "sm_swap <#userid>");
	RegServerCmd("hlx_sm_redirect",      hlx_sm_redirect);
	RegServerCmd("hlx_sm_player_action", hlx_sm_player_action);
	RegServerCmd("hlx_sm_team_action",   hlx_sm_team_action);
	RegServerCmd("hlx_sm_world_action",  hlx_sm_world_action);
	RegServerCmd("hlx_debug_dom_on",     Command_HLX_DebugDomOn);
	RegServerCmd("hlx_debug_dom_off",    Command_HLX_DebugDomOff);
	RegServerCmd("hlx_debug_dom_dump",   Command_HLX_DebugDomDump);
	RegServerCmd("hlx_debug_dom_pair",   Command_HLX_DebugDomPair);

	if (gamemod == Game_INSMOD)
	{
		AddCommandListener(hlx_block_commands, "say2");
	}
	else if (gamemod == Game_ND)
	{
		AddCommandListener(hlx_block_commands, "say_squad");
	}
	
	AddCommandListener(hlx_block_commands, "say");
	AddCommandListener(hlx_block_commands, "say_team");
	
	switch (gamemod)
	{
		case Game_CSS, Game_L4D, Game_TF, Game_HL2MP, Game_AOC, Game_FOF, Game_PVKII, Game_ND, Game_DDD:
		{
			g_bTrackColors4Chat = true;
			HookEvent("player_team",  HLstatsX_Event_PlyTeamChange, EventHookMode_Pre);
		}
	}
	
	switch (gamemod)
	{
		case Game_L4D, Game_INSMOD, Game_GES:
		{
			g_bGameCanDoMotd = false;
		}
	}
	
	if (gamemod == Game_HL2MP)
	{
		g_cvarTeamPlay = FindConVar("mp_teamplay");
		if (g_cvarTeamPlay != INVALID_HANDLE)
		{
			g_bTeamPlay = GetConVarBool(g_cvarTeamPlay);
			HookConVarChange(g_cvarTeamPlay, OnTeamPlayChange);
		}
	}
	
	CreateConVar("hlxce_plugin_version", VERSION, "HLstatsX:CE Ingame Plugin", FCVAR_NOTIFY);
	CreateConVar("hlxce_version", "", "HLstatsX:CE", FCVAR_NOTIFY);
	CreateConVar("hlxce_webpage", "http://www.hlxcommunity.com", "http://www.hlxcommunity.com", FCVAR_NOTIFY);
	
	hlx_block_chat_commands = CreateConVar("hlx_block_commands", "1", "If activated HLstatsX commands are blocked from the chat area");
	hlx_message_prefix = CreateConVar("hlx_message_prefix", "", "Define the prefix displayed on every HLstatsX ingame message");
	hlx_protect_address = CreateConVar("hlx_protect_address", "", "Address to be protected for logging/forwarding");
	hlx_server_tag = CreateConVar("hlx_server_tag", "1", "If enabled, adds \"HLstatsX:CE\" to server tags on supported games. 1 = Enabled (default), 0 = Disabled",
		_, true, 0.0, true, 1.0);
	
	g_hCustomTags = CreateArray(SVTAGSIZE);
	sv_tags = FindConVar("sv_tags");

	if (g_bLateLoad)
	{
		GetConVarString(hlx_message_prefix, message_prefix, sizeof(message_prefix));
		decl String:protaddr[24];
		GetConVarString(hlx_protect_address, protaddr, sizeof(protaddr));
		OnProtectAddressChange(hlx_protect_address, "", protaddr);
	}
	
	MyAddServerTag(HLXTAG);
	
	HookConVarChange(hlx_message_prefix, OnMessagePrefixChange);
	HookConVarChange(hlx_protect_address, OnProtectAddressChange);
	HookConVarChange(hlx_server_tag, OnServerTagChange);
	if (sv_tags != INVALID_HANDLE)
	{
		HookConVarChange(sv_tags, OnSVTagsChange);
	}
	
	RegServerCmd("log", ProtectLoggingChange);
	RegServerCmd("logaddress_del", ProtectForwardingChange);
	RegServerCmd("logaddress_delall", ProtectForwardingDelallChange);
	RegServerCmd("hlx_message_prefix_clear", MessagePrefixClear);

	PlayerColorArray = CreateArray();
	message_recipients = CreateStack();
	BuildPath(Path_SM, g_sDominationDebugLog, sizeof(g_sDominationDebugLog), "logs/hlstatsx_domination_debug.log");
	BuildPath(Path_SM, g_sDominationAutoReproFlag, sizeof(g_sDominationAutoReproFlag), "configs/hlx_domination_autorepro.flag");
	g_bDominationAutoRepro = FileExists(g_sDominationAutoReproFlag);
	if (g_bDominationAutoRepro)
	{
		g_bDominationDebug = true;
		LogToFileEx(g_sDominationDebugLog, "autorepro_flag_detected path=%s", g_sDominationAutoReproFlag);
		CreateTimer(8.0, Timer_HLX_DebugAutoReproPrepare, _, TIMER_FLAG_NO_MAPCHANGE);
	}
	
	GetTeams(gamemod == Game_INSMOD);

	g_hKDCache = CreateTrie();
	g_hKDExpiry = CreateTrie();
	RegServerCmd("sm_kd_set", Command_HLX_KDSet);
	RegServerCmd("sm_kd_get", Command_HLX_KDGet);
}


public OnAllPluginsLoaded()
{
	if (LibraryExists("clientprefs"))
	{
		SetCookieMenuItem(HLXSettingsMenu, 0, "HLstatsX:CE Settings");
	}
}

public HLXSettingsMenu(client, CookieMenuAction:action, any:info, String:buffer[], maxlen)
{
	if (action == CookieMenuAction_SelectOption)
	{
		DisplayMenu(HLstatsXMenuMain, client, MENU_TIME_FOREVER);
	}
}


public OnMapStart()
{
	PrecacheSound("common/warning.wav");
	GetTeams(gamemod == Game_INSMOD);

	if (g_bTrackColors4Chat)
	{
		find_player_team_slot(2);
		find_player_team_slot(3);
		if (gamemod == Game_PVKII)
		{
			find_player_team_slot(4);
		}
	}
}

bool:BTagsSupported()
{
	return (sv_tags != INVALID_HANDLE && (g_evEngineVersion == Engine_SourceSDK2007 || g_evEngineVersion == Engine_DODS || g_evEngineVersion == Engine_HL2DM || g_evEngineVersion == Engine_TF2 || gamemod == Game_ND));
}

stock MyAddServerTag(const String:tag[])
{
	if (!BTagsSupported())
	{
		// game doesn't support sv_tags
		return;
	}
	
	if (!GetConVarBool(hlx_server_tag))
	{
		return;
	}
	
	if (FindStringInArray(g_hCustomTags, tag) == -1)
	{
		PushArrayString(g_hCustomTags, tag);
	}
	
	decl String:current_tags[SVTAGSIZE];
	GetConVarString(sv_tags, current_tags, sizeof(current_tags));
	if (StrContains(current_tags, tag) > -1)
	{
		// already have tag
		return;
	}
	
	decl String:new_tags[SVTAGSIZE];
	Format(new_tags, sizeof(new_tags), "%s%s%s", current_tags, (current_tags[0]!=0)?",":"", tag);
	
	new flags = GetConVarFlags(sv_tags);
	SetConVarFlags(sv_tags, flags & ~FCVAR_NOTIFY);
	g_bIgnoreNextTagChange = true;
	SetConVarString(sv_tags, new_tags);
	g_bIgnoreNextTagChange = false;
	SetConVarFlags(sv_tags, flags);
}

stock MyRemoveServerTag(const String:tag[])
{
	if (!BTagsSupported())
	{
		// game doesn't support sv_tags
		return;
	}
	
	new idx = FindStringInArray(g_hCustomTags, tag);
	if (idx > -1)
	{
		RemoveFromArray(g_hCustomTags, idx);
	}
	
	decl String:current_tags[SVTAGSIZE];
	GetConVarString(sv_tags, current_tags, sizeof(current_tags));
	if (StrContains(current_tags, tag) == -1)
	{
		// tag isn't on here, just bug out
		return;
	}
	
	ReplaceString(current_tags, sizeof(current_tags), tag, "");
	ReplaceString(current_tags, sizeof(current_tags), ",,", "");
	
	new flags = GetConVarFlags(sv_tags);
	SetConVarFlags(sv_tags, flags & ~FCVAR_NOTIFY);
	g_bIgnoreNextTagChange = true;
	SetConVarString(sv_tags, current_tags);
	g_bIgnoreNextTagChange = false;
	SetConVarFlags(sv_tags, flags);
}

get_server_mod()
{
	new String: game_description[64];
	GetGameDescription(game_description, sizeof(game_description), true);
	
	if (StrContains(game_description, "Counter-Strike", false) != -1)
	{
		gamemod = Game_CSS;
	}
	else if (StrContains(game_description, "Day of Defeat", false) != -1)
	{
		gamemod = Game_DODS;
	}
	else if (StrContains(game_description, "Half-Life 2 Deathmatch", false) != -1)
	{
		gamemod = Game_HL2MP;
	}
	else if (StrContains(game_description, "Team Fortress", false) != -1)
	{
		gamemod = Game_TF;
	}
	else if (StrContains(game_description, "L4D", false) != -1 || StrContains(game_description, "Left 4 D", false) != -1)
	{
		gamemod = Game_L4D;
	}
	else if (StrContains(game_description, "Insurgency", false) != -1)
	{
		gamemod = Game_INSMOD;
		//psychonic - added detection for more supported games
	}
	else if (StrContains(game_description, "Fortress Forever", false) != -1)
	{
		gamemod = Game_FF;
	}
	else if (StrContains(game_description, "ZPS", false) != -1)
	{
		gamemod = Game_ZPS;
	}
	else if (StrContains(game_description, "Age of Chivalry", false) != -1)
	{
		gamemod = Game_AOC;
	}
	else if (StrContains(game_description, "PVKII", false) != -1)
	{
		gamemod = Game_PVKII;
	}
	else if (StrContains(game_description, "CSPromod", false) != -1)
	{
		gamemod = Game_CSP;
	}
	else if (StrContains(game_description, "Nuclear Dawn", false) != -1)
	{
		gamemod = Game_ND;
	}
	
	// game mod could not detected, try further
	if (gamemod == Game_Unknown)
	{
		new String: game_folder[64];
		GetGameFolderName(game_folder, sizeof(game_folder));
		if (StrContains(game_folder, "cstrike", false) != -1)
		{
			gamemod = Game_CSS;
		}
		else if (strncmp(game_folder, "dod", 3, false) == 0)
		{
			gamemod = Game_DODS;
		}
		else if (StrContains(game_folder, "hl2mp", false) != -1 || StrContains(game_folder, "hl2ctf", false) != -1)
		{
			gamemod = Game_HL2MP;
		}
		else if (StrContains(game_folder, "fistful_of_frags", false) != -1)
		{
			gamemod = Game_FOF;
		}
		else if (strncmp(game_folder, "tf", 2, false) == 0)
		{
			gamemod = Game_TF;
		}
		else if (StrContains(game_folder, "left4dead", false) != -1)
		{
			gamemod = Game_L4D;
		}
		else if (StrContains(game_folder, "insurgency", false) != -1)
		{
			gamemod = Game_INSMOD;
			//psychonic - added detection for more supported games
		}
		else if (StrContains(game_folder, "FortressForever", false) != -1)
		{
			gamemod = Game_FF;
		}
		else if (StrContains(game_folder, "zps", false) != -1)
		{
			gamemod = Game_ZPS;
		}
		else if (StrContains(game_folder, "ageofchivalry", false) != -1)
		{
			gamemod = Game_AOC;
		}
		else if (StrContains(game_folder, "gesource", false) != -1)
		{
			gamemod = Game_GES;
		}
		else if (StrContains(game_folder, "pvkii", false) != -1)
		{
			gamemod = Game_PVKII;
		}
		else if (StrContains(game_folder, "cspromod", false) != -1)
		{
			gamemod = Game_CSP;
		}
		else if (StrContains(game_folder, "nucleardawn", false) != -1)
		{
			gamemod = Game_ND;
		}
		else if (StrContains(game_folder, "dinodday", false) != -1)
		{
			gamemod = Game_DDD;
		}
		else
		{
			LogToGame("HLX:CE Mod Not In Detected List, Using Defaults (%s, %s)", game_description, game_folder);
			LogToGame("HLX:CE If this is incorrect, please file a bug at hlxcommunity.com");
		}
	}
	if (gamemod > Game_Unknown)
	{
		LogToGame("HLX:CE Mod Detection: %s", modnamelist[_:gamemod]);
		LogToGame("HLX:CE If this is incorrect, please file a bug at hlxcommunity.com");
	}
}

public OnClientPostAdminCheck(client)
{
	HLX_RequestClientLanguage(client);

	if (g_bGameCanDoMotd && !IsFakeClient(client))
	{
		QueryClientConVar(client, "cl_disablehtmlmotd", motdQuery);
	}
}

stock bool HLX_CanOverrideLanguage(int client)
{
	if (client <= 0 || client > MaxClients || !IsClientInGame(client) || IsFakeClient(client))
	{
		return false;
	}

	int english = GetLanguageByCode("en");
	// Original English includes deliberately selected English. Never replace another choice.
	return english != -1 && GetClientOriginalLanguage(client) == english
		&& GetClientLanguage(client) == english;
}

stock void HLX_RequestClientLanguage(int client)
{
	if (!HLX_CanOverrideLanguage(client) || g_iLanguageRequest[client] != 0)
	{
		return;
	}

	char authId[32], teamName[32];
	if (!GetClientAuthId(client, AuthId_Steam2, authId, sizeof(authId)))
	{
		return;
	}
	GetTeamName(GetClientTeam(client), teamName, sizeof(teamName));
	// Stay in upstream's 1..65535 range and never reuse an outstanding id.
	bool collision;
	do
	{
		g_iLanguageRequestId = g_iLanguageRequestId % 65535 + 1;
		collision = false;
		for (int other = 1; other <= MaxClients; other++)
		{
			if (g_iLanguageRequest[other] == g_iLanguageRequestId) collision = true;
		}
	} while (collision);
	g_iLanguageRequest[client] = g_iLanguageRequestId;
	g_iLanguageSerial[client] = GetClientSerial(client);
	g_hLanguageTimer[client] = CreateTimer(HLX_LANGUAGE_TIMEOUT, HLX_LanguageTimeout, client);
	LogToGame("\"%N<%d><%s><%s>\" api_request \"playerinfo\" (value \"%d\")",
		client, GetClientUserId(client), authId, teamName, g_iLanguageRequest[client]);
}

stock void HLX_ClearLanguageRequest(int client)
{
	delete g_hLanguageTimer[client];
	g_iLanguageRequest[client] = 0;
	g_iLanguageSerial[client] = 0;
}

public Action HLX_LanguageTimeout(Handle timer, any client)
{
	if (g_hLanguageTimer[client] == timer)
	{
		g_hLanguageTimer[client] = null;
		HLX_ClearLanguageRequest(client);
	}
	return Plugin_Stop;
}

public void OnPluginEnd()
{
	for (int client = 1; client <= MaxClients; client++)
	{
		HLX_ClearLanguageRequest(client);
	}
}

stock bool HLX_ReadPositiveArgument(int index, int &value)
{
	char argument[32];
	GetCmdArg(index, argument, sizeof(argument));
	int length = strlen(argument);
	if (length == 0 || length > 5) return false;
	for (int i = 0; i < length; i++)
	{
		if (argument[i] < '0' || argument[i] > '9') return false;
	}
	return StringToIntEx(argument, value) == length && value > 0;
}

stock void HLX_CountryLanguage(const char[] country, char[] code, int maxlen)
{
	strcopy(code, maxlen, "en");
	if (StrEqual(country, "CZ", false)) strcopy(code, maxlen, "cze");
	else if (StrEqual(country, "SK", false)) strcopy(code, maxlen, "sk");
	else if (StrEqual(country, "PL", false)) strcopy(code, maxlen, "pl");
	else if (StrEqual(country, "HU", false)) strcopy(code, maxlen, "hu");
	else if (StrEqual(country, "DE", false) || StrEqual(country, "AT", false)) strcopy(code, maxlen, "de");
	else if (StrEqual(country, "UA", false)) strcopy(code, maxlen, "uk");
	else if (StrEqual(country, "FR", false)) strcopy(code, maxlen, "fr");
}

public Action HLX_ClientLanguageResponse(int args)
{
	int type, requestId, userId;
	if (args < 1 || !HLX_ReadPositiveArgument(1, type))
	{
		return Plugin_Handled;
	}
	if (type == HLX_CALLBACK_TYPE_JOIN_POLICY)
	{
		if (args >= 5 && GetFeatureStatus(FeatureType_Native, "HLStatsX_Api_HandleResponse") == FeatureStatus_Available)
		{
			HLStatsX_Api_HandleResponse(args);
		}
		return Plugin_Handled;
	}
	if (args < 14 || type != HLX_CALLBACK_TYPE_PLAYER_INFO
		|| !HLX_ReadPositiveArgument(2, requestId) || !HLX_ReadPositiveArgument(3, userId))
	{
		return Plugin_Handled;
	}
	if (GetFeatureStatus(FeatureType_Native, "HLStatsX_Api_HandleResponse") == FeatureStatus_Available)
	{
		HLStatsX_Api_HandleResponse(args);
	}

	int client;
	for (int pending = 1; pending <= MaxClients; pending++)
	{
		if (g_iLanguageRequest[pending] == requestId)
		{
			client = pending;
			break;
		}
	}
	if (client == 0) return Plugin_Handled;
	int serial = g_iLanguageSerial[client];
	HLX_ClearLanguageRequest(client);
	if (GetClientOfUserId(userId) != client || GetClientFromSerial(serial) != client
		|| !HLX_CanOverrideLanguage(client))
	{
		return Plugin_Handled;
	}

	// Type 101: type, request id, userid, ten stats fields, country at arg 14.
	char country[16], code[4];
	GetCmdArg(14, country, sizeof(country));
	if (strlen(country) != 2) return Plugin_Handled;
	HLX_CountryLanguage(country, code, sizeof(code));

	int language = GetLanguageByCode(code);
	if (language != -1)
	{
		SetClientLanguage(client, language);
	}
	return Plugin_Handled;
}


public motdQuery(QueryCookie:cookie, client, ConVarQueryResult:result, const String:cvarName[], const String:cvarValue[])
{
	if (result == ConVarQuery_Okay && StringToInt(cvarValue) == 0 || result != ConVarQuery_Okay)
	{
		g_bPlyrCanDoMotd[client] = true;
	}
}


public OnServerTagChange(Handle:cvar, const String:oldVal[], const String:newVal[])
{
	if (GetConVarBool(hlx_server_tag))
	{
		MyAddServerTag(HLXTAG);
	}
	else
	{
		MyRemoveServerTag(HLXTAG);
	}
}

public OnSVTagsChange(Handle:cvar, const String:oldVal[], const String:newVal[])
{
	if (g_bIgnoreNextTagChange)
	{
		// we fired this callback, no need to reapply tags
		return;
	}
	
	// reapply each custom tag
	new cnt = GetArraySize(g_hCustomTags);
	for (new i = 0; i < cnt; i++)
	{
		decl String:tag[SVTAGSIZE];
		GetArrayString(g_hCustomTags, i, tag, sizeof(tag));
		MyAddServerTag(tag);
	}
}


public OnProtectAddressChange(Handle:cvar, const String:oldVal[], const String:newVal[])
{
	if (newVal[0] > 0)
	{
		decl String: log_command[192];
		Format(log_command, sizeof(log_command), "logaddress_add %s", newVal);
		LogToGame("Command: %s", log_command);
		ServerCommand(log_command);
	}
}

public OnTeamPlayChange(Handle:cvar, const String:oldVal[], const String:newVal[])
{
	g_bTeamPlay = GetConVarBool(g_cvarTeamPlay);
}

public Action:ProtectLoggingChange(args)
{
	if (hlx_protect_address != INVALID_HANDLE)
	{
		decl String: protect_address[192];
		GetConVarString(hlx_protect_address, protect_address, sizeof(protect_address));
		if (strcmp(protect_address, "") != 0)
		{
			if (args >= 1)
			{
				decl String: log_action[192];
				GetCmdArg(1, log_action, sizeof(log_action));
				if ((strcmp(log_action, "off") == 0) || (strcmp(log_action, "0") == 0))
				{
					LogToGame("HLstatsX address protection active, logging reenabled!");
					ServerCommand("log 1");
				}
			}
		}
	}
	return Plugin_Continue;
}


public Action:ProtectForwardingChange(args)
{
	if (hlx_protect_address != INVALID_HANDLE)
	{
		decl String: protect_address[192];
		GetConVarString(hlx_protect_address, protect_address, sizeof(protect_address));
		if (strcmp(protect_address, "") != 0)
		{
			if (args == 1)
			{
				decl String: log_action[192];
				GetCmdArg(1, log_action, sizeof(log_action));
				if (strcmp(log_action, protect_address) == 0)
				{
					decl String: log_command[192];
					Format(log_command, sizeof(log_command), "logaddress_add %s", protect_address);
					LogToGame("HLstatsX address protection active, logaddress readded!");
					ServerCommand(log_command);
				}
			}
			else if (args > 1)
			{
				new String: log_action[192];
				for(new i = 1; i <= args; i++)
				{
					decl String: temp_argument[192];
					GetCmdArg(i, temp_argument, sizeof(temp_argument));
					strcopy(log_action[strlen(log_action)], sizeof(log_action), temp_argument);
				}
				if (strcmp(log_action, protect_address) == 0)
				{
					decl String: log_command[192];
					Format(log_command, sizeof(log_command), "logaddress_add %s", protect_address);
					LogToGame("HLstatsX address protection active, logaddress readded!");
					ServerCommand(log_command);
				}
			
			}
		}
	}
	return Plugin_Continue;
}



public Action:ProtectForwardingDelallChange(args)
{
	if (hlx_protect_address != INVALID_HANDLE)
	{
		decl String: protect_address[192];
		GetConVarString(hlx_protect_address, protect_address, sizeof(protect_address));
		if (strcmp(protect_address, "") != 0)
		{
			decl String: log_command[192];
			Format(log_command, sizeof(log_command), "logaddress_add %s", protect_address);
			LogToGame("HLstatsX address protection active, logaddress readded!");
			ServerCommand(log_command);
		}
	}
	return Plugin_Continue;
}


public OnMessagePrefixChange(Handle:cvar, const String:oldVal[], const String:newVal[])
{
	strcopy(message_prefix, sizeof(message_prefix), newVal);
}


public Action:MessagePrefixClear(args)
{
	message_prefix = "";
}


find_player_team_slot(team_index) 
{
	if (team_index > -1)
	{
		ColorSlotArray[team_index] = -1;
		for(new i = 1; i <= MaxClients; i++)
		{
			if (IsClientInGame(i) && GetClientTeam(i) == team_index)
			{
				ColorSlotArray[team_index] = i;
				break;
			}
		}
	}
}


stock validate_team_colors() 
{
	for (new i = 0; i < sizeof(ColorSlotArray); i++)
	{
		new color_client = ColorSlotArray[i];
		if (color_client > 0)
		{
			if (IsClientInGame(color_client) && GetClientTeam(color_client) != color_client)
			{
				find_player_team_slot(i);
			}
		}
		else
		{
			if (i == 2 || i == 3 || (i == 4 && gamemod == Game_PVKII))
			{
				find_player_team_slot(i);
			}
		}
	}
}

public OnClientDisconnect(client)
{
	HLX_ClearLanguageRequest(client);
	if (g_bTrackColors4Chat && client > 0 && IsClientInGame(client))
	{
		new team_index = GetClientTeam(client);
		if (client == ColorSlotArray[team_index])
		{
			ColorSlotArray[team_index] = -1;
		}
	}
	
	g_bPlyrCanDoMotd[client] = false;
}

color_player(color_type, player_index, String: client_message[192]) 
{
	new color_player_index = -1;
	if (g_bTrackColors4Chat || (gamemod == Game_DODS) || (gamemod == Game_ZPS) || (gamemod == Game_GES) || (gamemod == Game_CSP))
	{
		decl String: client_name[192];
		GetClientName(player_index, client_name, sizeof(client_name));
		if ((strcmp(client_message, "") != 0) && (strcmp(client_name, "") != 0))
		{
			if (color_type == 1)
			{
				decl String: search_client_name[192];
				Format(search_client_name, sizeof(search_client_name), "%s ", client_name);
				decl String: colored_player_name[192];
				switch (gamemod)
				{
					case Game_DODS, Game_GES, Game_CSP:
						Format(colored_player_name, sizeof(colored_player_name), "\x04%s\x01 ", client_name);
					case Game_HL2MP:
						Format(colored_player_name, sizeof(colored_player_name), "%c%s\x01 ", g_bTeamPlay?3:4, client_name);
					case Game_ZPS:
						Format(colored_player_name, sizeof(colored_player_name), "\x05%s\x01 ", client_name);
					default:
						Format(colored_player_name, sizeof(colored_player_name), "\x03%s\x01 ", client_name);
				}
				if (ReplaceString(client_message, sizeof(client_message), search_client_name, colored_player_name) > 0)
				{
					return player_index;
				}
			}
			else
			{
				decl String: search_client_name[192];
				Format(search_client_name, sizeof(search_client_name), " %s ", client_name);
				decl String: colored_player_name[192];
				switch (gamemod)
				{
					case Game_ZPS:
						Format(colored_player_name, sizeof(colored_player_name), " \x05%s\x01 ", client_name);
					case Game_GES:
						Format(colored_player_name, sizeof(colored_player_name), " \x05%s\x01 ", client_name);
					default:
						Format(colored_player_name, sizeof(colored_player_name), " \x04%s\x01 ", client_name);
				}
				ReplaceString(client_message, sizeof(client_message), search_client_name, colored_player_name);
			}
		}
	}
	else if (gamemod == Game_FF)
	{
		decl String: client_name[192];
		GetClientName(player_index, client_name, sizeof(client_name));
		
		new team = GetClientTeam(player_index);
		if (team > 1 && team < 6)
		{
			decl String: colored_player_name[192];
			Format(colored_player_name, sizeof(colored_player_name), "^%d%s^0", (team-1), client_name);
			if (ReplaceString(client_message, sizeof(client_message), client_name, colored_player_name) > 0)
			{
				return player_index;
			}
		}
	}
	return color_player_index;
}



color_all_players(String: message[192]) 
{
	new color_index = -1;
	if ((g_bTrackColors4Chat || (gamemod == Game_DODS) || (gamemod == Game_ZPS) || (gamemod == Game_FF) || (gamemod == Game_GES) || (gamemod == Game_CSP)) && (PlayerColorArray != INVALID_HANDLE))
	{
		if (strcmp(message, "") != 0)
		{
			ClearArray(PlayerColorArray);

			new lowest_matching_pos = 192;
			new lowest_matching_pos_client = -1;

			for(new i = 1; i <= MaxClients; i++)
			{
				new client = i;
				if (IsClientInGame(client))
				{
					decl String: client_name[32];
					GetClientName(client, client_name, sizeof(client_name));

					if (strcmp(client_name, "") != 0)
					{
						new message_pos = StrContains(message, client_name);
						if (message_pos > -1)
						{
							if (lowest_matching_pos > message_pos)
							{
								lowest_matching_pos = message_pos;
								lowest_matching_pos_client = client;
							}
							new TempPlayerColorArray[1];
							TempPlayerColorArray[0] = client;
							PushArrayArray(PlayerColorArray, TempPlayerColorArray);
						}
					}
				}
			}
			new size = GetArraySize(PlayerColorArray);
			for (new i = 0; i < size; i++)
			{
				new temp_player_array[1];
				GetArrayArray(PlayerColorArray, i, temp_player_array);
				new temp_client = temp_player_array[0];
				if (temp_client == lowest_matching_pos_client)
				{
					new temp_color_index = color_player(1, temp_client, message);
					color_index = temp_color_index;
				}
				else
				{
					color_player(0, temp_client, message);
				}
			}
			ClearArray(PlayerColorArray);
		}
	}
	
	return color_index;
}



color_team_entities(String:message[192])
{
	switch(gamemod)
	{
		case Game_CSS:
		{
			if (strcmp(message, "") != 0)
			{
				if (ColorSlotArray[2] > -1)
				{
					if (ReplaceString(message, sizeof(message), "TERRORIST ", "\x03Terrorists\x01 ") > 0)
					{
						return ColorSlotArray[2];
					}
				}
				if (ColorSlotArray[3] > -1)
				{
					if (ReplaceString(message, sizeof(message), "CT ", "\x03​Counter-Terrorists\x01 ") > 0)
					{
						return ColorSlotArray[3];
					}
				}
			}
		}
		case Game_L4D:
		{
			if (strcmp(message, "") != 0)
			{
				if (ColorSlotArray[2] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Survivors ", "\x03Survivors\x01 ") > 0)
					{
						return ColorSlotArray[2];
					}
				}
				if (ColorSlotArray[3] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Infected ", "\x03Infected\x01 ") > 0)
					{
						return ColorSlotArray[3];
					}
				}
			}
		}
		case Game_TF:
		{
			if (strcmp(message, "") != 0)
			{
				if (ColorSlotArray[2] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Red ", "\x03Red\x01 ") > 0)
					{
						return ColorSlotArray[2];
					}
				}
				if (ColorSlotArray[3] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Blue ", "\x03Blue\x01 ") > 0)
					{
						return ColorSlotArray[3];
					}
				}
			}
		}
		case Game_FF:
		{
			if (strcmp(message, "") != 0)
			{
				if (ReplaceString(message, sizeof(message), "Red Team", "^2Red Team^0") > 0)
				{
					return 0;
				}
				if (ReplaceString(message, sizeof(message), "Blue Team", "^1Blue Team^0") > 0)
				{
					return 0;
				}
				if (ReplaceString(message, sizeof(message), "Yellow Team", "^3Yellow Team^0") > 0)
				{
					return 0;
				}
				if (ReplaceString(message, sizeof(message), "Green Team", "^4Green Team^0") > 0)
				{
					return 0;
				}
			}
		}
		case Game_AOC:
		{
			if (strcmp(message, "") != 0)
			{
				if (ColorSlotArray[2] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Agathia Knights ", "\x03Agathia Knights\x01 ") > 0)
					{
						return ColorSlotArray[2];
					}
				}
				if (ColorSlotArray[3] > -1)
				{
					if (ReplaceString(message, sizeof(message), "The Mason Order ", "\x03The Mason Order\x01 ") > 0)
					{
						return ColorSlotArray[3];
					}
				}
			}
		}
		case Game_FOF:
		{
			if (strcmp(message, "") != 0)
			{
				if (ColorSlotArray[2] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Desperados ", "\x03Desperados\x01 ") > 0
						|| ReplaceString(message, sizeof(message), "Desparados ", "\x03Desperados\x01 ") > 0)
					{
						return ColorSlotArray[2];
					}
				}
				if (ColorSlotArray[3] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Vigilantes ", "\x03Vigilantes\x01 ") > 0)
					{
						return ColorSlotArray[3];
					}
				}
			}
		}
		case Game_HL2MP:
		{
			if (g_bTeamPlay && strcmp(message, "") != 0)
			{
				if (ColorSlotArray[2] > -1)
				{
					if (ReplaceString(message, sizeof(message), "The Combine ", "\x03The Combine\x01 ") > 0)
					{
						return ColorSlotArray[2];
					}
				}
				if (ColorSlotArray[3] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Rebel Forces ", "\x03Rebel Forces\x01 ") > 0)
					{
						return ColorSlotArray[3];
					}
				}
			}
		}
		case Game_PVKII:
		{
			if (strcmp(message, "") != 0)
			{
				if (ColorSlotArray[2] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Pirates ", "\x03Pirates\x01 ") > 0)
					{
						return ColorSlotArray[2];
					}
				}
				if (ColorSlotArray[3] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Vikings ", "\x03Vikings\x01 ") > 0)
					{
						return ColorSlotArray[3];
					}
				}
				if (ColorSlotArray[4] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Knights ", "\x03Knights\x01 ") > 0)
					{
						return ColorSlotArray[4];
					}
				}
			}
		}
		case Game_ND:
		{
			if (strcmp(message, "") != 0)
			{
				if (ColorSlotArray[2] > -1)
				{
					if (ReplaceString(message, sizeof(message), "EMPIRE ", "\x03Empire\x01 ") > 0)
					{
						return ColorSlotArray[2];
					}
				}
				if (ColorSlotArray[3] > -1)
				{
					if (ReplaceString(message, sizeof(message), "CONSORTIUM ", "\x03Consortium\x01 ") > 0)
					{
						return ColorSlotArray[3];
					}
				}
			}
		}
		case Game_DDD:
		{
			if (g_bTeamPlay && strcmp(message, "") != 0)
			{
				if (ColorSlotArray[2] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Allies ", "\x03Allies\x01 ") > 0)
					{
						return ColorSlotArray[2];
					}
				}
				if (ColorSlotArray[3] > -1)
				{
					if (ReplaceString(message, sizeof(message), "Axis ", "\x03Axis\x01 ") > 0)
					{
						return ColorSlotArray[3];
					}
				}
			}
		}
	}

	return -1;
}


display_menu(player_index, time, String: full_message[1024], need_handler = 0)
{
	ReplaceString(full_message, sizeof(full_message), "\\n", "\10");
	if (need_handler == 0)
	{
		InternalShowMenu(player_index, full_message, time);
	}
	else
	{
		InternalShowMenu(player_index, full_message, time, (1<<0)|(1<<1)|(1<<2)|(1<<3)|(1<<4)|(1<<5)|(1<<6)|(1<<7)|(1<<8)|(1<<9), InternalMenuHandler);
	}
}


public InternalMenuHandler(Handle:menu, MenuAction:action, param1, param2)
{
	new client = param1;
	if (IsClientInGame(client))
	{
		if (action == MenuAction_Select)
		{
			decl String: player_event[192];
			IntToString(param2, player_event, sizeof(player_event));
			LogPlayerEvent(client, "selected", player_event);
		}
		else if (action == MenuAction_Cancel)
		{
			LogPlayerEvent(client, "selected", "cancel");
		}
	}
}


public Action:hlx_sm_psay(args)
{
	if (args < 2)
	{
		PrintToServer("Usage: hlx_sm_psay <userid><colored><message> - sends private message");
		return Plugin_Handled;
	}

	decl String: client_list[192];
	GetCmdArg(1, client_list, sizeof(client_list));
	BuildClientList(client_list);

	decl String: colored_param[32];
	GetCmdArg(2, colored_param, sizeof(colored_param));
	new is_colored = 0;
	new ignore_param = 0;
	
	if (strcmp(colored_param, "1") == 0)
	{
		is_colored = 1;
		ignore_param = 1;
	}
	else if (strcmp(colored_param, "2") == 0)
	{
		is_colored = 2;
		ignore_param = 1;
	}
	else if (strcmp(colored_param, "0") == 0)
	{
		ignore_param = 1;
	}

	new String: client_message[192];
	GetCmdArg((ignore_param + 2), client_message, sizeof(client_message));
	
	if (IsStackEmpty(message_recipients))
	{
		return Plugin_Handled;
	}
	
	new color_index = -1;
	decl String: display_message[192];

	switch (gamemod)
	{
		case Game_CSS, Game_DODS, Game_L4D, Game_TF, Game_HL2MP, Game_ZPS, Game_AOC, Game_FOF, Game_GES, Game_PVKII, Game_CSP, Game_ND, Game_DDD:
		{
			if (is_colored > 0)
			{
				if (is_colored == 1)
				{
					new player_color_index = color_all_players(client_message);
					if (player_color_index > -1)
					{
						color_index = player_color_index;
					}
					else
					{
						if (g_bTrackColors4Chat)
						{
							validate_team_colors();
						}
						color_index = color_team_entities(client_message);
					}
				}
			}
			if (strcmp(message_prefix, "") == 0)
			{
				Format(display_message, sizeof(display_message), "\x01\x0B\x01%s", client_message);
			}
			else
			{
				Format(display_message, sizeof(display_message), "\x01\x0B%c%s\x01 %s", ((gamemod == Game_ZPS || gamemod == Game_GES)?5:4), message_prefix, client_message);
			}
			
			new bool: setupColorForRecipients = false;
			if (color_index == -1)
			{
				setupColorForRecipients = true;
			}
			
			if (g_bTrackColors4Chat && is_colored != 2)
			{
				while (IsStackEmpty(message_recipients) == false)
				{
					new recipient_client = -1;
					PopStackCell(message_recipients, recipient_client);

					new player_index = GetClientOfUserId(recipient_client);
					if (player_index > 0 && !IsFakeClient(player_index) && IsClientInGame(player_index))
					{
						if (setupColorForRecipients == true)
						{
							color_index = player_index;
						}
						new Handle:hBf;
						hBf = StartMessageOne("SayText2", player_index, USERMSG_RELIABLE|USERMSG_BLOCKHOOKS);
						
						if (hBf != INVALID_HANDLE)
						{
							if(GetUserMessageType() == UM_Protobuf) 
							{
								PbSetInt(hBf, "ent_idx", 0);
								PbSetBool(hBf, "chat", false);
								PbSetString(hBf, "msg_name", display_message);
								PbAddString(hBf, "params", "");
								PbAddString(hBf, "params", "");
								PbAddString(hBf, "params", "");
								PbAddString(hBf, "params", "");
							}
							else
							{
								BfWriteByte(hBf, color_index); 
								BfWriteByte(hBf, 0);
								
								BfWriteString(hBf, display_message);
							}
							
							EndMessage();
						}
					}
				}
			}
			else
			{
				PrintToChatRecipients(display_message);
			}
		}
		case Game_FF:
		{
			// thanks to hlstriker for help with this
			
			decl String: client_message_backup[192];
			strcopy(client_message_backup, sizeof(client_message_backup), client_message);
		
			if (is_colored == 1)
			{
				color_index = color_all_players(client_message);
				if (color_index ==  -1)
				{
					color_team_entities(client_message);
				}
			}
			
			if (strcmp(message_prefix, "") == 0)
			{
				Format(display_message, sizeof(display_message), "Console: %s%s\n", ((is_colored == 2)?"^4":""), client_message);
			}
			else
			{
				Format(display_message, sizeof(display_message), "Console: ^4%s:%s %s\n", message_prefix, ((is_colored == 2)?"":"^"), client_message);
			}
			
			PrintToChatRecipientsFF(display_message);
		}
		default:
		{
			if (strcmp(message_prefix, "") != 0)
			{
				Format(display_message, sizeof(display_message), "%s %s", message_prefix, client_message);
				PrintToChatRecipients(display_message);
				return Plugin_Handled;
			}
			PrintToChatRecipients(client_message);
		}
	}
	return Plugin_Handled;
}


public Action:hlx_sm_psay2(args)
{
	if (args < 2)
	{
		PrintToServer("Usage: hlx_sm_psay2 <userid><colored><message> - sends green colored private message");
		return Plugin_Handled;
	}
	
	decl String: client_list[192];
	GetCmdArg(1, client_list, sizeof(client_list));
	BuildClientList(client_list);

	decl String: colored_param[32];
	GetCmdArg(2, colored_param, sizeof(colored_param));
	
	new ignore_param = 0;
	if (strcmp(colored_param, "2") == 0 || strcmp(colored_param, "1") == 0 || strcmp(colored_param, "0") == 0)
	{
		ignore_param = 1;
	}

	new String: client_message[192];
	GetCmdArg((ignore_param + 2), client_message, sizeof(client_message));

	if (IsStackEmpty(message_recipients)) {
		return Plugin_Handled;
	}
	
	// Strip color control codes
	decl String:buffer_message[192];
	new j = 0;
	for (new i = 0; i < sizeof(client_message); i++)
	{
		new sChar = client_message[i];
		if (sChar < 5 && sChar > 0)
		{
			continue;
		}
		buffer_message[j] = client_message[i];
		if (sChar == 0)
		{
			break;
		}
		j++;
	}
	
	switch(gamemod)
	{
		case Game_INSMOD:
		{
			new prefix = 0;
			if (strcmp(message_prefix, "") != 0)
			{
				prefix = 1;
				Format(client_message, sizeof(client_message), "%s: %s", message_prefix, buffer_message);
			}
			
			while (IsStackEmpty(message_recipients) == false)
			{
				new recipient_client = -1;
				PopStackCell(message_recipients, recipient_client);

				new player_index = GetClientOfUserId(recipient_client);
				if (player_index > 0 && !IsFakeClient(player_index) && IsClientInGame(player_index))
				{
					// thanks to Fyren and IceMatrix for help with this
					new Handle:hBf;
					hBf = StartMessageOne("SayText", player_index, USERMSG_RELIABLE|USERMSG_BLOCKHOOKS);
					if (hBf != INVALID_HANDLE)
					{
						if(GetUserMessageType() == UM_Protobuf) 
						{
							PbSetInt(hBf, "ent_idx", player_index);
							PbSetBool(hBf, "chat", true);
							
							if (prefix == 0)
							{
								PbSetString(hBf, "text", buffer_message);
							}
							else
							{
								PbSetString(hBf, "text", client_message);
							}
						}
						else
						{
							BfWriteByte(hBf, 1);
							BfWriteBool(hBf, true);
							BfWriteByte(hBf, player_index); 
							
							if (prefix == 0)
							{
								BfWriteString(hBf, buffer_message);
							}
							else
							{
								BfWriteString(hBf, client_message);
							}
						}
						
						EndMessage();
					}
				}
			}
		}
		case Game_FF:
		{
			if (strcmp(message_prefix, "") == 0)
			{
				Format(client_message, sizeof(client_message), "Console: \x02^4%s\n", buffer_message);
			}
			else
			{
				Format(client_message, sizeof(client_message), "Console: \x02^4%s: %s\n", message_prefix, buffer_message);
			}
			
			PrintToChatRecipientsFF(client_message);
		}
		case Game_ZPS, Game_GES:
		{
			if (strcmp(message_prefix, "") == 0)
			{
				Format(client_message, sizeof(client_message), "\x05%s", buffer_message);
			}
			else
			{
				Format(client_message, sizeof(client_message), "\x05%s %s", message_prefix, buffer_message);
			}
			PrintToChatRecipients(client_message);
		}
		default:
		{
			if (strcmp(message_prefix, "") == 0)
			{
				Format(client_message, sizeof(client_message), "\x04%s", buffer_message);
			}
			else
			{
				Format(client_message, sizeof(client_message), "\x04%s %s", message_prefix, buffer_message);
			}
			PrintToChatRecipients(client_message);
		}
	}
	return Plugin_Handled;
}


public Action:hlx_sm_csay(args)
{
	if (args < 1)
	{
		PrintToServer("Usage: hlx_sm_csay <message> - display center message");
		return Plugin_Handled;
	}

	new String: display_message[192];
	GetCmdArg(1, display_message, sizeof(display_message));

	if (strcmp(display_message, "") != 0)
	{
		if (gamemod == Game_L4D)
		{
			PrintToChatAll("\x03%s", display_message);
		}
		else
		{
			PrintCenterTextAll("%s", display_message);
		}
	}
		
	return Plugin_Handled;
}


public Action:hlx_sm_msay(args)
{
	if (args < 3)
	{
		PrintToServer("Usage: hlx_sm_msay <time><userid><message> - sends hud message");
		return Plugin_Handled;
	}

	if (gamemod == Game_HL2MP)
	{
		return Plugin_Handled;
	}
	
	decl String: display_time[16];
	GetCmdArg(1, display_time, sizeof(display_time));
	
	decl String: client_id[32];
	GetCmdArg(2, client_id, sizeof(client_id));
	
	decl String: handler_param[32];
	GetCmdArg(3, handler_param, sizeof(handler_param));
	
	new ignore_param = 0;
	new need_handler = 0;
	if (handler_param[1] == 0 && (handler_param[0] == '1' || handler_param[0] == '0'))
	{
		need_handler = 1;
		ignore_param = 1;
	}

	new String: client_message[1024];
	GetCmdArg((ignore_param + 3), client_message, 1024);

	new time = StringToInt(display_time);
	if (time <= 0)
	{
		time = 10;
	}

	new client = StringToInt(client_id);
	if (client > 0)
	{
		new player_index = GetClientOfUserId(client);
		if (player_index > 0 && !IsFakeClient(player_index) && IsClientInGame(player_index) && strcmp(client_message, "") != 0)
		{
			display_menu(player_index, time, client_message, need_handler);
		}	
	}
	
	return Plugin_Handled;
}


public Action:hlx_sm_tsay(args)
{
	if (args < 3)
	{
		PrintToServer("Usage: hlx_sm_tsay <time><userid><message> - sends hud message");
		return Plugin_Handled;
	}

	decl String: display_time[16];
	GetCmdArg(1, display_time, sizeof(display_time));
	
	decl String: client_id[32];
	GetCmdArg(2, client_id, sizeof(client_id));

	new String: client_message[192];
	GetCmdArg(3, client_message, sizeof(client_message));
	
	new client = StringToInt(client_id);
	if ((client > 0) && (strcmp(client_message, "") != 0))
	{
		new player_index = GetClientOfUserId(client);
		if (player_index > 0 && !IsFakeClient(player_index) && IsClientInGame(player_index))
		{
			new Handle:values = CreateKeyValues("msg");
			KvSetString(values, "title", client_message);
			KvSetNum(values, "level", 1); 
			KvSetString(values, "time", display_time); 
			CreateDialog(player_index, values, DialogType_Msg);
			CloseHandle(values);
		}	
	}
		
	return Plugin_Handled;
}


public Action:hlx_sm_hint(args)
{
	if (args < 2)
	{
		PrintToServer("Usage: hlx_sm_hint <userid><message> - send hint message");
		return Plugin_Handled;
	}

	decl String: client_list[192];
	GetCmdArg(1, client_list, sizeof(client_list));
	BuildClientList(client_list);

	new String: client_message[192];
	GetCmdArg(2, client_message, sizeof(client_message));

	if (IsStackEmpty(message_recipients) == false && strcmp(client_message, "") != 0)
	{
		while (IsStackEmpty(message_recipients) == false)
		{
			new recipient_client = -1;
			PopStackCell(message_recipients, recipient_client);
		
			new player_index = GetClientOfUserId(recipient_client);
			if (player_index > 0 && !IsFakeClient(player_index) && IsClientInGame(player_index) && IsClientInGame(player_index))
			{
				PrintHintText(player_index, "%s", client_message);
			}
		}
	}
	return Plugin_Handled;
}


public Action:hlx_sm_browse(args)
{
	if (args < 2)
	{
		PrintToServer("Usage: hlx_sm_browse <userid><url> - open client ingame browser");
		return Plugin_Handled;
	}

	decl String: client_list[192];
	GetCmdArg(1, client_list, sizeof(client_list));
	BuildClientList(client_list);

	new String: client_url[192];
	GetCmdArg(2, client_url, sizeof(client_url));

	if (IsStackEmpty(message_recipients) == false && strcmp(client_url, "") != 0)
	{
		while (IsStackEmpty(message_recipients) == false)
		{
			new recipient_client = -1;
			PopStackCell(message_recipients, recipient_client);

			new player_index = GetClientOfUserId(recipient_client);
			if (player_index > 0 && !IsFakeClient(player_index) && IsClientInGame(player_index))
			{
				if (g_bGameCanDoMotd)
				{
					if (g_bPlyrCanDoMotd[player_index])
					{
						if (GetUserMessageType() == UM_Protobuf)
						{
							decl String:typeStr[5];
							IntToString(MOTDPANEL_TYPE_URL, typeStr, 4);
							
							new Handle:pb = StartMessageOne("VGUIMenu", player_index);
							
							PbSetString(pb, "name", "info");
							PbSetBool(pb, "show", true);

							new Handle:modkey = PbAddMessage(pb, "subkeys");
							
							PbSetString(modkey, "name", "type");
							PbSetString(modkey, "str", typeStr); 

							modkey = PbAddMessage(pb, "subkeys");
							PbSetString(modkey, "name", "title");
							PbSetString(modkey, "str", "HLstatsX:CE");

							modkey = PbAddMessage(pb, "subkeys");
							PbSetString(modkey, "name", "msg");
							PbSetString(modkey, "str", client_url);

							EndMessage();
						}
						else
						{
							ShowMOTDPanel(player_index, "HLstatsX:CE", client_url, MOTDPANEL_TYPE_URL);
						}
						
						ShowMOTDPanel(player_index, "HLstatsX:CE", client_url, MOTDPANEL_TYPE_URL);
					}
					else
					{
						PrintToChat(player_index, "HTML MOTD needs to be enabled in your game options to use this command");
					}
				}
				else
				{
					PrintToChat(player_index, "This game does not support the HTML MOTD window required for this command");
				}
			}
		}
	}
			
	return Plugin_Handled;
}


public Action:hlx_sm_swap(args)
{
	if (args < 1)
	{
		PrintToServer("Usage: hlx_sm_swap <userid> - swaps players to the opposite team (css only)");
		return Plugin_Handled;
	}

	if (gamemod != Game_CSS)
	{
		PrintToServer("hlx_sm_swap is not supported by this game.");
		return Plugin_Handled;
	}
	
	decl String:client_id[32];
	GetCmdArg(1, client_id, sizeof(client_id));

	new client = StringToInt(client_id);
	if (client > 0)
	{
		new player_index = GetClientOfUserId(client);
		if (player_index > 0 && IsClientInGame(player_index))
		{
			swap_player(player_index);
		}
	}
	return Plugin_Handled;
}


public Action sm_swap(int admin, int args)
{
    if (args < 1)
    {
        ReplyToCommand(admin, "Usage: sm_swap <userid/name> - swaps players to the opposite team (CSS only)");
        return Plugin_Handled;
    }

    if (gamemod != Game_CSS)
    {
        ReplyToCommand(admin, "sm_swap is not supported by this game.");
        return Plugin_Handled;
    }

    decl String:input[32];
    GetCmdArg(1, input, sizeof(input));

    new target_client = -1;
    new matches = 0;

    // Attempt to match by user ID
    new user_id = StringToInt(input);
    if (user_id > 0)
    {
        target_client = GetClientOfUserId(user_id);
        if (target_client > 0 && IsClientInGame(target_client))
        {
            if (GetClientTeam(target_client) != CS_TEAM_T && GetClientTeam(target_client) != CS_TEAM_CT)
            {
                ReplyToCommand(admin, "[HLstatsX] Target must be on a playable team.");
                return Plugin_Handled;
            }
            swap_player_admin(target_client);
            ReplyToCommand(admin, "[HLstatsX] Successfully swapped player with ID.");
            return Plugin_Handled;
        }
    }

    // If not found by ID, search by partial name
    for (new i = 1; i <= MaxClients; i++)
    {
        if (IsClientInGame(i) && !IsFakeClient(i))
        {
            decl String:client_name[64];
            GetClientName(i, client_name, sizeof(client_name));

            if (StrContains(client_name, input, false) != -1)
            {
                target_client = i;
                matches++;
            }
        }
    }

    if (matches == 1)
    {
        if (GetClientTeam(target_client) != CS_TEAM_T && GetClientTeam(target_client) != CS_TEAM_CT)
        {
            ReplyToCommand(admin, "[HLstatsX] Target must be on a playable team.");
            return Plugin_Handled;
        }
        swap_player_admin(target_client);
        ReplyToCommand(admin, "[HLstatsX] Successfully swapped player by name.");
    }
    else if (matches > 1)
    {
        ReplyToCommand(admin, "[HLstatsX] Multiple players match that name. Please be more specific.");
    }
    else
    {
        ReplyToCommand(admin, "No matching players found.");
    }

    return Plugin_Handled;
}


public Action:hlx_sm_redirect(args)
{
	if (args < 3)
	{
		PrintToServer("Usage: hlx_sm_redirect <time><userid><address><reason> - asks player to be redirected to specified gameserver");
		return Plugin_Handled;
	}

	decl String: display_time[16];
	GetCmdArg(1, display_time, sizeof(display_time));

	decl String: client_list[192];
	GetCmdArg(2, client_list, sizeof(client_list));
	BuildClientList(client_list);
	
	new String: server_address[192];
	GetCmdArg(3, server_address, sizeof(server_address));

	new String: redirect_reason[192];
	GetCmdArg(4, redirect_reason, sizeof(redirect_reason));

	if (IsStackEmpty(message_recipients) == false && strcmp(server_address, "") != 0)
	{
		while (IsStackEmpty(message_recipients) == false)
		{
			new recipient_client = -1;
			PopStackCell(message_recipients, recipient_client);

			new player_index = GetClientOfUserId(recipient_client);
			if (player_index > 0 && !IsFakeClient(player_index) && IsClientInGame(player_index))
			{
				new Handle:top_values = CreateKeyValues("msg");
				KvSetString(top_values, "title", redirect_reason);
				KvSetNum(top_values, "level", 1); 
				KvSetString(top_values, "time", display_time); 
				CreateDialog(player_index, top_values, DialogType_Msg);
				CloseHandle(top_values);
		
				new Float: display_time_float;
				display_time_float = StringToFloat(display_time);
				DisplayAskConnectBox(player_index, display_time_float, server_address);
			}
		}
	}
		
	return Plugin_Handled;
}



public Action:hlx_sm_player_action(args)
{
	if (args < 2)
	{
		PrintToServer("Usage: hlx_sm_player_action <clientid><action> - trigger player action to be handled from HLstatsX");
		return Plugin_Handled;
	}

	decl String: client_id[32];
	GetCmdArg(1, client_id, sizeof(client_id));

	decl String: player_action[64];
	GetCmdArg(2, player_action, sizeof(player_action));

	new client = StringToInt(client_id);

	LogPlayerEvent(client, "triggered", player_action);

	return Plugin_Handled;
}


public Action:hlx_sm_team_action(args)
{
	if (args < 2)
	{
		PrintToServer("Usage: hlx_sm_team_action <team_name><action> - trigger team action to be handled from HLstatsX");
		return Plugin_Handled;
	}

	decl String: team_name[64];
	GetCmdArg(1, team_name, sizeof(team_name));

	decl String: team_action[64];
	GetCmdArg(2, team_action, sizeof(team_action));

	LogToGame("Team \"%s\" triggered \"%s\"", team_name, team_action); 

	return Plugin_Handled;
}


public Action:hlx_sm_world_action(args)
{
	if (args < 1)
	{
		PrintToServer("Usage: hlx_sm_world_action <action> - trigger world action to be handled from HLstatsX");
		return Plugin_Handled;
	}

	decl String: world_action[64];
	GetCmdArg(1, world_action, sizeof(world_action));

	LogToGame("World triggered \"%s\"", world_action); 

	return Plugin_Handled;
}


is_command_blocked(String: command[])
{
	new command_blocked = 0;
	new command_index = 0;
	while ((command_blocked == 0) && (command_index < sizeof(blocked_commands)))
	{
		if (strcmp(command, blocked_commands[command_index]) == 0)
		{
			command_blocked++;
		}
		command_index++;
	}
	if (command_blocked > 0)
	{
		return 1;
	}
	return 0;
}


public Action:hlx_block_commands(client, const String:command[], args)
{
	if (client)
	{
		if (client == 0)
		{
			return Plugin_Continue;
		}
		new block_chat_commands = GetConVarInt(hlx_block_chat_commands);

		decl String: user_command[192];
		GetCmdArgString(user_command, sizeof(user_command));

		decl String: origin_command[192];
		new start_index = 0;
		new command_length = strlen(user_command);
		if (command_length > 0)
		{
			if (user_command[0] == 34)
			{
				start_index = 1;
				if (user_command[command_length - 1] == 34)
				{
					user_command[command_length - 1] = 0;
				}
			}
			strcopy(origin_command, sizeof(origin_command), user_command[start_index]);
			
			if (user_command[start_index] == 47)
			{
				start_index++;
			}
		}

		new String: command_type[32] = "say";

		if (gamemod == Game_INSMOD)
		{
			decl String: say_type[1];
			strcopy(say_type, 2, user_command[start_index]);
			if (strcmp(say_type, "1") == 0)
			{
				command_type = "say";
			}
			else if (strcmp(say_type, "2") == 0)
			{
				command_type = "say_team";
			}
			start_index += 4;
		}

		if (command_length > 0)
		{
			if (block_chat_commands > 0)
			{
				new command_blocked = is_command_blocked(user_command[start_index]);
				if (command_blocked > 0)
				{
					if (IsClientInGame(client))
					{
						if ((strcmp("hlx_menu", user_command[start_index]) == 0) ||
							(strcmp("hlx", user_command[start_index]) == 0) ||
							(strcmp("hlstatsx", user_command[start_index]) == 0))
						{
							DisplayMenu(HLstatsXMenuMain, client, MENU_TIME_FOREVER);
						}

						if (gamemod == Game_INSMOD)
						{
							LogPlayerEvent(client, command_type, user_command[start_index]);
						}
						else
						{
							LogPlayerEvent(client, command_type, origin_command);
						}
					}
					return Plugin_Stop;
				}
			}
			else
			{
				if (IsClientInGame(client) &&
					(strcmp("hlx_menu", user_command[start_index]) == 0
					|| strcmp("hlx", user_command[start_index]) == 0
					|| strcmp("hlstatsx", user_command[start_index]) == 0))
				{
					DisplayMenu(HLstatsXMenuMain, client, MENU_TIME_FOREVER);
				}
				
				return Plugin_Continue;
			}
		}
		
	}
	return Plugin_Continue;
}


public Action: HLstatsX_Event_PlyTeamChange(Handle:event, const String:name[], bool:dontBroadcast)
{
	new client = GetClientOfUserId(GetEventInt(event, "userid"));
	if (client > 0)
	{
		for (new i = 0; (i < sizeof(ColorSlotArray)); i++)
		{
			new color_client = ColorSlotArray[i];
			if (color_client > -1)
			{
				if (color_client == client)
				{
					ColorSlotArray[i] = -1;
				}
			}
		}
	}

	if (g_bDominationDebug && client > 0 && IsClientInGame(client))
	{
		decl String:clientName[64];
		GetClientName(client, clientName, sizeof(clientName));
		LogToFileEx(g_sDominationDebugLog, "player_team userid=%d client=%N name=%s oldteam=%d team=%d disconnect=%d silent=%d", GetClientUserId(client), client, clientName, GetEventInt(event, "oldteam"), GetEventInt(event, "team"), GetEventBool(event, "disconnect"), GetEventBool(event, "silent"));
	}

	return Plugin_Continue;
}

stock void HLX_DebugLogDomPair(const char[] tag, int client, int other)
{
	if (!g_bDominationDebug)
	{
		return;
	}

	if (!IsClientInGame(client) || !IsClientInGame(other))
	{
		LogToFileEx(g_sDominationDebugLog, "%s invalid_pair client=%d other=%d", tag, client, other);
		return;
	}

	new dominated = -1;
	new dominatingMe = -1;
	if (HasEntProp(client, Prop_Send, "m_bPlayerDominated"))
	{
		dominated = GetEntProp(client, Prop_Send, "m_bPlayerDominated", 1, other);
	}
	if (HasEntProp(client, Prop_Send, "m_bPlayerDominatingMe"))
	{
		dominatingMe = GetEntProp(client, Prop_Send, "m_bPlayerDominatingMe", 1, other);
	}

	LogToFileEx(g_sDominationDebugLog, "%s client=%N userid=%d team=%d other=%N other_userid=%d other_team=%d dominated=%d dominatingMe=%d", tag, client, GetClientUserId(client), GetClientTeam(client), other, GetClientUserId(other), GetClientTeam(other), dominated, dominatingMe);
}

stock void HLX_DebugLogDomState(const char[] tag, int client)
{
	if (!g_bDominationDebug || !IsClientInGame(client))
	{
		return;
	}

	for (new other = 1; other <= MaxClients; other++)
	{
		if (other == client || !IsClientInGame(other))
		{
			continue;
		}
		HLX_DebugLogDomPair(tag, client, other);
	}
}

public Action:Command_HLX_DebugDomOn(args)
{
	decl String:mapName[64];
	GetCurrentMap(mapName, sizeof(mapName));
	g_bDominationDebug = true;
	LogToFileEx(g_sDominationDebugLog, "debug_on map=%s", mapName);
	PrintToServer("HLX domination debug enabled: %s", g_sDominationDebugLog);
	return Plugin_Handled;
}

public Action:Command_HLX_DebugDomOff(args)
{
	LogToFileEx(g_sDominationDebugLog, "debug_off");
	g_bDominationDebug = false;
	PrintToServer("HLX domination debug disabled");
	return Plugin_Handled;
}

public Action:Command_HLX_DebugDomDump(args)
{
	if (args < 2)
	{
		PrintToServer("Usage: hlx_debug_dom_dump <userid1> <userid2>");
		return Plugin_Handled;
	}

	decl String:arg1[16], String:arg2[16];
	GetCmdArg(1, arg1, sizeof(arg1));
	GetCmdArg(2, arg2, sizeof(arg2));

	new client = GetClientOfUserId(StringToInt(arg1));
	new other = GetClientOfUserId(StringToInt(arg2));
	HLX_DebugLogDomPair("manual_dump", client, other);
	HLX_DebugLogDomPair("manual_dump_reverse", other, client);
	return Plugin_Handled;
}

public Action:Command_HLX_DebugDomPair(args)
{
	if (args < 2)
	{
		PrintToServer("Usage: hlx_debug_dom_pair <dominator_userid> <victim_userid>");
		return Plugin_Handled;
	}

	decl String:arg1[16], String:arg2[16];
	GetCmdArg(1, arg1, sizeof(arg1));
	GetCmdArg(2, arg2, sizeof(arg2));

	new dominator = GetClientOfUserId(StringToInt(arg1));
	new victim = GetClientOfUserId(StringToInt(arg2));
	if (dominator <= 0 || victim <= 0 || !IsClientInGame(dominator) || !IsClientInGame(victim))
	{
		PrintToServer("HLX debug domination pair failed: invalid clients");
		return Plugin_Handled;
	}

	if (HasEntProp(dominator, Prop_Send, "m_bPlayerDominated"))
	{
		SetEntProp(dominator, Prop_Send, "m_bPlayerDominated", 1, 1, victim);
	}
	if (HasEntProp(victim, Prop_Send, "m_bPlayerDominatingMe"))
	{
		SetEntProp(victim, Prop_Send, "m_bPlayerDominatingMe", 1, 1, dominator);
	}

	HLX_DebugLogDomPair("forced_pair", dominator, victim);
	HLX_DebugLogDomPair("forced_pair_reverse", victim, dominator);
	PrintToServer("HLX debug domination pair set: dominator=%N victim=%N", dominator, victim);
	return Plugin_Handled;
}

stock int HLX_DebugFindFirstClientOnTeam(team)
{
	for (new i = 1; i <= MaxClients; i++)
	{
		if (IsClientInGame(i) && IsFakeClient(i) && GetClientTeam(i) == team)
		{
			return i;
		}
	}

	for (new i = 1; i <= MaxClients; i++)
	{
		if (IsClientInGame(i) && GetClientTeam(i) == team)
		{
			return i;
		}
	}

	return 0;
}

public Action:Timer_HLX_DebugAutoReproPrepare(Handle:timer)
{
	if (!g_bDominationAutoRepro)
	{
		return Plugin_Stop;
	}

	LogToFileEx(g_sDominationDebugLog, "autorepro_prepare");
	ServerCommand("bot_kick");
	ServerCommand("bot_add_t");
	ServerCommand("bot_add_ct");
	ServerCommand("bot_add_t");
	ServerCommand("bot_add_ct");
	ServerExecute();
	CreateTimer(4.0, Timer_HLX_DebugAutoReproRun, _, TIMER_FLAG_NO_MAPCHANGE);
	return Plugin_Stop;
}

public Action:Timer_HLX_DebugAutoReproRun(Handle:timer)
{
	if (!g_bDominationAutoRepro)
	{
		return Plugin_Stop;
	}

	new dominator = HLX_DebugFindFirstClientOnTeam(CS_TEAM_CT);
	new victim = HLX_DebugFindFirstClientOnTeam(CS_TEAM_T);
	if (dominator <= 0 || victim <= 0)
	{
		LogToFileEx(g_sDominationDebugLog, "autorepro_blocked missing_players dominator=%d victim=%d", dominator, victim);
		CreateTimer(4.0, Timer_HLX_DebugAutoReproRun, _, TIMER_FLAG_NO_MAPCHANGE);
		return Plugin_Stop;
	}

	LogToFileEx(g_sDominationDebugLog, "autorepro_selected dominator=%N dominator_userid=%d victim=%N victim_userid=%d", dominator, GetClientUserId(dominator), victim, GetClientUserId(victim));
	if (HasEntProp(dominator, Prop_Send, "m_bPlayerDominated"))
	{
		SetEntProp(dominator, Prop_Send, "m_bPlayerDominated", 1, 1, victim);
	}
	if (HasEntProp(victim, Prop_Send, "m_bPlayerDominatingMe"))
	{
		SetEntProp(victim, Prop_Send, "m_bPlayerDominatingMe", 1, 1, dominator);
	}

	HLX_DebugLogDomPair("autorepro_before_swap", dominator, victim);
	HLX_DebugLogDomPair("autorepro_before_swap_reverse", victim, dominator);
	swap_player(dominator);
	HLX_DebugLogDomPair("autorepro_after_swap", dominator, victim);
	HLX_DebugLogDomPair("autorepro_after_swap_reverse", victim, dominator);
	g_bDominationAutoRepro = false;
	return Plugin_Stop;
}

void ResetDominationStateForClient(int client)
{
    if (!IsClientInGame(client))
    {
        return;
    }

    bool hasDominated = HasEntProp(client, Prop_Send, "m_bPlayerDominated");
    bool hasDominatingMe = HasEntProp(client, Prop_Send, "m_bPlayerDominatingMe");

    if (!hasDominated && !hasDominatingMe)
    {
        return;
    }

    if (g_bDominationDebug)
    {
        HLX_DebugLogDomState("before_reset", client);
    }

    for (int other = 1; other <= MaxClients; other++)
    {
        if (other == client || !IsClientInGame(other))
        {
            continue;
        }

        if (hasDominated)
        {
            SetEntProp(client, Prop_Send, "m_bPlayerDominated", 0, 1, other);
        }

        if (hasDominatingMe)
        {
            SetEntProp(client, Prop_Send, "m_bPlayerDominatingMe", 0, 1, other);
        }

        if (HasEntProp(other, Prop_Send, "m_bPlayerDominated"))
        {
            SetEntProp(other, Prop_Send, "m_bPlayerDominated", 0, 1, client);
        }

        if (HasEntProp(other, Prop_Send, "m_bPlayerDominatingMe"))
        {
            SetEntProp(other, Prop_Send, "m_bPlayerDominatingMe", 0, 1, client);
        }
    }

    if (g_bDominationDebug)
    {
        HLX_DebugLogDomState("after_reset", client);
    }
}

bool GiveC4ToTOrSpawn()
{
    // Prefer giving C4 to a living T player.
    for (int i = 1; i <= MaxClients; i++)
    {
        if (IsClientInGame(i) && IsPlayerAlive(i) && GetClientTeam(i) == CS_TEAM_T)
        {
            int given = GivePlayerItem(i, "weapon_c4");
            return (given > 0);
        }
    }

    // No living T found: spawn C4 at a T spawn point.
    int spawn = -1;
    float origin[3];
    float angles[3];

    // Try to find a random info_player_terrorist
    int count = 0;
    int ent = -1;
    while ((ent = FindEntityByClassname(ent, "info_player_terrorist")) != -1)
    {
        count++;
        if (GetURandomInt() % count == 0)
        {
            spawn = ent; // reservoir sampling to pick one
        }
    }

    if (spawn != -1)
    {
        GetEntPropVector(spawn, Prop_Send, "m_vecOrigin", origin);
        GetEntPropVector(spawn, Prop_Send, "m_angRotation", angles);
    }
    else
    {
        // Fallback: center of map if no T spawns found.
        origin[0] = origin[1] = origin[2] = 0.0;
        angles[0] = angles[1] = angles[2] = 0.0;
    }

    int c4 = CreateEntityByName("weapon_c4");
    if (c4 <= 0) return false;

    DispatchSpawn(c4);
    TeleportEntity(c4, origin, angles, NULL_VECTOR);
    return true;
}

swap_player(player_index)
{
    swap_player_internal(player_index, false);
}

swap_player_admin(player_index)
{
    swap_player_internal(player_index, true);
}

swap_player_internal(player_index, bool:allow_alive)
{
    if (!IsClientInGame(player_index))
    {
        return;
    }

    bool was_alive = IsPlayerAlive(player_index);
    if (was_alive && !allow_alive)
    {
        PrintToServer("[HLstatsX] Refusing team swap: target is alive.");
        if (g_bDominationDebug)
        {
            LogToFileEx(g_sDominationDebugLog, "swap_player_refused_alive client=%N userid=%d team=%d", player_index, GetClientUserId(player_index), GetClientTeam(player_index));
        }
        return;
    }

    if (g_bDominationDebug)
    {
        LogToFileEx(g_sDominationDebugLog, "swap_player_start client=%N userid=%d team=%d alive=%d", player_index, GetClientUserId(player_index), GetClientTeam(player_index), IsPlayerAlive(player_index));
    }

    int team = GetClientTeam(player_index);

    // Detect if player currently holds C4 (slot 4, classname weapon_c4).
    bool hadC4 = false;
    int c4 = GetPlayerWeaponSlot(player_index, 4);
    if (c4 > 0)
    {
        char cls[32];
        GetEdictClassname(c4, cls, sizeof(cls));
        if (StrEqual(cls, "weapon_c4", false))
        {
            hadC4 = true;

            // Remove from player and kill the entity so it cannot drop at CT spawn.
            RemovePlayerItem(player_index, c4);
            AcceptEntityInput(c4, "Kill");
        }
    }

    switch (team)
    {
        case CS_TEAM_CT:
        {
            fade_msg = StartMessageOne("Fade", player_index);
            BfWriteShort(fade_msg, 500);
            BfWriteShort(fade_msg, 0);        // duration
            BfWriteShort(fade_msg, 0x0001);   // type
            BfWriteByte(fade_msg, 255);       // red
            BfWriteByte(fade_msg, 0);         // green
            BfWriteByte(fade_msg, 0);         // blue
            BfWriteByte(fade_msg, 255);       // alpha
            EndMessage();

            EmitSoundToClient(player_index, "common/warning.wav");
            PrintCenterText(player_index, "[HlstatsX] You were switched to balance team! You are now T.");

            CS_SwitchTeam(player_index, CS_TEAM_T);
            if (allow_alive && was_alive)
            {
                CS_RespawnPlayer(player_index);
            }

            ResetDominationStateForClient(player_index);
            if (g_bDominationDebug)
            {
                LogToFileEx(g_sDominationDebugLog, "swap_player_done client=%N userid=%d oldteam=%d newteam=%d", player_index, GetClientUserId(player_index), CS_TEAM_CT, GetClientTeam(player_index));
            }
        }

        case CS_TEAM_T:
        {
            fade_msg = StartMessageOne("Fade", player_index);
            BfWriteShort(fade_msg, 500);
            BfWriteShort(fade_msg, 0);        // duration
            BfWriteShort(fade_msg, 0x0001);   // type
            BfWriteByte(fade_msg, 0);         // red
            BfWriteByte(fade_msg, 0);         // green
            BfWriteByte(fade_msg, 255);       // blue
            BfWriteByte(fade_msg, 255);       // alpha
            EndMessage();

            EmitSoundToClient(player_index, "common/warning.wav");
            PrintCenterText(player_index, "[HlstatsX] You were switched to balance team! You are now CT.");

            CS_SwitchTeam(player_index, CS_TEAM_CT);
            if (allow_alive && was_alive)
            {
                CS_RespawnPlayer(player_index);
            }

            // Even if the player is dead, ensure bomb is reassigned properly.
            if (hadC4)
            {
                GiveC4ToTOrSpawn();
            }

            ResetDominationStateForClient(player_index);
            if (g_bDominationDebug)
            {
                LogToFileEx(g_sDominationDebugLog, "swap_player_done client=%N userid=%d oldteam=%d newteam=%d", player_index, GetClientUserId(player_index), CS_TEAM_T, GetClientTeam(player_index));
            }
        }
    }
}

public CreateHLstatsXMenuMain(&Handle: MenuHandle)
{
	MenuHandle = CreateMenu(HLstatsXMainCommandHandler, MenuAction_Select|MenuAction_Cancel);

	if (!g_bGameCanDoMotd)
	{
		SetMenuTitle(MenuHandle, "HLstatsX - Main Menu");
		AddMenuItem(MenuHandle, "", "Display Rank");
		AddMenuItem(MenuHandle, "", "Next Players");
		AddMenuItem(MenuHandle, "", "Top10 Players");
		AddMenuItem(MenuHandle, "", "Auto Ranking");
		AddMenuItem(MenuHandle, "", "Toggle Point Msgs");
		AddMenuItem(MenuHandle, "", "Toggle Ranking Display");
	}
	else
	{
		SetMenuTitle(MenuHandle, "HLstatsX - Main Menu");
		AddMenuItem(MenuHandle, "", "Display Rank");
		AddMenuItem(MenuHandle, "", "Next Players");
		AddMenuItem(MenuHandle, "", "Top10 Players");
		AddMenuItem(MenuHandle, "", "Clans Ranking");
		AddMenuItem(MenuHandle, "", "Server Status");
		AddMenuItem(MenuHandle, "", "Statsme");
		AddMenuItem(MenuHandle, "", "Auto Ranking");
		AddMenuItem(MenuHandle, "", "Toggle Point Msgs");
		AddMenuItem(MenuHandle, "", "Weapon Usage");
		AddMenuItem(MenuHandle, "", "Weapons Accuracy");
		AddMenuItem(MenuHandle, "", "Weapons Targets");
		AddMenuItem(MenuHandle, "", "Player Kills");
		AddMenuItem(MenuHandle, "", "Toggle Ranking Display");
		AddMenuItem(MenuHandle, "", "Ban and Cheater List");
		AddMenuItem(MenuHandle, "", "Display Help");
	}

	SetMenuPagination(MenuHandle, 8);
}


public CreateHLstatsXMenuAuto(&Handle: MenuHandle)
{
	MenuHandle = CreateMenu(HLstatsXAutoCommandHandler, MenuAction_Select|MenuAction_Cancel);

	SetMenuTitle(MenuHandle, "HLstatsX - Auto-Ranking");
	AddMenuItem(MenuHandle, "", "Enable on round-start");
	AddMenuItem(MenuHandle, "", "Enable on round-end");
	AddMenuItem(MenuHandle, "", "Enable on player death");
	AddMenuItem(MenuHandle, "", "Disable");

	SetMenuPagination(MenuHandle, 8);
}


public CreateHLstatsXMenuEvents(&Handle: MenuHandle)
{
	MenuHandle = CreateMenu(HLstatsXEventsCommandHandler, MenuAction_Select|MenuAction_Cancel);

	SetMenuTitle(MenuHandle, "HLstatsX - Console Events");
	AddMenuItem(MenuHandle, "", "Enable Events");
	AddMenuItem(MenuHandle, "", "Disable Events");
	AddMenuItem(MenuHandle, "", "Enable Global Chat");
	AddMenuItem(MenuHandle, "", "Disable Global Chat");

	SetMenuPagination(MenuHandle, 8);
}


make_player_command(client, String: player_command[192]) 
{
	LogPlayerEvent(client, "say", player_command);
}


public HLstatsXMainCommandHandler(Handle:menu, MenuAction:action, param1, param2)
{
	if (action == MenuAction_Select)
	{
		if (IsClientInGame(param1))
		{
			if (!g_bGameCanDoMotd)
			{
				switch (param2)
				{
					case 0 : 
						make_player_command(param1, "/rank");
					case 1 : 
						make_player_command(param1, "/next");
					case 2 : 
						make_player_command(param1, "/top10");
					case 3 : 
						DisplayMenu(HLstatsXMenuAuto, param1, MENU_TIME_FOREVER);
					case 4 : 
						DisplayMenu(HLstatsXMenuEvents, param1, MENU_TIME_FOREVER);
					case 5 : 
						make_player_command(param1, "/hlx_hideranking");
				}
			}
			else
			{
				switch (param2)
				{
					case 0 : 
						make_player_command(param1, "/rank");
					case 1 : 
						make_player_command(param1, "/next");
					case 2 : 
						make_player_command(param1, "/top10");
					case 3 : 
						make_player_command(param1, "/clans");
					case 4 : 
						make_player_command(param1, "/status");
					case 5 : 
						make_player_command(param1, "/statsme");
					case 6 : 
						DisplayMenu(HLstatsXMenuAuto, param1, MENU_TIME_FOREVER);
					case 7 : 
						DisplayMenu(HLstatsXMenuEvents, param1, MENU_TIME_FOREVER);
					case 8 : 
						make_player_command(param1, "/weapons");
					case 9 : 
						make_player_command(param1, "/accuracy");
					case 10 : 
						make_player_command(param1, "/targets");
					case 11 : 
						make_player_command(param1, "/kills");
					case 12 : 
						make_player_command(param1, "/hlx_hideranking");
					case 13 : 
						make_player_command(param1, "/bans");
					case 14 : 
						make_player_command(param1, "/help");
				}
			}
		}
	}
}


public HLstatsXAutoCommandHandler(Handle:menu, MenuAction:action, param1, param2)
{
	if (action == MenuAction_Select)
	{
		if (IsClientInGame(param1))
		{
			switch (param2)
			{
				case 0 : 
					make_player_command(param1, "/hlx_auto start rank");
				case 1 : 
					make_player_command(param1, "/hlx_auto end rank");
				case 2 : 
					make_player_command(param1, "/hlx_auto kill rank");
				case 3 : 
					make_player_command(param1, "/hlx_auto clear");
			}
		}
	}
}


public HLstatsXEventsCommandHandler(Handle:menu, MenuAction:action, param1, param2)
{
	if (action == MenuAction_Select)
	{
		if (IsClientInGame(param1))
		{
			switch (param2)
			{
				case 0 : 
					make_player_command(param1, "/hlx_display 1");
				case 1 : 
					make_player_command(param1, "/hlx_display 0");
				case 2 : 
					make_player_command(param1, "/hlx_chat 1");
				case 3 : 
					make_player_command(param1, "/hlx_chat 0");
			}
		}
	}
}

stock BuildClientList(const String:client_list[])
{
	if (StrContains(client_list, ",") > -1)
	{
		decl String:MessageRecipients[MaxClients][8];
		new recipient_count = ExplodeString(client_list, ",", MessageRecipients, MaxClients, 8);
		for (new i = 0; (i < recipient_count); i++)
		{
			PushStackCell(message_recipients, StringToInt(MessageRecipients[i]));
		}
	}
	else
	{
		PushStackCell(message_recipients, StringToInt(client_list));
	}
}

stock PrintToChatRecipients(const String:message[])
{
	while (IsStackEmpty(message_recipients) == false)
	{
		new recipient_client = -1;
		PopStackCell(message_recipients, recipient_client);

		new client = GetClientOfUserId(recipient_client);
		if (client > 0 && !IsFakeClient(client) && IsClientInGame(client))
		{
			PrintToChat(client, "%s", message);
		}
	}
}

stock PrintToChatRecipientsFF(const String:message[])
{
	while (IsStackEmpty(message_recipients) == false)
	{
		new recipient_client = -1;
		PopStackCell(message_recipients, recipient_client);

		new client = GetClientOfUserId(recipient_client);
		if (client > 0 && !IsFakeClient(client) && IsClientInGame(client))
		{	
			new Handle:hBf;
			hBf = StartMessageOne("SayText", client, USERMSG_RELIABLE|USERMSG_BLOCKHOOKS);
			if (hBf != INVALID_HANDLE)
			{
				if(GetUserMessageType() == UM_Protobuf) 
				{
					PbSetInt(hBf, "ent_idx", 0);
					PbSetBool(hBf, "chat", true);
					PbSetString(hBf, "text", message);
				}
				else
				{
					BfWriteByte(hBf, 0); // send as console
					BfWriteString(hBf, message);
					BfWriteByte(hBf, 1); // 1 to enable color parsing, 0 to not
				}
				EndMessage();
			}
		}
	}
}

/* =========================
 * HLstatsX KD cache (Steam3 optimized)
 * - Receives KD via: sm_kd_set "[U:1:15900511]" "0.59" "3600"
 * - Query via:       sm_kd_get "[U:1:15900511]"    OR sm_kd_get #userid
 * ========================= */

stock bool HLX_KD_NormalizeKey(const char[] inKey, char[] outKey, int outLen)
{
	int len = strcopy(outKey, outLen, inKey);

	TrimString(outKey);

	if (len <= 0)
	{
		outKey[0] = '\0';
		return false;
	}

	if (outKey[0] == '"' || outKey[0] == '\'')
	{
		int q = outKey[0];
		if (len >= 2 && outKey[len - 1] == q)
		{
			outKey[len - 1] = '\0';
			strcopy(outKey, outLen, outKey[1]);
			TrimString(outKey);
		}
	}

	return (outKey[0] != '\0');
}

stock bool HLX_KD_GetByKey(const char[] key, float &kd, int &ttl)
{
	if (g_hKDCache == INVALID_HANDLE || g_hKDExpiry == INVALID_HANDLE)
	{
		ttl = 0;
		kd = 0.0;
		return false;
	}

	char norm[128];
	if (!HLX_KD_NormalizeKey(key, norm, sizeof(norm)))
	{
		ttl = 0;
		kd = 0.0;
		return false;
	}

	int expiry = 0;
	int kdCell = 0;

	if (!GetTrieValue(g_hKDExpiry, norm, expiry))
	{
		ttl = 0;
		kd = 0.0;
		return false;
	}

	int now = GetTime();
	if (now > expiry)
	{
		RemoveFromTrie(g_hKDExpiry, norm);
		RemoveFromTrie(g_hKDCache, norm);

		ttl = 0;
		kd = 0.0;
		return false;
	}

	if (!GetTrieValue(g_hKDCache, norm, kdCell))
	{
		ttl = expiry - now;
		kd = 0.0;
		return false;
	}

	ttl = expiry - now;
	kd = view_as<float>(kdCell);
	return true;
}

stock bool HLX_KD_GetClient(int client, float &kd, int &ttl)
{
	if (client <= 0 || client > MaxClients || !IsClientInGame(client) || IsFakeClient(client))
	{
		ttl = 0;
		kd = 0.0;
		return false;
	}

	char steam3[64];
	GetClientAuthId(client, AuthId_Steam3, steam3, sizeof(steam3), true);

	if (steam3[0] != '\0')
	{
		if (HLX_KD_GetByKey(steam3, kd, ttl))
			return true;
	}

	char steam2[64];
	GetClientAuthId(client, AuthId_Steam2, steam2, sizeof(steam2), true);

	if (steam2[0] != '\0')
	{
		if (HLX_KD_GetByKey(steam2, kd, ttl))
			return true;
	}

	ttl = 0;
	kd = 0.0;
	return false;
}

stock bool HLX_GetPreferredTeamForClient(int client, int &team)
{
	if (client < 1 || client > MaxClients || !IsClientInGame(client))
	{
		return false;
	}

	Handle enabledCvar = FindConVar("jtc_enabled");
	if (enabledCvar == INVALID_HANDLE || !GetConVarBool(enabledCvar))
	{
		return false;
	}

	float kd = 0.0;
	int ttl = 0;
	if (!HLX_KD_GetClient(client, kd, ttl))
	{
		return false;
	}

	Handle thresholdCvar = FindConVar("jtc_kd_threshold");
	float threshold = thresholdCvar != INVALID_HANDLE ? GetConVarFloat(thresholdCvar) : 2.0;
	if (kd < threshold)
	{
		return false;
	}

	int current = GetClientTeam(client);
	if (current != CS_TEAM_T && current != CS_TEAM_CT)
	{
		current = 0;
	}

	int tCount = 0;
	int ctCount = 0;
	for (int i = 1; i <= MaxClients; i++)
	{
		if (i == client || !IsClientInGame(i))
		{
			continue;
		}

		int clientTeam = GetClientTeam(i);
		if (clientTeam == CS_TEAM_T)
		{
			tCount++;
		}
		else if (clientTeam == CS_TEAM_CT)
		{
			ctCount++;
		}
	}

	// Count parity takes precedence over score preference. In particular,
	// placing a high-KD joiner on the losing side must not create a two-player
	// count gap or violate mp_limitteams.
	if (tCount < ctCount)
	{
		team = CS_TEAM_T;
	}
	else if (ctCount < tCount)
	{
		team = CS_TEAM_CT;
	}
	else
	{
		// With equal counts, send a high-KD joiner to the currently losing side.
		int tScore = CS_GetTeamScore(CS_TEAM_T);
		int ctScore = CS_GetTeamScore(CS_TEAM_CT);
		if (tScore < ctScore)
		{
			team = CS_TEAM_T;
		}
		else if (ctScore < tScore)
		{
			team = CS_TEAM_CT;
		}
		else
		{
			team = current != 0 ? current : (GetRandomInt(0, 1) == 0 ? CS_TEAM_T : CS_TEAM_CT);
		}
	}

	return true;
}

public Native_HLX_GetPreferredTeam(Handle:plugin, numParams)
{
	int client = GetNativeCell(1);
	int team = 0;
	bool hasPreference = HLX_GetPreferredTeamForClient(client, team);
	SetNativeCellRef(2, team);
	return hasPreference;
}

public Native_HLX_KD_GetClient(Handle:plugin, numParams)
{
	int client = GetNativeCell(1);
	float kd = 0.0;
	int ttl = 0;
	bool ok = HLX_KD_GetClient(client, kd, ttl);

	SetNativeCellRef(2, view_as<int>(kd));
	SetNativeCellRef(3, ttl);

	return ok;
}

public Action:Command_HLX_KDSet(args)
{
	if (args < 3)
	{
		PrintToServer("[KD] Usage: sm_kd_set <steamid> <kd> <ttl>");
		return Plugin_Handled;
	}

	char rawKey[128];
	char key[128];
	char sKD[32];
	char sTTL[32];

	GetCmdArg(1, rawKey, sizeof(rawKey));
	GetCmdArg(2, sKD, sizeof(sKD));
	GetCmdArg(3, sTTL, sizeof(sTTL));

	if (!HLX_KD_NormalizeKey(rawKey, key, sizeof(key)))
	{
		PrintToServer("[KD] Invalid key: %s", rawKey);
		return Plugin_Handled;
	}

	float kd = StringToFloat(sKD);
	int ttl = StringToInt(sTTL);
	if (ttl < 1) ttl = 1;

	int expiry = GetTime() + ttl;

	if (g_hKDCache == INVALID_HANDLE) g_hKDCache = CreateTrie();
	if (g_hKDExpiry == INVALID_HANDLE) g_hKDExpiry = CreateTrie();

	SetTrieValue(g_hKDCache, key, view_as<int>(kd), true);
	SetTrieValue(g_hKDExpiry, key, expiry, true);

	PrintToServer("[KD] Received for %s: KD=%.2f, TTL=%ds", key, kd, ttl);

	return Plugin_Handled;
}

public Action:Command_HLX_KDGet(args)
{
	if (args < 1)
	{
		PrintToServer("[KD] Usage: sm_kd_get <steamid|#userid>");
		return Plugin_Handled;
	}

	char a1[128];
	GetCmdArg(1, a1, sizeof(a1));
	TrimString(a1);

	float kd = 0.0;
	int ttl = 0;

	if (a1[0] == '#')
	{
		int userid = StringToInt(a1[1]);
		int client = GetClientOfUserId(userid);

		if (HLX_KD_GetClient(client, kd, ttl))
		{
			char steam3[64];
			GetClientAuthId(client, AuthId_Steam3, steam3, sizeof(steam3), true);
			PrintToServer("[KD] %s KD=%.2f TTL=%ds", steam3, kd, ttl);
		}
		else
		{
			PrintToServer("[KD] NODATA %s", a1);
		}

		return Plugin_Handled;
	}

	char key[128];
	if (!HLX_KD_NormalizeKey(a1, key, sizeof(key)))
	{
		PrintToServer("[KD] Invalid key: %s", a1);
		return Plugin_Handled;
	}

	if (HLX_KD_GetByKey(key, kd, ttl))
	{
		PrintToServer("[KD] %s KD=%.2f TTL=%ds", key, kd, ttl);
	}
	else
	{
		PrintToServer("[KD] NODATA %s", key);
	}

	return Plugin_Handled;
}
