#pragma semicolon 1
#pragma newdecls required

#include <sourcemod>
#include <sdktools>
#include <cstrike>
#undef REQUIRE_PLUGIN
#undef REQUIRE_EXTENSIONS
#include <hlstatsx_api>
#define REQUIRE_EXTENSIONS
#define REQUIRE_PLUGIN

#define PLUGIN_VERSION "0.2.7"
#define LOCK_SOUND "buttons/button11.wav"
#define TEAM_CHECK_DELAY 1.0

ConVar gCvarEnabled;
ConVar gCvarKDThreshold;
ConVar gCvarConnectDelay;
ConVar gCvarMOTDTitle;
ConVar gCvarMOTDText;
StringMap gMapTeamAssignments;

float gLastBlockNotice[MAXPLAYERS + 1];
Handle gEnforceTimer[MAXPLAYERS + 1];
bool gApplyingTeamChange[MAXPLAYERS + 1];
bool gApplyingPreferredJoin[MAXPLAYERS + 1];
bool gMapTeamLock[MAXPLAYERS + 1];
int gLockedTeam[MAXPLAYERS + 1];
int gJoinTarget[MAXPLAYERS + 1];
bool gPolicyKnown[MAXPLAYERS + 1];
bool gPolicyKDLocked[MAXPLAYERS + 1];
int gPreferredTeam[MAXPLAYERS + 1];
bool gAllowPlacement[MAXPLAYERS + 1];
bool gHadMapAssignment[MAXPLAYERS + 1];
bool gHLStatsApi;

public Plugin myinfo =
{
	name = "HLstatsX:CE Auto Team Balance",
	author = "Imblikator",
	description = "Applies HLstatsX join policy and per-map auto-balance team locks",
	version = PLUGIN_VERSION,
	url = ""
};

public void OnPluginStart()
{
	gCvarEnabled = CreateConVar("jtc_enabled", "1", "Enable balanced join placement and per-map team locks; lifetime KD controls switch restrictions", FCVAR_NOTIFY, true, 0.0, true, 1.0);
	gCvarKDThreshold = CreateConVar("jtc_kd_threshold", "2.0", "Backend KD threshold for join placement", FCVAR_NOTIFY, true, 0.1, true, 20.0);
	gCvarConnectDelay = CreateConVar("jtc_connect_enforce_delay", "1.0", "Delay after connect before enforcing the preferred team", FCVAR_NOTIFY, true, 0.1, true, 10.0);
	gCvarMOTDTitle = CreateConVar("jtc_motd_title", "HLstatsX:CE - Auto Team Balance", "MOTD title shown to KD locked players");
	gCvarMOTDText = CreateConVar("jtc_motd_text", "Lifetime KD >= %KD_THRESHOLD%. Auto-balance may place you on the team that needs you and restrict switching to keep teams balanced.", "MOTD text shown to KD locked players; %KD_THRESHOLD% is replaced dynamically");
	gMapTeamAssignments = new StringMap();
	gHLStatsApi = LibraryExists("hlstatsx_api");

	AddCommandListener(Command_JoinTeam, "jointeam");
	HookEvent("player_team", Event_PlayerTeam, EventHookMode_Post);

	for (int i = 1; i <= MaxClients; i++)
	{
		gEnforceTimer[i] = INVALID_HANDLE;
		gLastBlockNotice[i] = 0.0;
		gMapTeamLock[i] = false;
		gLockedTeam[i] = 0;
		gJoinTarget[i] = 0;
		gPolicyKnown[i] = false;
		gPolicyKDLocked[i] = false;
		gPreferredTeam[i] = 0;
		gAllowPlacement[i] = false;
		gHadMapAssignment[i] = false;
		if (IsValidHuman(i))
		{
			int currentTeam = GetClientTeam(i);
			if (currentTeam == CS_TEAM_T || currentTeam == CS_TEAM_CT)
			{
				gHadMapAssignment[i] = true;
				gMapTeamLock[i] = true;
				if (IsClientKDLocked(i))
				{
					gLockedTeam[i] = 0;
				}
				else
				{
					gLockedTeam[i] = currentTeam;
					RememberLockedTeam(i, currentTeam);
				}
			}
		}
		if (IsValidHuman(i))
		{
			RequestJoinPolicy(i);
		}
	}
}

