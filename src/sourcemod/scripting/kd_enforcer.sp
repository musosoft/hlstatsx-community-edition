#pragma semicolon 1
#pragma newdecls required

#include <sourcemod>

public Plugin myinfo = {
    name = "HLstatsX KD Enforcer",
    author = "Antigravity",
    description = "Receives KD from HLstatsX and enforces rules",
    version = "1.0.0",
    url = ""
};

StringMap g_hKDCache;
StringMap g_hKDExpiry;

public void OnPluginStart() {
    g_hKDCache = new StringMap();
    g_hKDExpiry = new StringMap();
    
    RegServerCmd("sm_kd_set", Command_KDSet, "Sets a player's KD: sm_kd_set <steamid> <kd> <ttl>");
}

public Action Command_KDSet(int args) {
    if (args < 3) {
        PrintToServer("[KD] Usage: sm_kd_set <steamid> <kd> <ttl>");
        return Plugin_Handled;
    }

    char sSteamID[64];
    char sKD[16];
    char sTTL[16];

    GetCmdArg(1, sSteamID, sizeof(sSteamID));
    GetCmdArg(2, sKD, sizeof(sKD));
    GetCmdArg(3, sTTL, sizeof(sTTL));

    float fKD = StringToFloat(sKD);
    int iTTL = StringToInt(sTTL);
    int iExpiry = GetTime() + iTTL;

    g_hKDCache.SetValue(sSteamID, fKD);
    g_hKDExpiry.SetValue(sSteamID, iExpiry);

    PrintToServer("[KD] Received for %s: KD=%.2f, TTL=%ds", sSteamID, fKD, iTTL);
    
    // Check if player is already in-game and apply immediately if needed
    CheckPlayerByAuth(sSteamID, fKD);

    return Plugin_Handled;
}

void CheckPlayerByAuth(const char[] auth, float kd) {
    for (int i = 1; i <= MaxClients; i++) {
        if (IsClientConnected(i) && IsClientAuthorized(i)) {
// Check Steam2
            char sAuth[64];
            GetClientAuthId(i, AuthId_Steam2, sAuth, sizeof(sAuth));
            if (StrEqual(sAuth, auth)) {
                ApplyLogic(i, kd);
                break;
            }
            
            // Check Steam3
            GetClientAuthId(i, AuthId_Steam3, sAuth, sizeof(sAuth));
            if (StrEqual(sAuth, auth)) {
                ApplyLogic(i, kd);
                break;
            }
        }
    }
}

public void OnClientPostAdminCheck(int client) {
    if (IsFakeClient(client)) return;
    
    char sAuth[64];
    GetClientAuthId(client, AuthId_Steam2, sAuth, sizeof(sAuth));
    
    float fKD;
    int iExpiry;
    
    // Check Steam2 first
    if (g_hKDCache.GetValue(sAuth, fKD) && g_hKDExpiry.GetValue(sAuth, iExpiry)) {
        if (GetTime() <= iExpiry) {
            ApplyLogic(client, fKD);
            return;
        } else {
            g_hKDCache.Remove(sAuth);
            g_hKDExpiry.Remove(sAuth);
        }
    }
    
    // Check Steam3
    GetClientAuthId(client, AuthId_Steam3, sAuth, sizeof(sAuth));
    if (g_hKDCache.GetValue(sAuth, fKD) && g_hKDExpiry.GetValue(sAuth, iExpiry)) {
        if (GetTime() <= iExpiry) {
            ApplyLogic(client, fKD);
        } else {
            g_hKDCache.Remove(sAuth);
            g_hKDExpiry.Remove(sAuth);
        }
    }
}

void ApplyLogic(int client, float kd) {
    if (kd > 2.0) {
        PrintToConsole(client, "[KD] You are marked as a High KD player (%.2f).", kd);
        // Add additional enforcement logic here (e.g., restrict team, give tag, etc.)
    }
}
