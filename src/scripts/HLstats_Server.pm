package HLstats_Server;
# HLstatsX Community Edition - Real-time player and clan rankings and statistics
# Copyleft (L) 2008-20XX Nicholas Hastings (nshastings@gmail.com)
# http://www.hlxcommunity.com
#
# HLstatsX Community Edition is a continuation of 
# ELstatsNEO - Real-time player and clan rankings and statistics
# Copyleft (L) 2008-20XX Malte Bayer (steam@neo-soft.org)
# http://ovrsized.neo-soft.org/
# 
# ELstatsNEO is an very improved & enhanced - so called Ultra-Humongus Edition of HLstatsX
# HLstatsX - Real-time player and clan rankings and statistics for Half-Life 2
# http://www.hlstatsx.com/
# Copyright (C) 2005-2007 Tobias Oetzel (Tobi@hlstatsx.com)
#
# HLstatsX is an enhanced version of HLstats made by Simon Garner
# HLstats - Real-time player and clan rankings and statistics for Half-Life
# http://sourceforge.net/projects/hlstats/
# Copyright (C) 2001  Simon Garner
#             
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
# 
# For support and installation notes visit http://www.hlxcommunity.com

use POSIX;
use IO::Socket;
use Socket;
use Encode;
use Syntax::Keyword::Try;

# Share scalar player counts between servers for the same game, not DB handles.
my %player_count_cache;

do "$::opt_libdir/HLstats_GameConstants.plib";

sub new
{
	my ($class_name, $serverId, $address, $port, $server_name, $rcon_pass, $game, $publicaddress, $gameengine, $realgame, $maxplayers, $mp_maxrounds, $mp_timelimit, $mp_halftime, $map, $map_started) = @_;
	
	my ($self) = {};
	
	bless($self, $class_name);
	
	$self->{id}             = $serverId;
	$self->{address}        = $address;
	$self->{port}           = $port;
	$self->{game}           = $game;
	$self->{rcon}           = $rcon_pass;
	$self->{srv_players}	= ();
	$self->{jtc_join_reservations} = {};
	
	# Game Engine
	# HL1 - 1
	# HL2 (original) - 2
	# HL2ep2 ("OrangeBox") - 3
	$self->{game_engine}	= $gameengine;
	
    $self->{rcon_obj}	    = undef;
    $self->{name}           = $server_name;
    $self->{auto_ban}       = 0;
    $self->{contact}        = "";
    $self->{hlstats_url}    = "";
    $self->{publicaddress}  = $publicaddress;
	$self->{play_game}      = -1;
	
	$self->{last_event}     = 0;
	$self->{last_check}     = 0;
    
	$self->{lines}         = 0;
	$self->{map}           = $map || "";
	$self->{numplayers}    = 0;
	$self->{num_trackable_players} = 0;
	$self->{minplayers}    = 6;
	$self->{maxplayers}    = $maxplayers;
	$self->{difficulty}    = 0;
	$self->{mp_maxrounds}  = $mp_maxrounds;
	$self->{mp_timelimit}  = $mp_timelimit;
	$self->{mp_halftime}  = $mp_halftime;
	
    $self->{players}       = 0;
 	$self->{rounds}        = 0;
	$self->{kills}         = 0;
	$self->{suicides}      = 0;
	$self->{headshots}     = 0;
	$self->{ct_shots}      = 0;
	$self->{ct_hits}       = 0;
	$self->{ts_shots}      = 0;
	$self->{ts_hits}       = 0;
	$self->{bombs_planted} = 0;
	$self->{bombs_defused} = 0;
	$self->{ct_wins}       = 0;
	$self->{ts_wins}       = 0; 
	$self->{map_started}   = $map_started || time();
	$self->{map_changes}   = 0;
	$self->{map_rounds}    = 0;
	$self->{map_ct_wins}   = 0;
	$self->{map_ts_wins}   = 0; 
	$self->{map_ct_shots}  = 0;
	$self->{map_ct_hits}   = 0;
	$self->{map_ts_shots}  = 0; 
	$self->{map_ts_hits}   = 0;

	# team balancer
	$self->{ba_enabled}       = 0;
	$self->{ba_ct_wins}       = 0;
	$self->{ba_ts_win}        = 0;
	$self->{ba_ct_frags}      = 0;
	$self->{ba_ts_frags}      = 0;
	$self->{ba_winner}        = ();
	$self->{ba_map_rounds}    = 0;
	$self->{ba_last_swap}     = 0;
	$self->{ba_player_switch} = 0;  # player switched on his own
	$self->{ba_swapped_players} = {};
	$self->{ba_pending_swaps} = {};
	$self->{ba_swap_history_loaded} = 0;
	$self->{ba_swap_history_warning} = 0;
	$self->{ba_round_orientation} = [];
	$self->{ba_round_bias_active} = 0;
	$self->{ba_round_bias_eligible} = 0;
	$self->{ba_eligible_ct_rounds} = 0;
	$self->{ba_eligible_ts_rounds} = 0;
	$self->{ba_fill_roster_signature} = undef;
	$self->{ba_fill_roster_stable_since} = 0;
	$self->{jtc_enabled_checked_at} = 0;
	$self->{jtc_enabled_cached} = 0;
	$self->{jtc_threshold_checked_at} = 0;
	$self->{jtc_threshold_cached} = 2.0;
	$self->{ba_balance_pending} = 0;
	$self->{ba_balance_due} = 0;
	$self->{ba_balance_deadline} = 0;
	$self->{ba_round_active} = 0;
	$self->{ba_roster_changed_at} = 0;
	$self->{ba_halftime_rotated} = 0;
	$self->{balance_switch_only_dead} = 0;
	$self->{balance_ignore_bots} = 0;
	$self->{balance_start_rounds} = 0;
	$self->{balance_rounds_delay} = 0;
	$self->{balance_analyze_rounds} = 0;
	$self->{balance_max_wins} = 0;
	
	# Messaging commands
	$self->{show_stats}                  	= 0;
	$self->{broadcasting_events}         	= 0;
	$self->{broadcasting_player_actions} 	= 0;
	$self->{broadcasting_command}        	= "";
  	$self->{broadcasting_command_announce}	= "say";
	$self->{player_events}               	= 1; 
 	$self->{player_command}              	= "say";
 	$self->{player_command_osd}          	= "";
	$self->{player_command_hint}			= "";
	$self->{player_admin_command}        	= 0;
	$self->{default_display_events}			= 1;
	$self->{browse_command} 				= "";
	$self->{swap_command}					= "";
	$self->{exec_command}					= "";
	$self->{global_chat_command}			= "say";
	
	# Message format operators
	$self->{format_color}		= "";
	$self->{format_action}		= "";
	$self->{format_actionend}	= "";
	
	$self->{total_kills}                   = 0;
	$self->{total_headshots}               = 0;
	$self->{total_suicides}                = 0;
	$self->{total_rounds}                  = 0;
	$self->{total_shots}                   = 0;
	$self->{total_hits}                    = 0;

	$self->{track_server_load}             = 0;
	$self->{track_server_timestamp}        = 0;
	
	$self->{ignore_nextban}                = ();
	$self->{use_browser}                   = 0;
	$self->{round_status}                  = 0;
	$self->{min_players_rank}              = 1;
	$self->{admins}                        = ();
	$self->{ignore_bots}                   = 1;
	$self->{tk_penalty}                    = 0;
	$self->{suicide_penalty}               = 0;
	$self->{skill_mode}                    = 0;
	$self->{game_type}                     = 0;
    $self->{bonusroundignore}              = 0;
    $self->{bonusroundtime}                = 0;
    $self->{bonusroundtime_ts}             = 0;
    $self->{bonusroundtime_state}          = 0;
	$self->{lastdisabledbonus}			   = $::ev_unixtime;
	$self->{mod}                           = "";
	$self->{switch_admins}                 = 0;
	$self->{public_commands}               = 1;
	$self->{connect_announce}			   = 0;
	$self->{update_hostname}			   = 0;
	
	$self->{lastblueflagdefend}			   = 0;
	$self->{lastredflagdefend}			   = 0;
	
	# location hax
	$self->{nextkillx}					= "";
	$self->{nextkilly}					= "";
	$self->{nextkillz}					= "";
	$self->{nextkillvicx}					= "";
	$self->{nextkillvicy}					= "";
	$self->{nextkillvicz}					= "";
	
	$self->{nextkillheadshot}			= 0;
	
	$self->{next_timeout} = 0;
	$self->{next_flush} = 0;
	$self->{next_plyr_flush} = 0;
	$self->{needsupdate} = 0;
	
	$self->set_play_game($realgame);
	
	if ($self->{rcon})
	{
		$self->init_rcon();
	}
	
	$self->updateDB();
	$self->update_server_loc();

	return $self;
}

sub set_play_game
{
	my ($self, $realgame) = @_;
	
	if (exists($gamecode_to_game{$realgame}))
	{
		$self->{play_game} = $gamecode_to_game{$realgame};
	}
}

sub is_admin
{
	my($self, $steam_id) = @_;
	for (@{$self->{admins}}) {
		if ($_ eq $steam_id) {
			return 1;
		}
	}
	return 0;
}