public void OnMapStart()
{
	PrecacheSound(LOCK_SOUND, true);
	gMapTeamAssignments.Clear();
	for (int client = 1; client <= MaxClients; client++)
	{
		gMapTeamLock[client] = false;
		gHadMapAssignment[client] = false;
		int currentTeam = IsValidHuman(client) ? GetClientTeam(client) : 0;
		if (currentTeam == CS_TEAM_T || currentTeam == CS_TEAM_CT)
		{
			gHadMapAssignment[client] = true;
			gMapTeamLock[client] = true;
			gLockedTeam[client] = currentTeam;
			RememberLockedTeam(client, currentTeam);
		}
		else
		{
			gLockedTeam[client] = 0;
		}
		gJoinTarget[client] = 0;
		gPolicyKnown[client] = false;
		gPolicyKDLocked[client] = false;
		gPreferredTeam[client] = 0;
		gAllowPlacement[client] = true;
		if (IsValidHuman(client) && IsClientKDLocked(client)
			&& gLockedTeam[client] == 0)
		{
			ScheduleTeamCheck(client, gCvarConnectDelay.FloatValue);
		}
		if (IsValidHuman(client))
		{
			RequestJoinPolicy(client);
		}
	}
}

public void OnMapEnd()
{
	// TIMER_FLAG_NO_MAPCHANGE destroys these timers without invoking their
	// callbacks. Clear the stored handles before clients disconnect/reconnect.
	for (int client = 1; client <= MaxClients; client++)
	{
		gEnforceTimer[client] = INVALID_HANDLE;
		gApplyingTeamChange[client] = false;
		gApplyingPreferredJoin[client] = false;
		gMapTeamLock[client] = false;
		gLockedTeam[client] = 0;
		gJoinTarget[client] = 0;
	}
}

public void OnClientPostAdminCheck(int client)
{
	if (!gCvarEnabled.BoolValue || !IsValidHuman(client))
	{
		return;
	}

	CancelClientTimer(client);
	RequestJoinPolicy(client);
	gMapTeamLock[client] = false;
	gLockedTeam[client] = 0;
	gHadMapAssignment[client] = false;
	char auth[64];
	if (GetClientAuthId(client, AuthId_Steam3, auth, sizeof(auth), true)
		&& gMapTeamAssignments.GetValue(auth, gLockedTeam[client]))
	{
		gMapTeamLock[client] = true;
		gHadMapAssignment[client] = true;
	}
	gAllowPlacement[client] = true;
	gJoinTarget[client] = 0;
	gPolicyKnown[client] = false;
	gPolicyKDLocked[client] = false;
	gPreferredTeam[client] = 0;
	gEnforceTimer[client] = CreateTimer(gCvarConnectDelay.FloatValue, Timer_EnforceClientTeam, GetClientUserId(client), TIMER_FLAG_NO_MAPCHANGE);

	ShowLockInfoIfNeeded(client);
}

public void OnClientDisconnect(int client)
{
	CancelClientTimer(client);
	gLastBlockNotice[client] = 0.0;
	gApplyingTeamChange[client] = false;
	gApplyingPreferredJoin[client] = false;
	gMapTeamLock[client] = false;
	gLockedTeam[client] = 0;
	gJoinTarget[client] = 0;
	gPolicyKnown[client] = false;
	gPolicyKDLocked[client] = false;
	gPreferredTeam[client] = 0;
	gAllowPlacement[client] = false;
	gHadMapAssignment[client] = false;
}

