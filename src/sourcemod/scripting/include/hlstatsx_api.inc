#if defined _hlstatsx_api_included
  #endinput
#endif
#define _hlstatsx_api_included

#define MAX_INT_VALUE 					65535
#define HLX_CALLBACK_TYPE_PLAYER_INFO	101
#define HLX_CALLBACK_TYPE_JOIN_POLICY	102

public SharedPlugin __pl_hlstatsx_api = 
{
	name = "hlstatsx_api",
	file = "hlstatsx_api.smx",
	#if defined REQUIRE_PLUGIN
	required = 1,
	#else
	required = 0,
	#endif
}

public void __pl_hlstatsx_api_SetNTVOptional()
{
	MarkNativeAsOptional("HLStatsX_Api_GetStats");
	MarkNativeAsOptional("HLStatsX_Api_GetJoinPolicy");
	MarkNativeAsOptional("HLStatsX_Api_HandleResponse");
}

typedef HLStatsX_Api_StatsCallback = function void(int command, int payload, int client, DataPack &dataPack);

/**
 * Query HLStatsX Stats data from a client
 * 
 * @param request		Request type (only 'playerinfo' available)
 * @param client		Client index.
 * @param func			Callback function.
 * @param payload		Payload to distinct queries.
 * @noreturn
 */
native bool HLStatsX_Api_GetStats(char[] request, int client, HLStatsX_Api_StatsCallback callback, int payload = 0);

typedef HLStatsX_Api_JoinPolicyCallback = function void(int command, int payload, int client, DataPack &dataPack);

/**
 * Request the backend-computed join policy for a client.
 * The response DataPack contains preferred team (2=T, 3=CT) and KD-lock flag.
 */
native bool HLStatsX_Api_GetJoinPolicy(int client, HLStatsX_Api_JoinPolicyCallback callback, int payload = 0);
native int HLStatsX_Api_HandleResponse(int args);