sub get_game_mod_opts
{
	# Runs immediately after server object is created and options are loaded.
	my($self) = @_;

	if ($self->{mod} ne "") {
		my $mod = $self->{mod};
			
		if ($mod eq "SOURCEMOD") {
			$self->{browse_command} = "hlx_sm_browse";
			$self->{swap_command} = "hlx_sm_swap";
			$self->{global_chat_command} = "hlx_sm_psay";
			$self->setHlxCvars();
		} elsif ($mod eq "MANI") {
			$self->{browse_command} = "ma_hlx_browse";
			$self->{swap_command} = "ma_swapteam";
			$self->{exec_command} = "ma_cexec";
			$self->{global_chat_command} = "ma_psay";
		} elsif ($mod eq "AMXX") {
			$self->{browse_command} = "hlx_amx_browse";
			$self->{swap_command} = "hlx_amx_swap";
			$self->{global_chat_command} = "hlx_amx_psay";
			$self->setHlxCvars();
		} elsif ($mod eq "BEETLE") {
			$self->{browse_command} = "hlx_browse";
			$self->{swap_command} = "hlx_swap";
			$self->{exec_command} = "hlx_exec";
			$self->{global_chat_command} = "admin_psay";
		} elsif ($mod eq "MINISTATS") {
			$self->{browse_command} = "ms_browse";
			$self->{swap_command} = "ms_swap";
			$self->{global_chat_command} = "ms_psay";
		}
		
		# Turn on color and add game-specific color modifiers for when using hlx:ce sourcemod plugin
		if (($self->{mod} eq "SOURCEMOD" &&
				(
				$self->{play_game} == CSS()
				|| $self->{play_game} == TF()
				|| $self->{play_game} == L4D()
				|| $self->{play_game} == DODS()
				|| $self->{play_game} == HL2MP()
				|| $self->{play_game} == AOC()
				|| $self->{play_game} == ZPS()
				|| $self->{play_game} == FF()
				|| $self->{play_game} == GES()
				|| $self->{play_game} == FOF()
				|| $self->{play_game} == PVKII()
				|| $self->{play_game} == CSP()
				|| $self->{play_game} == NUCLEARDAWN()
				|| $self->{play_game} == DDD()
				)
			)
			|| ($self->{mod} eq "AMXX"
				&& $self->{play_game} == CSTRIKE())
		) {
	
			$self->{format_color} = " 1";
			if ($self->{play_game} == ZPS() || $self->{play_game} == GES()) {
				$self->{format_action} = "\x05";
			} elsif ($self->{play_game} == FF()) {
				$self->{format_action} = "^4";
			} else {
				$self->{format_action} = "\x04";
			}
			
			if ($self->{play_game} == FF()) {
				$self->{format_actionend} = "^0";
			} else {
				$self->{format_actionend} = "\x01";
			}
		}
		# Insurgency can only do one solid color afaik. The rest is handled in the plugin
		if ($self->{mod} eq "SOURCEMOD" && $self->{play_game} == INSMOD()) {
			$self->{format_color} = " 1";
		}
	}
}

sub format_userid {
	my($self, $userid) = @_;
	if ($self->{mod} eq "AMXX") {
		return "#".$userid;
	}
	return "\"".$userid."\"";
}

sub quoteparam {
	my($self, $message) = @_;
	$message =~ s/'/ ' /g;
	$message =~ s/"/ '' /g;
	
	if (($self->{game_engine} != 2 || $self->{mod} eq "SOURCEMOD") && $self->{mod} ne "MANI") {
		return "\"".$message."\"";
	}
	return $message;
}

#
# Set property 'key' to 'value'
#

sub set
{
	my ($self, $key, $value) = @_;
	
	if (defined($self->{$key}))
	{
		if ($self->{$key} eq $value)
		{
			if ($::g_debug > 2)
			{
				&::printNotice("Hlstats_Server->set ignored: Value of \"$key\" is already \"$value\"");
			}
			return 0;
		}
		
		$self->{$key} = $value;
		
		if ($key eq "hlstats_url")  {
        	# so ingame browsing will work correctly
            $self->{ingame_url}  = $value;
            $self->{ingame_url}  =~ s/\/hlstats.php//i;
			$self->{ingame_url}  =~ s/\/$//;
         	&::printEvent("SERVER", "Ingame-URL: ".$self->{ingame_url}, 1);
		}
		return 1;
	}
	else
	{
		warn("HLstats_Server->set: \"$key\" is not a valid property name\n");
		return 0;
	}
}


#
# Increment (or decrement) the value of 'key' by 'amount' (or 1 by default)
#

sub increment
{
	my ($self, $key, $amount) = @_;
	if ($amount) {
		$amount = int($amount);
	} else {
		$amount = 1
	}
	
	my $value = $self->{$key};
	$self->set($key, $value + $amount);
}


sub init_rcon
{
	my ($self)      = @_;
	my $server_ip   = $self->{address};
	my $server_port = $self->{port};
	my $rcon_pass   = $self->{rcon};
	my $game        = $self->{game};

	if ($::g_rcon && $rcon_pass) {
		if ($self->{game_engine} == 1) {
			$self->{rcon_obj} = new BASTARDrcon($self);
		} else {
			$self->{rcon_obj} = new TRcon($self);
		}
	}
   	if ($self->{rcon_obj}) {
		&::printEvent ("SERVER", "Connecting to rcon on $server_ip:$server_port ... ok");
		#&::printEvent("SERVER", "Server running game: ".$self->{play_game}, 1);
		&::printEvent("SERVER", "Server running map: ".$self->get_map(), 1);
		if ($::g_mode eq "LAN") {
			$self->get_lan_players();
		}
	}
}

sub dorcon
{
	my ($self, $command)      = @_;
    my $result;
    my $rcon_obj = $self->{rcon_obj};
	if (($rcon_obj) && ($::g_rcon == 1) && ($self->{rcon} ne "")) {
	 
		# replace ; to avoid executing multiple rcon commands.
		$command  =~ s/;//g;
	
		&::printNotice("RCON", $command, 1);
		$result = $rcon_obj->execute($command);
    } else {
		&::printNotice("Rcon error: No Object available");
    }
    return $result;
}

sub dorcon_multi
{
	my ($self, @commands)      = @_;
    my $result;
    my $rcon_obj = $self->{rcon_obj};
	if (($rcon_obj) && ($::g_rcon == 1) && ($self->{rcon} ne "")) {
		if ($self->{game_engine} > 1)
		{
			my $fullcmd = "";
			foreach (@commands)
			{
				# replace ; to avoid executing multiple rcon commands.
				my $cmd = $_;
				$cmd =~ s/;//g;
				$fullcmd .="$cmd;";
			}
			&::printNotice("RCON", $fullcmd, 1);
			$result = $rcon_obj->execute($fullcmd);
		}
		else
		{
			foreach (@commands)
			{
				&::printNotice("RCON", $_, 1);
				$result = $rcon_obj->execute($_);
			}
		}
    } else {
      &::printNotice("Rcon error: No Object available");
    }
    return $result;
}	

sub rcon_getaddress
{
	my ($self, $uniqueid) = @_;
	my $result;
	my $rcon_obj = $self->{rcon_obj};
	if (($rcon_obj) && ($::g_rcon == 1) && ($self->{rcon} ne ""))
	{
		$result = $rcon_obj->getPlayer($uniqueid);
	}
	else
	{
    	 	 &::printNotice("Rcon error: No Object available");
	}
	return $result;
}

sub rcon_getStatus
{
	my ($self) = @_;
    my $rcon_obj = $self->{rcon_obj};
    my $map_result = "";
    my $max_player_result = -1;
	my $servhostname = "";
	my $difficulty = 0;
	my $mp_maxrounds = 0;
	my $mp_timelimit = 0;
	my $mp_halftime = 0;
	
	if (($rcon_obj) && ($::g_rcon == 1) && ($self->{rcon} ne "")) {
		($servhostname, $map_result, $max_player_result, $difficulty) = $rcon_obj->getServerData();
		($visible_maxplayers) = $rcon_obj->getVisiblePlayers();
		
		if (
			$self->{play_game} == CSS() ||
			$self->{play_game} == CSGO() ||
			$self->{play_game} == CS2()
		) {
			($mp_maxrounds) = $rcon_obj->getMaxRounds();
			($mp_timelimit) = $rcon_obj->getTimeLimit();
			($mp_halftime) = $rcon_obj->getHalfTime();
		}

		if (($visible_maxplayers != -1) && ($visible_maxplayers < $max_player_result)) {
			$max_player_result = $visible_maxplayers;
		}
	} else {
		&::printNotice("Rcon error: No Object available");
    }
    return ($map_result, $max_player_result, $servhostname, $difficulty, $mp_maxrounds, $mp_timelimit, $mp_halftime);
}

sub rcon_getplayers
{
	my ($self) = @_;
	my %result;
	my $rcon_obj = $self->{rcon_obj};
	if (($rcon_obj) && ($::g_rcon == 1) && ($self->{rcon} ne ""))
	{
		%result = $rcon_obj->getPlayers();
	} else {
	&::printNotice("Rcon error: No Object available");
	}
	return %result;
}