public Action Event_PlayerTeam(Event event, const char[] name, bool dontBroadcast)
{
	if (!gCvarEnabled.BoolValue)
	{
		return Plugin_Continue;
	}

	int client = GetClientOfUserId(event.GetInt("userid"));
	if (!IsValidHuman(client))
	{
		return Plugin_Continue;
	}

	int newTeam = event.GetInt("team");
	if (newTeam != CS_TEAM_T && newTeam != CS_TEAM_CT)
	{
		return Plugin_Continue;
	}

	if (!gMapTeamLock[client])
	{
		if (gAllowPlacement[client] && !gHadMapAssignment[client]
			&& gPolicyKnown[client] && IsValidPreferredTeam(gPreferredTeam[client]))
		{
			gMapTeamLock[client] = false;
			gLockedTeam[client] = 0;
			EnforcePreferredTeam(client);
			return Plugin_Continue;
		}
		gMapTeamLock[client] = true;
		if (IsClientKDLocked(client))
		{
			ScheduleTeamCheck(client, TEAM_CHECK_DELAY);
		}
		else
		{
			gLockedTeam[client] = newTeam;
			RememberLockedTeam(client, newTeam);
		}
		return Plugin_Continue;
	}

	if (gLockedTeam[client] == 0)
	{
		if (gApplyingTeamChange[client] || gJoinTarget[client] == newTeam)
		{
			gLockedTeam[client] = newTeam;
			gJoinTarget[client] = 0;
			RememberLockedTeam(client, newTeam);
		}
		else
		{
			// A policy response may arrive before or after the first team event.
			// Enforce immediately when known; otherwise the response handles it.
			if (gPolicyKnown[client] && IsClientKDLocked(client))
			{
				EnforcePreferredTeam(client);
			}
		}
	}
	else if (event.GetInt("oldteam") != newTeam)
	{
		// Server-side ATB swaps are authoritative. Manual jointeam requests are
		// blocked before they can raise player_team, so accepting this event also
		// updates the lock after a legitimate balance move.
		gLockedTeam[client] = newTeam;
		RememberLockedTeam(client, newTeam);
	}

	return Plugin_Continue;
}

public Action Timer_EnforceClientTeam(Handle timer, any userId)
{
	int client = GetClientOfUserId(userId);
	if (client < 1 || client > MaxClients)
	{
		return Plugin_Stop;
	}

	if (gEnforceTimer[client] == timer)
	{
		gEnforceTimer[client] = INVALID_HANDLE;
	}

	if (!gCvarEnabled.BoolValue || !IsValidHuman(client))
	{
		return Plugin_Stop;
	}

	if (gAllowPlacement[client] && gPolicyKnown[client]
		&& IsValidPreferredTeam(gPreferredTeam[client])
		&& !gHadMapAssignment[client])
	{
		gMapTeamLock[client] = false;
		gLockedTeam[client] = 0;
		EnforcePreferredTeam(client);
	}

	return Plugin_Stop;
}

