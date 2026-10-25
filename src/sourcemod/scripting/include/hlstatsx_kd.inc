#if defined _hlstatsx_kd_included
  #endinput
#endif
#define _hlstatsx_kd_included

public SharedPlugin __pl_hlstatsx_kd =
{
	name = "hlstatsx_kd",
	file = "hlstatsx.smx",
	#if defined REQUIRE_PLUGIN
	required = 1,
	#else
	required = 0,
	#endif
}

public void __pl_hlstatsx_kd_SetNTVOptional()
{
	MarkNativeAsOptional("HLX_KD_GetClient");
	MarkNativeAsOptional("HLX_GetPreferredTeam");
}

/** Retrieve a player's cached HLstatsX lifetime K/D and remaining cache TTL. */
native bool HLX_KD_GetClient(int client, float &kd, int &ttl);

/** CSS join-policy preference; only meaningful for Counter-Strike team IDs. */
native bool HLX_GetPreferredTeam(int client, int &team);