sub track_server_load
{
    my ($self) = @_;

    if (($::g_stdin == 0) && ($self->{track_server_load} > 0))
    {
        my $last_timestamp = $self->{track_server_timestamp};
        my $new_timestamp  = time();
        if ($last_timestamp > 0)
        {
            if ($last_timestamp+299 < $new_timestamp)
            {

                my $act_players;
                my $max_players;
                my $map;
                my $uptime;
                my $fps;
                if ($self->{play_game} == CS2()) {
                    $status_json = $self->dorcon("status_json");
                    my @lines = split(/[\r\n]+/, $status_json);
                    foreach my $line (@lines) {
                        if ($line =~ /^\s*"map": "([^"]+)".*/) {
                            $map = $1;
                        }
                        if ($line =~ /^\s*"process_uptime": (\d+)/) {
                            $uptime = $1;
                        }
                        if ($line =~ /^\s*"frametime_ms": ([\d\.]+)/) {
                            $fps = floor(1000 / $1);
                        }
                    }
                }else {

                    # fetch fps and uptime via rcon

                    # Old style stats output:
                    #$string = "          0.00  0.00  0.00      54     1  249.81       0 dhjdsk";

                    # New style stats output:
                    #CPU    In (KB/s)  Out (KB/s)  Uptime  Map changes  FPS      Players  Connects
                    #0.00   0.00       0.00        0      0            00.00    0        0

                    $string = $self->dorcon("stats");

                    # leo - no answer from the server. We wont record a server load.
                    if ($string eq "") {
                        # consider as tracked so we dont keep on tracking
                        $self->set("track_server_timestamp", $new_timestamp);

                        &::printEvent("[HLstats_Server][track_server_load]", "No answer from the server " . $self->{address}.":".$self->{port} . ", Returning. Answer: $string", 1);
                        return;
                    }

                    # leo - debug
                    $tmp = $string;

                    # Remove first line of output
                    $string =~ /CPU.*\n(.*)\n*L{0,1}.*\Z/;
                    $string = $1;

                    # Grab FPS and Uptime from the output
                    $string =~ /([^ ]+)\s+([^ ]+)\s+([^ ]+)\s+([^ ]+)\s+([^ ]+)\s+([^ ]+)\s+([^ ]+)\s*([^ ]*)/;
                    $uptime = $4;
                    $fps = $6;
                }
                $act_players  = $self->{numplayers};
                $max_players  = $self->{maxplayers};
                if ($max_players > 0) {
                    if ($act_players > $max_players)  {
                        $act_players = $max_players;
                    }
                }
                &::execCached("flush_server_load",
                    "INSERT IGNORE INTO hlstats_server_load
                        SET
                            server_id=?,
                            timestamp=?,
                            act_players=?,
                            min_players=?,
                            max_players=?,
                            map=?,
                            uptime=?,
                            fps=?",
                    $self->{id},
                    $new_timestamp,
                    $act_players,
                    $self->{minplayers},
                    $max_players,
                    $self->{map},
                    (($uptime)?$uptime:0),
                    (($fps)?$fps:0)
                );
                $self->set("track_server_timestamp", $new_timestamp);
				&::printEvent("SERVER", "Insert new server load timestamp", 1);
            }
        } else {
            $self->set("track_server_timestamp", $new_timestamp);
        }
    }
}

sub dostats 
{
	my ($self) = @_;
	my $rcon_obj = $self->{rcon_obj};
	$rcmd = $self->{broadcasting_command_announce};

	if (($::g_stdin == 0) && ($rcon_obj) && ($self->{rcon} ne ""))  
	{
		if ($self->{broadcasting_events} == 1)
		{
			my $hpk = sprintf("%.0f", 0);
			if ($self->{total_kills} > 0) {
				$hpk = sprintf("%.2f", (100/$self->{total_kills})*$self->{total_headshots});
			}  
			if ($rcmd ne "") {
				$self->dorcon("$rcmd ".$self->quoteparam("HLstatsX:CE - Tracking ".&::number_format($self->{players})." players with ".&::number_format($self->{total_kills})." kills and ".&::number_format($self->{total_headshots})." headshots ($hpk%)"));
			} else {
				$self->messageAll("HLstatsX:CE - Tracking ".&::number_format($self->{players})." players with ".&::number_format($self->{total_kills})." kills and ".&::number_format($self->{total_headshots})." headshots ($hpk%)");
			}  
		}  
	}  
}	  

sub get_map
{
	my ($self, $fromupdate) = @_;

	if ($::g_stdin == 0) {
		if ((time() - $self->{last_check})>120) {
			$self->{last_check} = time();
			&::printNotice("get_rcon_status");
			my $temp_map        = "";
			my $temp_maxplayers = -1;
			my $servhostname	  = "";
			my $mp_maxrounds      = 0;
			my $mp_timelimit      = 0;
			my $mp_halftime      = 0;
			my $difficulty      = 0;
			my $update		  = 0;
			
			if ($self->{rcon_obj})
			{
				($temp_map, $temp_maxplayers, $servhostname, $difficulty, $mp_maxrounds, $mp_timelimit, $mp_halftime) = $self->rcon_getStatus();
				
				if ($temp_map eq "") {
					goto STATUSFAIL;
				}
				
				if ($self->{map} ne $temp_map) {
					$self->{map} = $temp_map;
					$update++;
				}
			
				if (($temp_maxplayers != -1) && ($temp_maxplayers > 0) && ($temp_maxplayers ne "")) {
					if ($self->{maxplayers} != $temp_maxplayers) {
						$self->{maxplayers} = $temp_maxplayers;
						$update++;
					}
				}
				if (($difficulty > 0) && ($self->{play_game} == L4D())) {
					$self->{difficulty} = $difficulty;
				}
				if (
					$self->{play_game} == CSS() ||
					$self->{play_game} == CSGO() ||
					$self->{play_game} == CS2()
				) {
					# Zero is meaningful: map-specific settings commonly disable
					# halftime, round limits, or time limits. Do not carry a prior
					# map's values into the next map's balance/history decision.
					$self->{mp_timelimit} = $mp_timelimit;
					$self->{mp_maxrounds} = $mp_maxrounds;
					$self->{mp_halftime} = $mp_halftime;
				}
				if (($self->{update_hostname} > 0) && ($self->{name} ne $servhostname) && ($servhostname ne "")) {
						$self->{name} = $servhostname;
						$update++;
				}
			}
			else
			{  # no rcon or status command failed
			STATUSFAIL:
				my ($querymap, $queryhost, $querymax) = &::queryServer($self->{address}, $self->{port}, 'mapname', 'hostname', 'maxplayers');
				if ($querymap ne "") {
					$self->{map} = $querymap;
					$update++;
					
					#if map is blank, query likely failed as a whole
					
					if (($querymax != -1) && ($querymax > 0)) {
						if ($self->{maxplayers} != $querymax) {
							$self->{maxplayers} = $querymax;
							$update++;
						}
					}
					if ($self->{update_hostname} > 0 && $queryhost ne "" && $self->{name} ne $queryhost) {
						$self->{name} = $queryhost;
						$update++;
					}
				}
			}
				
			if ($update > 0 && $fromupdate == 0) {
				$self->updateDB();
			}
		  
			&::printNotice("get_rcon_status successfully");
		}
	}  
	return $self->{map};
}

sub refresh_map_balance_rules
{
	my ($self) = @_;
	my $rcon = $self->{rcon_obj};
	return 0 if !$rcon;

	# Clear first so a failed query cannot reuse the previous map's settings.
	$self->{mp_maxrounds} = 0;
	$self->{mp_timelimit} = 0;
	$self->{mp_halftime} = 0;

	my ($maxrounds, $timelimit, $halftime);
	my $ok = eval {
		$maxrounds = $rcon->getMaxRounds();
		$timelimit = $rcon->getTimeLimit();
		$halftime = $rcon->getHalfTime();
		1;
	};
	return 0 if !$ok || !defined($maxrounds) || !defined($timelimit) || !defined($halftime);

	$self->{mp_maxrounds} = $maxrounds + 0;
	$self->{mp_timelimit} = $timelimit + 0;
	$self->{mp_halftime} = $halftime + 0;
	return 1;
}


sub update_players_pings
{
	my ($self) = @_;

	if ($self->{num_trackable_players} < $self->{minplayers}) 
	{
		&::printNotice("(IGNORED) NOTMINPLAYERS: Update_player_pings");
	}
	else
	{
		&::printNotice("update_player_pings");
		&::printEvent("RCON", "Update Player pings", 1);
		my %players = $self->rcon_getplayers();
		while ( my($pl, $player) = each(%{$self->{srv_players}}) )
		{
			my $uniqueid = $player->{uniqueid};
			if (defined($players{$uniqueid})) 
			{
				if ($player->{is_bot} == 0 && $player->{userid} > 0) {
					my $ping = $players{$uniqueid}->{"Ping"};
					$player->set("ping", $ping);
					if ($ping > 0) {
						&::recordEvent(
							"Latency", 0,
							$player->{playerid},
							$ping
						);
					}
				}
			}
		}
		&::printNotice("update_player_pings successfully");
	}
}

sub get_lan_players
{
	my ($self) = @_;

	if ($::g_mode eq "LAN")  {
		&::printNotice("get_lan_players");
		&::printEvent("RCON", "Get LAN players", 1);
		my %players = $self->rcon_getplayers();
		while ( my($p_uid, $p_obj) = each(%players) )
		{	
			my $srv_addr = $self->{address}.":".$self->{port};
			my $userid   = $p_obj->{"UserID"};
			my $name     = $p_obj->{"Name"};
			my $address  = $p_obj->{"Address"};
			::g_lan_noplayerinfo->{"$srv_addr/$userid/$name"} = {
				ipaddress => $address,
				userid => $userid,
				name => $name,
				server => $srv_addr
			};
		}
		&::printNotice("get_lan_players successfully");
	}
}