public Action Command_JoinTeam(int client, const char[] command, int args)
{
	if (client > 0 && client <= MaxClients && gApplyingPreferredJoin[client])
	{
		return Plugin_Continue;
	}

	if (!gCvarEnabled.BoolValue || !IsValidHuman(client))
	{
		return Plugin_Continue;
	}
	if (!gPolicyKnown[client] && !gHadMapAssignment[client])
	{
		return Plugin_Continue;
	}

	bool kdLocked = IsClientKDLocked(client);
	if (!gMapTeamLock[client] && !kdLocked)
	{
		return Plugin_Continue;
	}
	if (kdLocked)
	{
		gMapTeamLock[client] = true;
	}

	int desired = GetJointeamDesired(args);
	if (desired == CS_TEAM_SPECTATOR)
	{
		return Plugin_Continue;
	}
	if (GetClientTeam(client) == CS_TEAM_SPECTATOR && gLockedTeam[client] != 0)
	{
		ForcePreferredJoin(client, gLockedTeam[client]);
		return Plugin_Handled;
	}

	if (gLockedTeam[client] != 0)
	{
		if (desired == 0 || desired == 5 || desired != gLockedTeam[client])
		{
			if (!kdLocked && (CanSwitchWithoutBreakingParity(client, desired)
				|| IsSwitchToTrailingTeam(gLockedTeam[client], desired)))
			{
				return Plugin_Continue;
			}
			int currentTeam = GetClientTeam(client);
			if (currentTeam != CS_TEAM_NONE && currentTeam != CS_TEAM_SPECTATOR)
			{
				NotifyBlocked(client, gLockedTeam[client]);
			}
			ForcePreferredJoin(client, gLockedTeam[client]);
			return Plugin_Handled;
		}
		return Plugin_Continue;
	}

	int preferredTeam = 0;
	if (!GetPreferredTeam(client, preferredTeam))
	{
		return Plugin_Continue;
	}

	if (desired == 0 || desired == 5)
	{
		ForcePreferredJoin(client, preferredTeam);
		return Plugin_Handled;
	}

	if (desired != preferredTeam)
	{
		ForcePreferredJoin(client, preferredTeam);
		return Plugin_Handled;
	}

	return Plugin_Continue;
}

void ForcePreferredJoin(int client, int team)
{
	ShowVGUIPanel(client, "team", INVALID_HANDLE, false);
	gJoinTarget[client] = team;
	gApplyingPreferredJoin[client] = true;
	FakeClientCommand(client, "jointeam %d", team);
	gApplyingPreferredJoin[client] = false;
	ScheduleClassSelection(client);
}

void ScheduleClassSelection(int client)
{
	CreateTimer(0.1, Timer_ShowClassSelection, GetClientUserId(client), TIMER_FLAG_NO_MAPCHANGE);
}

public Action Timer_ShowClassSelection(Handle timer, any userId)
{
	int client = GetClientOfUserId(userId);
	if (client < 1 || client > MaxClients || !IsValidHuman(client))
	{
		return Plugin_Stop;
	}

	int team = GetClientTeam(client);
	if (team == CS_TEAM_T || team == CS_TEAM_CT)
	{
		ShowVGUIPanel(client, "class");
	}
	return Plugin_Stop;
}

void EnforcePreferredTeam(int client)
{
	if (gLockedTeam[client] != 0)
	{
		return;
	}

	int current = GetClientTeam(client);
	if (current == CS_TEAM_SPECTATOR)
	{
		return;
	}

	int preferredTeam = 0;
	if (!GetPreferredTeam(client, preferredTeam))
	{
		return;
	}

	if (current == preferredTeam)
	{
		gLockedTeam[client] = current;
		RememberLockedTeam(client, current);
		return;
	}
	ShowVGUIPanel(client, "team", INVALID_HANDLE, false);
	if (GetFeatureStatus(FeatureType_Native, "CS_SwitchTeam") == FeatureStatus_Available)
	{
		gApplyingTeamChange[client] = true;
		CS_SwitchTeam(client, preferredTeam);
		gApplyingTeamChange[client] = false;
	}
	else
	{
		gApplyingTeamChange[client] = true;
		ChangeClientTeam(client, preferredTeam);
		gApplyingTeamChange[client] = false;
	}
	gLockedTeam[client] = GetClientTeam(client);
	if (gLockedTeam[client] != CS_TEAM_T && gLockedTeam[client] != CS_TEAM_CT)
	{
		gLockedTeam[client] = current;
	}
	RememberLockedTeam(client, gLockedTeam[client]);
	if (gLockedTeam[client] == preferredTeam)
	{
		ScheduleClassSelection(client);
	}

	char teamName[16];
	strcopy(teamName, sizeof(teamName), (preferredTeam == CS_TEAM_T) ? "T" : "CT");
	PrintToChat(client, "\x04HLstatsX:CE - ATB\x01 - You were placed on %s for auto-balance. Staying there helps prevent repeated swaps this map.", teamName);
}