sub clear_winner
{
  my ($self) = @_;
  &::printNotice("clear_winner");
  @{$self->{winner}} = ();
}

sub apply_team_balance_config_defaults
{
	my ($self, $config) = @_;
	my %defaults = (
		BalanceSwitchOnlyDead => 1,
		BalanceStartRounds => 7,
		BalanceRoundsDelay => 3,
		BalanceAnalyzeRounds => 7,
		BalanceMaxWins => 1,
		BalanceIgnoreBots => 0,
	);

	for my $key (keys %defaults) {
		if (!defined($config->{$key}) || $config->{$key} !~ /\S/) {
			$config->{$key} = $defaults{$key};
		}
	}
	if ($config->{BalanceAnalyzeRounds} !~ /^\d+$/ || $config->{BalanceAnalyzeRounds} < 1) {
		$config->{BalanceAnalyzeRounds} = $defaults{BalanceAnalyzeRounds};
	}

	return $config;
}

sub reset_team_balance
{
	my ($self) = @_;
	$self->clear_winner();
	$self->{ba_ct_wins} = 0;
	$self->{ba_ts_wins} = 0;
	$self->{ba_map_rounds} = 0;
	$self->{ba_last_swap} = 0;
	$self->{ba_player_switch} = 0;
	$self->{jtc_join_reservations} = {};
	$self->{ba_swapped_players} = {};
	$self->{ba_pending_swaps} = {};
	$self->{ba_swap_history_loaded} = 1;
	$self->{ba_round_orientation} = [];
	$self->{ba_eligible_ct_rounds} = 0;
	$self->{ba_eligible_ts_rounds} = 0;
	$self->{ba_fill_roster_signature} = undef;
	$self->{ba_fill_roster_stable_since} = 0;
	$self->{ba_balance_pending} = 0;
	$self->{ba_balance_due} = 0;
	$self->{ba_balance_deadline} = 0;
	$self->{ba_round_active} = 0;
	$self->{ba_roster_changed_at} = 0;
	$self->{ba_halftime_rotated} = 0;
	$self->{ba_round_bias_active} = 0;
	$self->{ba_round_bias_eligible} = 0;
	$self->{map_rounds} = 0;
}

sub load_balance_swap_history
{
	my ($self) = @_;
	$self->{ba_swapped_players} = {};
	$self->{ba_swap_history_loaded} = 0;
	return 0 if !$self->{map_started} || !$self->{id} || !$self->{map};

	my $result = eval {
		&::execCached(
			"get_map_team_balance_swaps",
			"SELECT uniqueId FROM hlstats_MapTeamBalanceSwaps WHERE serverId=? AND map=? AND mapStarted=?",
			$self->{id}, $self->{map}, $self->{map_started},
		);
	};
	if (!$result) {
		if (!$self->{ba_swap_history_warning}) {
			&::printEvent("TEAMS", "ATB swap history unavailable; human swaps are paused for safety", 1);
			$self->{ba_swap_history_warning} = 1;
		}
		return 0;
	}

	while (my ($uniqueid) = $result->fetchrow_array()) {
		$self->{ba_swapped_players}{"human:$uniqueid"} = 1 if defined($uniqueid) && $uniqueid ne "";
	}
	my $fetch_error = $result->err;
	my $finished = $result->finish;
	if ($fetch_error || !$finished) {
		$self->{ba_swapped_players} = {};
		return 0;
	}

	$self->{ba_swap_history_loaded} = 1;
	return 1;
}

sub schedule_team_balance
{
	my ($self, $now) = @_;
	return if $::g_stdin != 0 || $self->{ba_enabled} == 0;

	$self->{ba_round_active} = 0;
	$now = time() if !defined($now);
	my $max_wait = 15;
	my $fill_mode = $self->is_bot_fill_mode();
	if ($fill_mode) {
		my $delay = $self->dorcon("bot_join_delay");
		if (defined($delay) && $delay =~ /bot_join_delay[^\r\n]*?=\s*"?(\d+)/i
			&& $1 + 5 > $max_wait) {
			$max_wait = $1 + 5;
		}
	}
	$self->{ba_balance_pending} = 1;
	# Start during the round-end window; run_pending_team_balance() independently
	# waits for a stable fill roster and refuses to analyze once a round is live.
	$self->{ba_balance_due} = $now + 2;
	$self->{ba_balance_deadline} = $now + $max_wait;
}

sub note_balance_roster_change
{
	my ($self, $now) = @_;
	$now = time() if !defined($now);
	# Disqualify the current round if its roster changes after Round_Start.
	$self->{ba_round_bias_eligible} = 0 if $self->{ba_round_bias_active};
	$self->{ba_roster_changed_at} = $now;
	if ($self->{ba_balance_pending}) {
		my $new_due = $now + 2;
		$new_due = $self->{ba_balance_due}
			if $self->{ba_balance_due} > $now && $self->{ba_balance_due} > $new_due;
		$self->{ba_balance_due} = $new_due;
		$self->{ba_balance_due} = $self->{ba_balance_deadline}
			if $self->{ba_balance_due} > $self->{ba_balance_deadline};
	}
}

sub run_pending_team_balance
{
	my ($self, $now) = @_;
	return if !$self->{ba_balance_pending} || $now < $self->{ba_balance_due};
	return if $self->{ba_round_active};
	$self->expire_pending_balance_swaps($now);

	my $recent_change = $self->{ba_roster_changed_at}
		&& $now - $self->{ba_roster_changed_at} < 2;
	if ($recent_change || $self->bot_fill_roster_is_changing($now)) {
		$self->{ba_balance_due} = $now + 1;
		return;
	}

	$self->{ba_balance_pending} = 0;
	$self->analyze_teams();
}

sub bot_fill_roster_is_changing
{
	my ($self, $now) = @_;
	$now = time() if !defined($now);
	my $mode_result = $self->dorcon("bot_quota_mode") // "";
	my ($mode) = $mode_result =~ /bot_quota_mode[^\r\n]*?=\s*"?([a-z]+)/i;
	my $mode_known = defined($mode) && $mode =~ /^(?:normal|fill|match)$/i;
	my $dynamic_mode = defined($mode) && $mode =~ /^(?:fill|match)$/i;
	my $vacate_result = $self->dorcon("bot_auto_vacate") // "";
	my $auto_vacate = $vacate_result =~ /bot_auto_vacate[^\r\n]*?=\s*"?1\b/i;
	return 0 if !$dynamic_mode && !$auto_vacate && $mode_known;

	my @roster = sort map {
		my $player = $_;
		my $team = $player->{team} // "";
		join(":", $player->{is_bot} ? 1 : 0, $player->{userid} // 0, $team);
	} values %{$self->{srv_players}};
	my $quota = $self->dorcon("bot_quota") // "";
	my $delay_result = $self->dorcon("bot_join_delay") // "";
	my $settle_seconds = 12; # Fail safe if the server's bot delay cannot be read.
	if ($delay_result =~ /bot_join_delay[^\r\n]*?=\s*"?([0-9]+(?:\.[0-9]+)?)/i) {
		$settle_seconds = $1 + 2;
		$settle_seconds = 3 if $settle_seconds < 3;
	}
	my $signature = join("|", $mode_result, $vacate_result, $quota, $delay_result, @roster);

	if (!defined($self->{ba_fill_roster_signature})
		|| $self->{ba_fill_roster_signature} ne $signature) {
		$self->{ba_fill_roster_signature} = $signature;
		$self->{ba_fill_roster_stable_since} = $now;
		return 1;
	}

	return $now - ($self->{ba_fill_roster_stable_since} || $now) < $settle_seconds;
}

sub is_bot_fill_mode
{
	my ($self) = @_;
	my $mode = $self->dorcon("bot_quota_mode");
	return defined($mode) && $mode =~ /bot_quota_mode[^\r\n]*?\bfill\b/i;
}

sub mark_balance_player_swapped
{
	my ($self, $entry) = @_;
	my $key = $entry->[9] ? "bot:$entry->[8]" : "human:$entry->[1]";
	$self->{ba_swapped_players}{$key} = 1;
}

sub balance_player_key
{
	my ($self, $entry) = @_;
	return $entry->[9] ? "bot:$entry->[8]" : "human:$entry->[1]";
}

sub balance_player_swap_pending
{
	my ($self, $entry) = @_;
	return exists $self->{ba_pending_swaps}{$self->balance_player_key($entry)};
}

sub request_balance_player_swap
{
	my ($self, $entry, $counts_as_skill_balance) = @_;
	my $key = $self->balance_player_key($entry);
	return 0 if $self->balance_player_was_swapped($entry)
		|| exists $self->{ba_pending_swaps}{$key};
	return 0 if !$self->switch_player($entry->[8], $entry->[0]);

	$self->{ba_pending_swaps}{$key} = {
		team => $entry->[3] eq "CT" ? "TERRORIST" : "CT",
		expires => time() + 15,
		counts_as_skill_balance => $counts_as_skill_balance ? 1 : 0,
	};
	return 1;
}

sub confirm_balance_player_swap
{
	my ($self, $player) = @_;
	my $key = $player->{is_bot}
		? "bot:$player->{userid}"
		: "human:$player->{uniqueid}";
	my $pending = $self->{ba_pending_swaps}{$key};
	return 0 if !$pending || $player->{team} ne $pending->{team};
	if ($pending->{expires} <= time()) {
		delete $self->{ba_pending_swaps}{$key};
		return 0;
	}
	delete $self->{ba_pending_swaps}{$key};
	$self->{ba_swapped_players}{$key} = 1;
	$self->persist_balance_player_swap($player) if !$player->{is_bot};
	$self->{ba_last_swap} = $self->{balance_rounds_delay}
		if $pending->{counts_as_skill_balance};
	return 1;
}

sub persist_balance_player_swap
{
	my ($self, $player) = @_;
	my $uniqueid = $player->{uniqueid} // "";
	return 0 if $uniqueid eq "" || !$self->{map_started} || !$self->{map};
	my $query = sprintf(
		"INSERT IGNORE INTO hlstats_MapTeamBalanceSwaps " .
		"(serverId, map, mapStarted, uniqueId, swappedAt) VALUES (%d, '%s', %d, '%s', %d)",
		$self->{id}, &::quoteSQL($self->{map}), $self->{map_started},
		&::quoteSQL($uniqueid), time(),
	);
	my $stored = eval { defined(&::execNonQuery($query)) };
	if (!$stored) {
		$self->{ba_swap_history_loaded} = 0;
		if (!$self->{ba_swap_history_warning}) {
			&::printEvent("TEAMS", "ATB swap history could not be saved; human swaps are paused for safety", 1);
			$self->{ba_swap_history_warning} = 1;
		}
		return 0;
	}
	return 1;
}

sub expire_pending_balance_swaps
{
	my ($self, $now) = @_;
	for my $key (keys %{$self->{ba_pending_swaps}}) {
		delete $self->{ba_pending_swaps}{$key}
			if $self->{ba_pending_swaps}{$key}{expires} <= $now;
	}
}

sub balance_player_was_swapped
{
	my ($self, $entry) = @_;
	my $key = $entry->[9] ? "bot:$entry->[8]" : "human:$entry->[1]";
	return 1 if !$entry->[9] && !$self->{ba_swap_history_loaded};
	return $self->{ba_swapped_players}{$key} || 0;
}

sub begin_map_bias_round
{
	my ($self) = @_;
	$self->{ba_round_bias_active} = 1;
	$self->{ba_round_bias_eligible} = $self->map_bias_round_is_eligible() ? 1 : 0;
}

sub prepare_halftime_balance
{
	my ($self) = @_;
	return if !$self->{mp_halftime} || $self->{ba_halftime_rotated};

	my $half_by_rounds = $self->{mp_maxrounds} > 0
		&& $self->{map_rounds} >= int($self->{mp_maxrounds} / 2);
	my $half_by_time = $self->{mp_timelimit} > 0
		&& time() - $self->{map_started} >= ($self->{mp_timelimit} * 30);
	return if !$half_by_rounds && !$half_by_time;

	for my $i (0 .. $#{$self->{winner}}) {
		$self->{winner}[$i] = $self->{winner}[$i] eq "ct" ? "ts" : "ct"
			if defined($self->{winner}[$i]) && $self->{winner}[$i] ne "";
	}
	$self->{ba_halftime_rotated} = 1;
	$self->recount_balance_wins();
	$self->note_balance_roster_change();
}

sub archive_completed_map_team_results
{
	my ($self, $completed_map) = @_;
	my $rounds = $self->{map_ct_wins} + $self->{map_ts_wins};
	return 0 if !$completed_map || $self->{map_started} <= 0 || $rounds < 10;
	my $eligible_rounds = $self->{ba_eligible_ct_rounds} + $self->{ba_eligible_ts_rounds};
	my $eligible = $self->{mp_halftime} && $self->{ba_halftime_rotated}
		&& $eligible_rounds >= 10;
	my $ct_rounds = $eligible ? $self->{ba_eligible_ct_rounds} : $self->{map_ct_wins};
	my $ts_rounds = $eligible ? $self->{ba_eligible_ts_rounds} : $self->{map_ts_wins};

	my $query = sprintf(
		"INSERT IGNORE INTO hlstats_MapTeamBalanceMatches " .
		"(serverId, map, mapStarted, ct_rounds, ts_rounds, eligible) VALUES (%d, '%s', %d, %d, %d, %d)",
		$self->{id}, &::quoteSQL($completed_map), $self->{map_started},
		$ct_rounds, $ts_rounds, $eligible ? 1 : 0,
	);
	my $stored = eval { &::execNonQuery($query); 1 };
	if (!$stored && !$self->{ba_map_bias_storage_warning}) {
		&::printEvent("TEAMS", "Map side-bias history could not be saved; ATB will use raw round results", 1);
		$self->{ba_map_bias_storage_warning} = 1;
	}
	return $stored ? 1 : 0;
}

sub get_map_team_bias
{
	my ($self) = @_;
	my $no_bias = { active => 0, ct_rate => 0.5, ts_rate => 0.5, rounds => 0 };
	return $no_bias if !$self->{mp_halftime};
	my $result = eval {
		&::execCached(
			"get_map_team_balance_matches",
			"SELECT COALESCE(SUM(ct_rounds), 0), COALESCE(SUM(ts_rounds), 0), COUNT(DISTINCT mapStarted) " .
			"FROM hlstats_MapTeamBalanceMatches WHERE serverId=? AND map=? AND eligible=1 " .
			"AND ct_rounds + ts_rounds >= 10",
			$self->{id}, $self->{map},
		);
	};
	if (!$result) {
		if (!$self->{ba_map_bias_storage_warning}) {
			&::printEvent("TEAMS", "Map side-bias history unavailable; ATB will use raw round results", 1);
			$self->{ba_map_bias_storage_warning} = 1;
		}
		return $no_bias;
	}
	my ($ct_rounds, $ts_rounds, $matches) = $result->fetchrow_array();
	$ct_rounds ||= 0;
	$ts_rounds ||= 0;
	my $rounds = $ct_rounds + $ts_rounds;
	return $no_bias if $rounds < 100 || !$matches || $matches < 5;

	my $ct_rate = $ct_rounds / $rounds;
	my $ts_rate = $ts_rounds / $rounds;
	return $no_bias if abs($ct_rate - 0.5) < 0.15;

	return {
		active => 1,
		ct_rate => $ct_rate,
		ts_rate => $ts_rate,
		rounds => $rounds,
	};
}

sub get_map_adjusted_team_excess
{
	my ($self, $ct_wins, $ts_wins) = @_;
	my $bias = $self->get_map_team_bias();
	return (0, 0, 0) if !$bias->{active};

	my $expected_ct_wins = 0;
	my $rounds = 0;
	my $current_orientation = $self->{ba_halftime_rotated} ? 1 : 0;
	for my $i (0 .. $#{$self->{winner}}) {
		next if !defined($self->{winner}[$i]) || $self->{winner}[$i] eq "";
		my $round_orientation = $self->{ba_round_orientation}[$i] || 0;
		my $ct_group_played_ct = $round_orientation == $current_orientation;
		$expected_ct_wins += $ct_group_played_ct ? $bias->{ct_rate} : $bias->{ts_rate};
		$rounds++;
	}
	return (0, 0, 0) if !$rounds;

	my $expected_ts_wins = $rounds - $expected_ct_wins;
	return (
		$ct_wins - $expected_ct_wins,
		$ts_wins - $expected_ts_wins,
		1,
	);
}

sub recount_balance_wins
{
	my ($self) = @_;
	$self->{ba_ct_wins} = 0;
	$self->{ba_ts_wins} = 0;
	for (@{$self->{winner}}) {
		$self->{ba_ct_wins}++ if $_ eq "ct";
		$self->{ba_ts_wins}++ if $_ eq "ts";
	}
}

sub add_round_winner
{
	my ($self, $team) = @_;

	&::printNotice("add_round_winner");
	$self->increment("ba_map_rounds");
	$self->increment("map_rounds");
	$self->increment("rounds");
	$self->increment("total_rounds");
	if ($self->{ba_round_bias_active} && $self->{ba_round_bias_eligible}) {
		$self->increment($team eq "ct" ? "ba_eligible_ct_rounds" : "ba_eligible_ts_rounds");
	}
	$self->{ba_round_bias_active} = 0;
	$self->{ba_round_bias_eligible} = 0;

	my $analyze_rounds = $self->{balance_analyze_rounds};
	$analyze_rounds = 7 if !defined($analyze_rounds) || $analyze_rounds !~ /^\d+$/ || $analyze_rounds < 1;
	my $index = $self->{map_rounds} % $analyze_rounds;
	$self->{winner}[$index] = $team;
	$self->{ba_round_orientation}[$index] = $self->{ba_halftime_rotated} ? 1 : 0;
	$self->recount_balance_wins();
}

sub map_bias_round_is_eligible
{
	my ($self) = @_;
	my ($ct_humans, $ts_humans, $bots) = (0, 0, 0);
	for my $player (values %{$self->{srv_players}}) {
		if ($player->{is_bot}) {
			$bots++ if ($player->{team} // "") eq "CT"
				|| ($player->{team} // "") eq "TERRORIST";
			next;
		}
		$ct_humans++ if ($player->{team} // "") eq "CT";
		$ts_humans++ if ($player->{team} // "") eq "TERRORIST";
	}
	return !$bots && $ct_humans + $ts_humans >= $self->{minplayers}
		&& $ct_humans >= 2 && $ts_humans >= 2;
}

sub switch_player
{
	my ($self, $playerid, $name) = @_;
	return 0 if !$self->{swap_command};
	my $rcmd = $self->{player_command_hint};
	
	my $result = $self->dorcon($self->{swap_command}." ".$self->format_userid($playerid));
	return 0 if !defined($result);
	if ($self->{player_command_hint} eq "") {
		$rcmd = $self->{player_command};
	}
	if ($rcmd ne "") {
		$self->dorcon(sprintf("%s %s %s", $rcmd, $self->format_userid($playerid), $self->quoteparam("HLstatsX:CE - You were switched to balance teams")));
	}
	# if ($self->{player_admin_command} ne "") {
	# 	$self->dorcon(sprintf("%s %s",$self->{player_admin_command}, $self->quoteparam("HLstatsX:CE - $name was switched to balance teams")));
	# }
	return 1;
}

sub update_player_kd_cache
{
	my ($self, $player) = @_;
	return 0 if !$player || $self->{play_game} != CSS()
		|| $player->{is_bot} || $player->{userid} <= 0;

	my $uniqueid = $player->{uniqueid} // "";
	return 0 if $uniqueid !~ /^(?:STEAM_[0-5]:[01]:\d+|\[U:\d+:\d+\])$/i;

	return 0 if !$self->join_team_control_enabled();

	my $kills = $player->{total_kills} || 0;
	my $deaths = $player->{lifetime_deaths} || 0;
	return 0 if $kills !~ /^\d+$/ || $deaths !~ /^\d+$/;
	my $kd = $kills / ($deaths > 0 ? $deaths : 1);
	my $command = sprintf('sm_kd_set "%s" "%.3f" "21600"', $uniqueid, $kd);
	return defined($self->dorcon($command)) ? 1 : 0;
}

sub join_team_control_enabled
{
	my ($self) = @_;
	if (time() - $self->{jtc_enabled_checked_at} >= 30) {
		my $result = $self->dorcon("jtc_enabled");
		$self->{jtc_enabled_cached} = defined($result)
			&& $result =~ /"jtc_enabled"\s*=\s*"1"/ ? 1 : 0;
		$self->{jtc_enabled_checked_at} = time();
	}
	return $self->{jtc_enabled_cached};
}

sub join_team_kd_threshold
{
	my ($self) = @_;
	if (time() - $self->{jtc_threshold_checked_at} >= 30) {
		my $result = $self->dorcon("jtc_kd_threshold");
		if (defined($result) && $result =~ /jtc_kd_threshold[^\r\n]*?=\s*"?([0-9]+(?:\.[0-9]+)?)/i) {
			$self->{jtc_threshold_cached} = $1 + 0;
		}
		$self->{jtc_threshold_checked_at} = time();
	}
	return $self->{jtc_threshold_cached};
}

sub get_join_team_policy
{
	my ($self, $player) = @_;
	my $no_policy = { preferred_team => 0, kd_locked => 0 };
	return $no_policy if !$player;
	my $uniqueid = $player->{uniqueid} // "";
	delete $self->{jtc_join_reservations}{$uniqueid} if $uniqueid ne "";
	return $no_policy if $self->{play_game} != CSS()
		|| $player->{is_bot} || $player->{userid} <= 0
		|| !$self->join_team_control_enabled();

	if ($uniqueid !~ /^(?:STEAM_[0-5]:[01]:\d+|\[U:\d+:\d+\])$/i) {
		delete $self->{jtc_join_reservations}{$uniqueid};
		return $no_policy;
	}

	my $kills = $player->{total_kills} || 0;
	my $deaths = $player->{lifetime_deaths} || 0;
	my $kd_locked = 0;
	if ($kills =~ /^\d+$/ && $deaths =~ /^\d+$/) {
		my $kd = $kills / ($deaths > 0 ? $deaths : 1);
		$kd_locked = $kd >= $self->join_team_kd_threshold() ? 1 : 0;
	}

	my ($ct_count, $ts_count, $reserved_ct_count, $reserved_ts_count) = (0, 0, 0, 0);
	my $bot_fill_mode = $self->is_bot_fill_mode();
	for my $other (values %{$self->{srv_players}}) {
		next if $other->{userid} == $player->{userid}
			|| ($other->{uniqueid} // "") eq $uniqueid;
		# In fill mode bots are temporary quota occupants: a human connect can
		# remove one asynchronously. Balance human placements against humans and
		# pending reservations, then let round-end ATB settle the actual roster.
		next if $bot_fill_mode && $other->{is_bot};
		if (($other->{team} // "") eq "CT") {
			$ct_count++;
		} elsif (($other->{team} // "") eq "TERRORIST") {
			$ts_count++;
		} else {
			my $reserved_team = $self->{jtc_join_reservations}{$other->{uniqueid} // ""} || 0;
			if ($reserved_team == 3) {
				$ct_count++;
				$reserved_ct_count++;
			} elsif ($reserved_team == 2) {
				$ts_count++;
				$reserved_ts_count++;
			}
		}
	}

	my $preferred_team;
	if ($ts_count < $ct_count) {
		$preferred_team = 2;
	} elsif ($ct_count < $ts_count) {
		$preferred_team = 3;
	} elsif ($reserved_ts_count < $reserved_ct_count) {
		$preferred_team = 2;
	} elsif ($reserved_ct_count < $reserved_ts_count) {
		$preferred_team = 3;
	} elsif (($self->{map_ts_wins} || 0) < ($self->{map_ct_wins} || 0)) {
		$preferred_team = 2;
	} elsif (($self->{map_ct_wins} || 0) < ($self->{map_ts_wins} || 0)) {
		$preferred_team = 3;
	} elsif (($player->{team} // "") eq "TERRORIST") {
		$preferred_team = 2;
	} elsif (($player->{team} // "") eq "CT") {
		$preferred_team = 3;
	} else {
		$preferred_team = (int(rand(2)) == 0) ? 2 : 3;
	}

	my $player_team = uc($player->{team} // "");
	if ($player_team ne "CT" && $player_team ne "TERRORIST" && $player_team ne "SPECTATOR") {
		$self->{jtc_join_reservations}{$uniqueid} = $preferred_team;
	}
	return { preferred_team => $preferred_team, kd_locked => $kd_locked };
}

sub clear_join_team_reservation
{
	my ($self, $player) = @_;
	return if !$player;
	my $uniqueid = $player->{uniqueid} // "";
	delete $self->{jtc_join_reservations}{$uniqueid} if $uniqueid ne "";
}

sub debug_message
{
	my ($self, $message) = @_;

	if ($self->{player_events} == 1 && $self->{player_admin_command} ne "") {
		$cmd_str = $self->{player_admin_command}." [DEBUG] $message";
		$self->dorcon($cmd_str);
	}
}

sub analyze_teams
{
	my ($self) = @_;

	if ($::g_stdin != 0 || $self->{ba_enabled} == 0) {
		return;
	}

	# Count correction is independent of score history and can run after round 1.
	&::printNotice("analyze_teams");
	my $ts_skill     = 0;
	my $ts_avg_skill = 0;
	my $ts_count     = 0;
	my $ts_wins      = $self->{ba_ts_wins};
	my $ts_kills     = 0;
	my $ts_deaths    = 0;
	my $ts_diff      = 0;
	my @ts_players   = ();

	my $ct_skill     = 0;
	my $ct_avg_skill = 0;
	my $ct_count     = 0;
	my $ct_wins      = $self->{ba_ct_wins};
	my $ct_kills     = 0;
	my $ct_deaths    = 0;
	my $ct_diff      = 0;
	my @ct_players   = ();

	my $server_id   = $self->{id};
	while (my($pl, $player) = each(%{$self->{srv_players}}) )
	{
		if ($player->{is_bot} && $self->{balance_ignore_bots}) {
			next;
		}

		my @Player      = ( $player->{name},			#0
							$player->{uniqueid},		#1
							$player->{skill},   		#2
							$player->{team},			#3
							$player->{map_kills},		#4
							$player->{map_deaths},		#5
							($player->{map_kills}-$player->{map_deaths}), #6
							$player->{is_dead},			#7
							$player->{userid},			#8
							$player->{is_bot},			#9
							);
		if ($Player[3] eq "TERRORIST") {
			push(@{$ts_players[$ts_count]}, @Player);
			$ts_skill   += $Player[2];
			$ts_count   += 1;
			$ts_kills   += $Player[4];
			$ts_deaths  += $Player[5];
		} elsif ($Player[3] eq "CT") {
			push(@{$ct_players[$ct_count]}, @Player);
			$ct_skill   += $Player[2];
			$ct_count   += 1;
			$ct_kills   += $Player[4];
			$ct_deaths  += $Player[5];
		}
	}
	# Join reservations model humans who have connected but have not chosen a
	# playable team yet. Include them in parity decisions, but never as swap targets.
	for my $player (values %{$self->{srv_players}}) {
		next if $player->{is_bot};
		my $team = uc($player->{team} // "");
		next if $team eq "CT" || $team eq "TERRORIST" || $team eq "SPECTATOR";
		my $reserved_team = $self->{jtc_join_reservations}{$player->{uniqueid} // ""} || 0;
		$ct_count++ if $reserved_team == 3;
		$ts_count++ if $reserved_team == 2;
	}
	@ct_players = sort { $b->[6] <=> $a->[6]} @ct_players;
	@ts_players = sort { $b->[6] <=> $a->[6]} @ts_players;

	&::printEvent("TEAM", "Checking Teams", 1);

	$self->debug_message("HLstatsX:CE - ATB: CT ($ct_count) $ct_kills:$ct_deaths  [$ct_wins - $ts_wins] $ts_kills:$ts_deaths ($ts_count) TS");

	my $action_done = 0;
	# At or below the configured population threshold only correct team counts;
	# do not use round score to choose the players being moved.
	my $can_balance_by_score = $self->{num_trackable_players} > $self->{minplayers};
	my ($ct_excess, $ts_excess, $has_map_bias) = (0, 0, 0);
	if ($can_balance_by_score) {
		($ct_excess, $ts_excess, $has_map_bias) = $self->get_map_adjusted_team_excess($ct_wins, $ts_wins);
	}
	my $ct_underperforming = $has_map_bias
		? $ct_excess < -$self->{balance_max_wins}
		: $ct_wins <= $self->{balance_max_wins};
	my $ts_underperforming = $has_map_bias
		? $ts_excess < -$self->{balance_max_wins}
		: $ts_wins <= $self->{balance_max_wins};
	if ($self->{ba_last_swap} > 0) {
		$self->{ba_last_swap}--;
	}

	# Balance based on team count.
	# If smaller team is also losing, give them best enemy player(s). Otherwise just give them worst player(s) to even counts.
	my $needed_players = 0;
	my @possible_players = ();
	my $count_as_skill_balance = 0;

	if ($ct_count + 1 < $ts_count) {
		$needed_players = floor( ($ts_count - $ct_count) / 2);
		if ($can_balance_by_score && $ct_underperforming) {
			my @bots = $bot_fill_mode ? grep { $_->[9] } @ts_players : ();
			my @humans = $bot_fill_mode ? grep { !$_->[9] } @ts_players : @ts_players;
			@possible_players = (
				sort { $a->[6] <=> $b->[6] } @bots,
				sort { $b->[6] <=> $a->[6] } @humans,
			); # prefer a fill bot before moving a strong human
			$count_as_skill_balance = 1;
		} else {
			if ($bot_fill_mode) {
				my @bots = sort { $a->[6] <=> $b->[6] } grep { $_->[9] } @ts_players;
				my @humans = sort { $a->[6] <=> $b->[6] } grep { !$_->[9] } @ts_players;
				@possible_players = (@bots, @humans);
				shift(@possible_players) if !@bots; # retain the legacy human-protection rule
			} else {
				@possible_players = sort { $a->[6] <=> $b->[6]} @ts_players;
				shift(@possible_players);
			}
		}
	} elsif ($ts_count + 1 < $ct_count) {
		$needed_players = floor( ($ct_count - $ts_count) / 2);
		if ($can_balance_by_score && $ts_underperforming) {
			my @bots = $bot_fill_mode ? grep { $_->[9] } @ct_players : ();
			my @humans = $bot_fill_mode ? grep { !$_->[9] } @ct_players : @ct_players;
			@possible_players = (
				sort { $a->[6] <=> $b->[6] } @bots,
				sort { $b->[6] <=> $a->[6] } @humans,
			);
			$count_as_skill_balance = 1;
		} else {
			if ($bot_fill_mode) {
				my @bots = sort { $a->[6] <=> $b->[6] } grep { $_->[9] } @ct_players;
				my @humans = sort { $a->[6] <=> $b->[6] } grep { !$_->[9] } @ct_players;
				@possible_players = (@bots, @humans);
				shift(@possible_players) if !@bots;
			} else {
				@possible_players = sort { $a->[6] <=> $b->[6]} @ct_players;
				shift(@possible_players);
			}
		}
	}

	foreach my $entry (@possible_players) {
		if ($needed_players == 0) {
			last;
		}

		if ((@{$entry}[7] == 0 && $self->{balance_switch_only_dead}) || ($self->is_admin(@{$entry}[1]) == 1 && !$self->{switch_admins})) {
			next;
		}

		if ($self->balance_player_was_swapped($entry) || $self->balance_player_swap_pending($entry)) {
			next;
		}
		$self->messageAll("HLstatsX:CE - ATB - Player [" . @{$entry}[3] . "] " . @{$entry}[0] . " was switched to ensure an even team count.", 0, 1);
		if ($self->request_balance_player_swap(
			$entry, $count_as_skill_balance && !$entry->[9]
		)) {
			$action_done++;
			$needed_players--;
		}
	}

	if ($action_done) {
		return;
	}
	# End of balance based on team count.

	# Balance based on frags.
	if (!$can_balance_by_score || $self->{ba_last_swap} > 0 || $self->{map_rounds} < $self->{balance_start_rounds}) {
		return;
	}

	my $need_ct = 0;
	my $need_ts = 0;
	if ($ct_underperforming) {
		$need_ct = $ct_count < $ts_count ? 0 : 1;
		$need_ts = 1;

		@ts_players = sort { $b->[6] <=> $a->[6]} @ts_players;  # best player
		shift(@ts_players); # remove first best
		@ct_players = sort { $a->[6] <=> $b->[6]} @ct_players;  # worst player
	} elsif ($ts_underperforming) {
		$need_ct = 1;
		$need_ts = $ts_count < $ct_count ? 0 : 1;

		@ts_players = sort { $a->[6] <=> $b->[6]} @ts_players;  # worst player
		@ct_players = sort { $b->[6] <=> $a->[6]} @ct_players;  # best player
		shift(@ct_players); # remove first best
	}

	my @ct_switch_player = ();
	my @ts_switch_player = ();

	foreach my $entry (@ct_players) {
		if ($self->balance_player_was_swapped($entry)
			|| $self->balance_player_swap_pending($entry)
			|| ($bot_fill_mode && $entry->[9])
			|| (@{$entry}[7] == 0 && $self->{balance_switch_only_dead})
			|| ($self->is_admin(@{$entry}[1]) == 1 && !$self->{switch_admins})) {
			next;
		}

		@ct_switch_player = @{$entry};
		last;
	}

	foreach my $entry (@ts_players) {
		if ($self->balance_player_was_swapped($entry)
			|| $self->balance_player_swap_pending($entry)
			|| ($bot_fill_mode && $entry->[9])
			|| (@{$entry}[7] == 0 && $self->{balance_switch_only_dead})
			|| ($self->is_admin(@{$entry}[1]) == 1 && !$self->{switch_admins})) {
			next;
		}

		@ts_switch_player = @{$entry};
		last;
	}

	if ($need_ct && @ct_switch_player && $need_ts && @ts_switch_player) {
		$self->messageAll("HLstatsX:CE - ATB - Switching players [CT] " . @ct_switch_player[0] . " ⇆ [T] " . @ts_switch_player[0] . " to balance teams.", 0, 1);
		my $ct_requested = $self->request_balance_player_swap(\@ct_switch_player, 1);
		my $ts_requested = $self->request_balance_player_swap(\@ts_switch_player, 1);
		return if !$ct_requested && !$ts_requested;
	} elsif ($need_ct && @ct_switch_player && !$need_ts) {
		$self->messageAll("HLstatsX:CE - ATB - Player [CT] " . @ct_switch_player[0] . " was switched to balance teams.", 0, 1);
		return if !$self->request_balance_player_swap(\@ct_switch_player, 1);
	} elsif ($need_ts && @ts_switch_player && !$need_ct) {
		$self->messageAll("HLstatsX:CE - ATB - Player [T] " . @ts_switch_player[0] . " was switched to balance teams.", 0, 1);
		return if !$self->request_balance_player_swap(\@ts_switch_player, 1);
	} else {
		# No players found or needed to switch.
		return;
	}

	# End of balance based on frags.
}

#
# Marks server as needing flush
#

sub updateDB
{
	my ($self) = @_;
	$self->{needsupdate} = 1;
}

#
# Flushes server information in database
#

sub flushDB
{
	my ($self) = @_;
   	$self->get_map(1);
	
	my $serverid      = $self->{id};

    if ($self->{total_kills} == 0)
    {
		my $result = &::execCached(
			"get_server_player_info",
			"SELECT
				kills,
				headshots,
				suicides,
				rounds,
				ct_shots+ts_shots as shots,
				ct_hits+ts_hits as hits
			FROM
				hlstats_Servers
			WHERE
				serverId=?",
			$self->{id}
			);
		($self->{total_kills}, $self->{total_headshots}, $self->{total_suicides},$self->{total_rounds},$self->{total_shots},$self->{total_hits}) = $result->fetchrow_array();
		$result->finish;
	}   

	my $game = $self->{game};
	my $now = time;
	my $cached_count = $player_count_cache{$game};
	if ($cached_count && $now >= $cached_count->{time}
		&& $now - $cached_count->{time} < 5)
	{
		$self->{players} = $cached_count->{players};
	}
	else
	{
		# Invalidate before querying so failed refreshes cannot retain stale counts.
		delete $player_count_cache{$game};
		my $result = &::execCached(
			"get_player_count",
			"SELECT count(*) as players FROM hlstats_Players WHERE game=? and hideranking<>2",
			&::quoteSQL($game));
		$self->{players} = $result->fetchrow_array();
		my $fetch_error = $result->err;
		my $finished = $result->finish;
		if (defined($self->{players}) && !$fetch_error && $finished)
		{
			$player_count_cache{$game} = { time => $now, players => $self->{players} };
		}
	}
	
	
	# Update player details
	my $query = "
		UPDATE
			hlstats_Servers
		SET  
			name=?,
			rounds=rounds + ?,
			kills=kills + ?,
			suicides=suicides + ?,
			headshots=headshots + ?,
			bombs_planted=bombs_planted + ?,
			bombs_defused=bombs_defused + ?,
			players=?,
			ct_wins=ct_wins + ?,
			ts_wins=ts_wins + ?,
			act_players=?,
			max_players=?,
			act_map=?,
			map_rounds=?,
			map_ct_wins=?,
			map_ts_wins=?,
			map_started=?,
			map_changes=map_changes + ?,
			ct_shots=ct_shots + ?,
			ct_hits=ct_hits + ?,
			ts_shots=ts_shots + ?,
			ts_hits=ts_hits + ?,
			map_ct_shots=?,
			map_ct_hits=?,
			map_ts_shots=?,
			map_ts_hits=?,
			last_event=?
		WHERE
			serverId=?
	";
	my @vals = (
		&::quoteSQL($self->{name}),
		$self->{rounds},
		$self->{kills},
		$self->{suicides},
		$self->{headshots},
		$self->{bombs_planted},
		$self->{bombs_defused},
		$self->{players},
		$self->{ct_wins},
		$self->{ts_wins},
		$self->{numplayers},
		$self->{maxplayers},
		&::quoteSQL($self->{map}),
		$self->{map_rounds},
		$self->{map_ct_wins},
		$self->{map_ts_wins},
		$self->{map_started},
		$self->{map_changes},
		$self->{ct_shots},
		$self->{ct_hits},
		$self->{ts_shots},
		$self->{ts_hits},
		$self->{map_ct_shots},
		$self->{map_ct_hits},
		$self->{map_ts_shots},
		$self->{map_ts_hits},
		$::ev_unixtime,
		$serverid
	);
	&::execCached("update_server_stats", $query, @vals);

	$self->set("rounds", 0);
	$self->set("kills", 0);
	$self->set("suicides", 0);
	$self->set("headshots", 0);
	$self->set("bombs_planted", 0);
	$self->set("bombs_defused", 0);
	$self->set("ct_wins", 0);
	$self->set("ts_wins", 0);
	$self->set("ct_shots", 0);
	$self->set("ct_hits", 0);
	$self->set("ts_shots", 0);
	$self->set("ts_hits", 0);
	$self->set("map_changes", 0);
	$self->{needsupdate} = 0;
}

sub flush_player_count
{
	my ($self) = @_;
	
	&::execCached("flush_plyr_cnt",
		"UPDATE hlstats_Servers SET act_players=? WHERE serverId=?",
		$self->{numplayers},
		$self->{id}
	);
}

sub update_server_loc
{
	my ($self)      = @_;
	my $serverid    = $self->{id};
    my $server_ip   = $self->{address};
	my $publicaddress = $self->{publicaddress};

	if ($publicaddress =~ /^(\d+\.\d+\.\d+\.\d+)/) {
		$server_ip = $publicaddress;
	} elsif ($publicaddress =~ /^([0-9a-zA-Z\-\.]+)\:*.*/) {
		my $hostip = inet_aton($1);
		if ($hostip) {
			$server_ip = inet_ntoa($hostip);
		}
	}
	my $found = 0;
	my $servcity = undef;
	my $servcountry = undef;
	my $servlat=undef;
	my $servlng=undef;
	if ($::g_geoip_binary > 0) {
		if(!defined($::g_gi)) {
			return;
		}

		my $geoCity = undef;

		try { $geoCity = $::g_gi->city( ip => $server_ip ); }
		catch { $geoCity = undef; };

		if ($geoCity) {

			my $geoCityRec = $geoCity->city();
			my $geoCountryRec = $geoCity->country();
			my $geoLocationRec = $geoCity->location();
			my $geoPostalRec = $geoCity->postal();
			my $geoMostSpecificSubdivision = $geoCity->most_specific_subdivision();

			$country_code = $geoCountryRec->iso_code();
			$country_name = $geoCountryRec->name();
			$region = $geoMostSpecificSubdivision->name();
			$city = $geoCityRec->name();
			$postal_code = $geoPostalRec->code();
			$latitude = $geoLocationRec->latitude();
			$longitude = $geoLocationRec->longitude();
		}			
		
		if ($longitude) {

			$found++;
			$servcity = ((defined($city))?encode("utf8",$city):"");
			$servcountry = ((defined($country_name))?encode("utf8",$country_name):"");
			$servlat = $latitude;
			$servlng = $longitude;
		}
	
	} else {
		my @ipp = split (/\./,$server_ip);
		my $ip_number = $ipp[0]*16777216+$ipp[1]*65536+$ipp[2]*256+$ipp[3];
		my $query = "
			SELECT locId FROM geoLiteCity_Blocks WHERE startIpNum<= $ip_number AND endIpNum>= $ip_number LIMIT 1";
		my $result = &::doQuery($query);
		if ($result->rows > 0) {
			my $locid = $result->fetchrow_array;
			$result->finish;
			my $query = "
				SELECT
					city,
					name AS country,
					latitude AS lat,
					longitude AS lng
				FROM
					geoLiteCity_Location a 
				INNER JOIN
					hlstats_Countries b ON a.country=b.flag
				WHERE
					locId= $locid
				LIMIT 1";
			my $result = &::doQuery($query);
			if ($result->rows > 0) {
				$found++;
				($servcity,$servcountry,$servlat,$servlng) = $result->fetchrow_array;
				$result->finish;
			}
		}
	}
	if ($found > 0) {
		my $query = "
			UPDATE
				`hlstats_Servers`
			SET
				city = '".(defined($servcity)?&::quoteSQL($servcity):"")."',
				country='".(defined($servcountry)?&::quoteSQL($servcountry):"")."',
				lat=".((defined($servlat))?$servlat:"NULL").",
				lng=".((defined($servlng))?$servlng:"NULL")."
			WHERE
				serverId =$serverid
		";
		&::execNonQuery($query);
	}
}

sub messageAll
{
	my($self, $msg, $noshow, $force) = @_;
	
	if ($self->{broadcasting_events} == 1 || $force == 1)
	{
		if ($self->{mod} eq "SOURCEMOD" || $self->{mod} eq "AMXX")
		{
			my @userlist;

			foreach $player (values(%{$self->{srv_players}}))
			{
				if (($player->{is_bot} == 0) && ($player->{userid} > 0) && ($player->{playerid} != $noshow) && ($player->{display_events} == 1 || $force == 1))
				{
					push(@userlist, $player->{userid});
				}
			}

			if ($self->{play_game} != FF())
			{
				$msg = $self->{format_action}.$msg;
			}
			$self->messageMany($msg, 1, @userlist);
		}
		else
		{
			$self->dorcon("say ".$msg);
		}
	}
}

sub messageMany
{
	my($self, $msg, $toall, @userlist) = @_;
	if (scalar(@userlist) > 0)
	{
		if ($self->{mod} eq "SOURCEMOD")
		{
			my $usersendlist = "";
			foreach (@userlist)
			{
				$usersendlist .= $_.",";
			}
			$usersendlist =~ s/,$//;
			my $color = $self->{format_color};
			if ($toall > 0 && $color eq " 1")
			{
				$color = " 2";
			}
			$self->dorcon($self->{player_command}." \"$usersendlist\"$color ".$self->quoteparam($msg));
		}
		elsif ($self->{mod} eq "AMXX")
		{
			while (@userlist)
			{
				my $usersendlist = "";
				for ($i = 0; $i < 8; $i++)
				{
					$usersendlist .= shift(@userlist);
					if ($i < 7)
					{
						$usersendlist .= ",";
					}
				}
				$self->dorcon("hlx_amx_bulkpsay \"$usersendlist\"".$self->{format_color}." ".$self->quoteparam($msg));
			}
		}
		else
		{
			$rcmd = $self->{broadcasting_command};
			foreach (@userlist)
			{
				$self->dorcon(sprintf("%s %s%s %s",$rcmd, $self->format_userid($_), $self->{format_color}, $self->quoteparam($msg)));
			}
		}
	}
}


sub setHlxCvars
{
	my ($self) = @_;

	if ($self->{hlstats_url} ne "")
	{
		$self->dorcon("hlxce_webpage \"".$self->{hlstats_url}."\"");
	}
	$self->dorcon("hlxce_version \"".$::g_version."\"");
	
	if ($self->{play_game} eq "MANI" && $self->dorcon("mani_hlx_prefix" =~ /gameme/i))
	{
		$self->dorcon("mani_hlx_prefix \"HLstatsX\"");
	}
}

sub updatePlayerCount
{
	my ($self) = @_;
	
	if ($::g_debug > 1) {
		&::printEvent("SERVER","Updating Player Count");
	}
	
	my $trackable = 0;

	if ($self->{play_game} == L4D()) {
		my $num = 0;
		while (my($pl, $player) = each(%{$self->{srv_players}})) {
			$player->updateTrackable();
			if ($player->{trackable} == 1) {
				$trackable++;
			}
			if ($player->{userid} > 0) {
				$num++;
			}
		}
		$self->{numplayers} = $num;
		$self->{num_trackable_players} = $trackable;
	} else {
		$self->{numplayers} = scalar keys %{$self->{srv_players}};
		while (my($pl, $player) = each(%{$self->{srv_players}})) {
			$player->updateTrackable();
			if ($player->{trackable} == 1) {
				$trackable++;
			}
		}
		$self->{num_trackable_players} = $trackable;
	}
	
	$self->flush_player_count();
}

1;