void ShowLockInfoIfNeeded(int client)
{
	if (!IsClientKDLocked(client))
	{
		return;
	}

	char title[128];
	char text[512];
	gCvarMOTDTitle.GetString(title, sizeof(title));
	gCvarMOTDText.GetString(text, sizeof(text));
	char threshold[32];
	gCvarKDThreshold.GetString(threshold, sizeof(threshold));
	ReplaceString(text, sizeof(text), "%KD_THRESHOLD%", threshold, false);

	ShowMOTDPanel(client, title, text, MOTDPANEL_TYPE_TEXT);
	PrintToChat(client, "\x04HLstatsX:CE - ATB\x01 - Lifetime KD %s+ detected; auto-balance may place you where teams need you.", threshold);
}

bool CanSwitchWithoutBreakingParity(int client, int desiredTeam)
{
	if (desiredTeam != CS_TEAM_T && desiredTeam != CS_TEAM_CT)
	{
		return false;
	}

	int tCount = 0;
	int ctCount = 0;
	for (int i = 1; i <= MaxClients; i++)
	{
		if (i == client || !IsClientInGame(i))
		{
			continue;
		}
		int team = GetClientTeam(i);
		if (team == CS_TEAM_T)
		{
			tCount++;
		}
		else if (team == CS_TEAM_CT)
		{
			ctCount++;
		}
	}

	if (desiredTeam == CS_TEAM_T)
	{
		tCount++;
	}
	else
	{
		ctCount++;
	}
	int difference = tCount > ctCount ? tCount - ctCount : ctCount - tCount;
	return difference <= 1;
}

bool IsSwitchToTrailingTeam(int currentTeam, int desiredTeam)
{
	if ((currentTeam != CS_TEAM_T && currentTeam != CS_TEAM_CT)
		|| (desiredTeam != CS_TEAM_T && desiredTeam != CS_TEAM_CT))
	{
		return false;
	}

	return GetTeamScore(desiredTeam) < GetTeamScore(currentTeam);
}

bool IsClientKDLocked(int client)
{
	return client > 0 && client <= MaxClients && gPolicyKnown[client]
		&& gPolicyKDLocked[client];
}

bool GetPreferredTeam(int client, int &team)
{
	if (!gCvarEnabled.BoolValue || !IsValidHuman(client) || !gPolicyKnown[client]
		|| !IsValidPreferredTeam(gPreferredTeam[client]))
	{
		return false;
	}

	team = gPreferredTeam[client];
	return true;
}

bool IsValidPreferredTeam(int team)
{
	return team == CS_TEAM_T || team == CS_TEAM_CT;
}

public void OnAllPluginsLoaded()
{
	gHLStatsApi = LibraryExists("hlstatsx_api");
}

public void OnLibraryAdded(const char[] name)
{
	if (StrEqual(name, "hlstatsx_api"))
	{
		gHLStatsApi = true;
		for (int client = 1; client <= MaxClients; client++)
		{
			if (IsValidHuman(client) && gAllowPlacement[client])
			{
				RequestJoinPolicy(client);
			}
		}
	}
}

public void OnLibraryRemoved(const char[] name)
{
	if (StrEqual(name, "hlstatsx_api"))
	{
		gHLStatsApi = false;
		for (int client = 1; client <= MaxClients; client++)
		{
			gPolicyKnown[client] = false;
			gPolicyKDLocked[client] = false;
			gPreferredTeam[client] = 0;
		}
	}
}

void RequestJoinPolicy(int client)
{
	if (!gHLStatsApi || !IsValidHuman(client))
	{
		return;
	}
	gPolicyKnown[client] = false;
	gPolicyKDLocked[client] = false;
	gPreferredTeam[client] = 0;
	HLStatsX_Api_GetJoinPolicy(client, JoinPolicyResponse, GetClientUserId(client));
}

public void JoinPolicyResponse(int command, int payload, int client, DataPack &dataPack)
{
	if (command != HLX_CALLBACK_TYPE_JOIN_POLICY || !IsValidHuman(client)
		|| GetClientUserId(client) != payload)
	{
		return;
	}

	DataPack pack = view_as<DataPack>(CloneHandle(dataPack));
	int preferredTeam = pack.ReadCell();
	bool kdLocked = pack.ReadCell() != 0;
	delete pack;

	gPolicyKnown[client] = true;
	gPolicyKDLocked[client] = kdLocked;
	gPreferredTeam[client] = preferredTeam;
	if (kdLocked)
	{
		ShowLockInfoIfNeeded(client);
	}
	if (gAllowPlacement[client] && !gHadMapAssignment[client]
		&& IsValidPreferredTeam(preferredTeam))
	{
		gMapTeamLock[client] = false;
		gLockedTeam[client] = 0;
		int currentTeam = GetClientTeam(client);
		if (currentTeam == CS_TEAM_T || currentTeam == CS_TEAM_CT)
		{
			EnforcePreferredTeam(client);
		}
		else
		{
			ScheduleTeamCheck(client, TEAM_CHECK_DELAY);
		}
	}
	else if (!gHadMapAssignment[client])
	{
		int currentTeam = GetClientTeam(client);
		if (currentTeam == CS_TEAM_T || currentTeam == CS_TEAM_CT)
		{
			gMapTeamLock[client] = true;
			gLockedTeam[client] = currentTeam;
			RememberLockedTeam(client, currentTeam);
		}
	}
}

void ScheduleTeamCheck(int client, float delay)
{
	if (client < 1 || client > MaxClients || !IsValidHuman(client))
	{
		return;
	}
	CancelClientTimer(client);
	gEnforceTimer[client] = CreateTimer(delay, Timer_EnforceClientTeam, GetClientUserId(client), TIMER_FLAG_NO_MAPCHANGE);
}

int GetJointeamDesired(int args)
{
	if (args < 1)
	{
		return 0;
	}

	char arg[16];
	GetCmdArg(1, arg, sizeof(arg));
	TrimString(arg);

	if (arg[0] == '\0')
	{
		return 0;
	}

	return StringToInt(arg);
}

void NotifyBlocked(int client, int weakerTeam)
{
	float now = GetGameTime();
	if (now - gLastBlockNotice[client] < 1.5)
	{
		return;
	}

	gLastBlockNotice[client] = now;

	char teamName[16];
	strcopy(teamName, sizeof(teamName), (weakerTeam == CS_TEAM_T) ? "T" : "CT");

	PrintCenterText(client, "HLstatsX ATB prefers %s this map.", teamName);
	PrintToChat(client, "\x04HLstatsX:CE - ATB\x01 - Auto-balance prefers you on %s to keep teams balanced and avoid repeated swaps this map. Spectator remains available.", teamName);
	EmitSoundToClient(client, LOCK_SOUND, client, SNDCHAN_AUTO, SNDLEVEL_NORMAL, SND_NOFLAGS, 1.0, SNDPITCH_NORMAL);
}

bool IsValidHuman(int client)
{
	return client > 0 && client <= MaxClients && IsClientInGame(client) && !IsFakeClient(client);
}

void RememberLockedTeam(int client, int team)
{
	if (!IsValidHuman(client) || (team != CS_TEAM_T && team != CS_TEAM_CT))
	{
		return;
	}

	char auth[64];
	if (GetClientAuthId(client, AuthId_Steam3, auth, sizeof(auth), true))
	{
		gMapTeamAssignments.SetValue(auth, team);
	}
}

void CancelClientTimer(int client)
{
	if (gEnforceTimer[client] != INVALID_HANDLE)
	{
		CloseHandle(gEnforceTimer[client]);
		gEnforceTimer[client] = INVALID_HANDLE;
	}
}
