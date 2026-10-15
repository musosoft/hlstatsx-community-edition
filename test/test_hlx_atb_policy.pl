#!/usr/bin/env perl
use strict;
use warnings;
use Test::More;
use FindBin;

our $opt_libdir = "$FindBin::Bin/../src/scripts";

require "$::opt_libdir/HLstats_Server.pm";
my $real_get_map_team_bias = \&HLstats_Server::get_map_team_bias;
open my $event_handlers, '<', "$::opt_libdir/HLstats_EventHandlers.plib"
	 or die "Cannot read HLstats event handlers: $!";
my $event_source = do { local $/; <$event_handlers> };
close $event_handlers;
my ($connect_handler) = $event_source =~ /sub doEvent_Connect\s*\{(.*?)^\}/ms;
die "Cannot find doEvent_Connect handler" if !defined($connect_handler);
like($connect_handler,
	qr/clear_join_team_reservation\(\$player\).*?get_join_team_policy\(\$player\)/s,
	"Connect restores a pre-team-selection reservation after clearing stale state");

our $g_stdin = 0;
our $g_rcon = 1;

sub main::printNotice { }
sub main::printEvent { }
sub main::quoteSQL {
	my ($value) = @_;
	$value =~ s/\\/\\\\/g;
	$value =~ s/'/\\'/g;
	return $value;
}

{
	package TestRcon;
	sub execute {
		my ($self, $command) = @_;
		return '"bot_quota_mode" = "fill"' if $command eq "bot_quota_mode";
		return '"bot_quota" = "6"' if $command eq "bot_quota";
		return '"bot_join_delay" = "10"' if $command eq "bot_join_delay";
		return '"bot_auto_vacate" = "0"' if $command eq "bot_auto_vacate";
		return "";
	}
}

{
	package TestMapRules;
	sub execute { return '"bot_quota_mode" = "normal"'; }
	sub getMaxRounds { return $_[0]->{rounds}; }
	sub getTimeLimit { return $_[0]->{timelimit}; }
	sub getHalfTime { return $_[0]->{halftime}; }
}

{
	package TestStatement;
	sub fetchrow_array { return @{$_[0]->{row}}; }
	sub err { return 0; }
	sub finish { return 1; }
}

{
	package TestRows;
	sub fetchrow_array {
		my ($self) = @_;
		$self->{index} ||= 0;
		return if $self->{index} >= @{$self->{rows}};
		return @{$self->{rows}[$self->{index}++]};
	}
	sub err { return 0; }
	sub finish { return 1; }
}

{
	no warnings qw(redefine once);
	*HLstats_Server::messageAll = sub { };
	*HLstats_Server::is_admin = sub { return 0 };
	*HLstats_Server::get_map_team_bias = sub {
		return { active => 0, ct_rate => 0.5, ts_rate => 0.5, rounds => 0 };
	};
	*HLstats_Server::switch_player = sub {
		my ($self, $userid) = @_;
		for my $player (values %{$self->{srv_players}}) {
			if ($player->{userid} == $userid) {
				$player->{team} = $player->{team} eq "CT" ? "TERRORIST" : "CT";
				push @{$self->{test_switches}}, $userid;
				push @{$self->{test_swap_acks}}, $player;
				return 1;
			}
		}
	};
}

sub acknowledge_test_swaps {
	my ($server) = @_;
	for my $player (@{$server->{test_swap_acks} || []}) {
		$server->confirm_balance_player_swap($player);
	}
	$server->{test_swap_acks} = [];
}

sub new_server {
	return bless {
		ba_enabled => 1,
		jtc_join_reservations => {},
		rcon => "test",
		rcon_obj => bless({}, "TestRcon"),
		minplayers => 4,
		num_trackable_players => 0,
		maxplayers => 32,
		srv_players => {},
		ba_swapped_players => {},
		ba_pending_swaps => {},
		ba_swap_history_loaded => 1,
		ba_swap_history_warning => 0,
		ba_round_orientation => [],
		ba_round_bias_active => 0,
		ba_round_bias_eligible => 0,
		ba_eligible_ct_rounds => 0,
		ba_eligible_ts_rounds => 0,
		ba_fill_roster_signature => undef,
		ba_fill_roster_stable_since => 0,
		ba_balance_pending => 0,
		ba_balance_due => 0,
		ba_balance_deadline => 0,
		ba_round_active => 0,
		ba_roster_changed_at => 0,
		mp_halftime => 1,
		mp_maxrounds => 25,
		mp_timelimit => 0,
		map_started => time(),
		map => "de_dust2",
		id => 7,
		map_rounds => 12,
		winner => ["ct", "ct", "ts", ""],
		ba_ct_wins => 2,
		ba_ts_wins => 1,
		player_events => 0,
		player_admin_command => "",
	}, "HLstats_Server";
}

my $server = new_server();
is_deeply(
	$server->apply_team_balance_config_defaults({
		BalanceSwitchOnlyDead => "",
		BalanceStartRounds => "3",
		BalanceRoundsDelay => "",
		BalanceAnalyzeRounds => undef,
		BalanceMaxWins => "",
		BalanceIgnoreBots => "",
	}),
	{
		BalanceSwitchOnlyDead => 1,
		BalanceStartRounds => "3",
		BalanceRoundsDelay => 3,
		BalanceAnalyzeRounds => 7,
		BalanceMaxWins => 1,
		BalanceIgnoreBots => 0,
	},
	"empty DB balance settings inherit documented defaults while configured values remain intact",
);
my $explicit_balance = {
	BalanceSwitchOnlyDead => "0",
	BalanceStartRounds => "0",
	BalanceRoundsDelay => "0",
	BalanceAnalyzeRounds => "2",
	BalanceMaxWins => "0",
	BalanceIgnoreBots => "1",
};
$server->apply_team_balance_config_defaults($explicit_balance);
is_deeply($explicit_balance, {
	BalanceSwitchOnlyDead => "0",
	BalanceStartRounds => "0",
	BalanceRoundsDelay => "0",
	BalanceAnalyzeRounds => "2",
	BalanceMaxWins => "0",
	BalanceIgnoreBots => "1",
}, "explicit zero and bot-ignore settings are preserved");
is($server->apply_team_balance_config_defaults({ BalanceAnalyzeRounds => "0" })->{BalanceAnalyzeRounds}, 7,
	"non-positive analysis window falls back instead of causing modulo by zero");
{
	no warnings qw(redefine once);
	my @commands;
	local *HLstats_Server::dorcon = sub {
		my ($self, $command) = @_;
		push @commands, $command;
		return '"jtc_enabled" = "1"' if $command eq "jtc_enabled";
		return "ok";
	};
	$server->{play_game} = 0; # CSS() constant
	my $player = {
		uniqueid => "STEAM_0:1:42",
		userid => 7,
		is_bot => 0,
		total_kills => 44,
		lifetime_deaths => 22,
	};
	ok($server->update_player_kd_cache($player),
		"HLstatsX publishes a human's persistent KD when join-team control is enabled");
	like($commands[-1], qr/^sm_kd_set "STEAM_0:1:42" "2\.000" "21600"$/,
		"the cache receives lifetime KD, Steam identity, and a bounded six-hour TTL");
	$player->{total_kills} = 9;
	$player->{lifetime_deaths} = 0;
	ok($server->update_player_kd_cache($player),
		"zero lifetime deaths use a denominator of one rather than erasing a strong KD");
	like($commands[-1], qr/"9\.000"/, "zero-death KD remains eligible for the join lock");
	$player->{is_bot} = 1;
	my $commands_before_bot = scalar @commands;
	ok(!$server->update_player_kd_cache($player) && scalar @commands == $commands_before_bot,
		"bots never receive player KD cache entries");
	$player->{is_bot} = 0;
	$player->{uniqueid} = 'invalid";sm_fake_command';
	my $commands_before_invalid = scalar @commands;
	ok(!$server->update_player_kd_cache($player) && scalar @commands == $commands_before_invalid,
		"untrusted identifiers cannot be injected into the KD RCON command");
}
{
	no warnings qw(redefine once);
	local *HLstats_Server::dorcon = sub {
		my ($self, $command) = @_;
		return '"jtc_enabled" = "1"' if $command eq "jtc_enabled";
		return '"jtc_kd_threshold" = "2.0"' if $command eq "jtc_kd_threshold";
		return "";
	};
	$server->{play_game} = 0;
	$server->{jtc_enabled_checked_at} = 0;
	$server->{jtc_threshold_checked_at} = 0;
	$server->{map_ct_wins} = 4;
	$server->{map_ts_wins} = 8;
	my $joiner = {
		uniqueid => "STEAM_0:1:42", userid => 7, is_bot => 0,
		total_kills => 44, lifetime_deaths => 22, team => "TERRORIST",
	};
	$server->{srv_players} = {
		7 => $joiner,
		8 => { uniqueid => "STEAM_0:0:1", userid => 8, is_bot => 0, team => "CT" },
		9 => { uniqueid => "BOT", userid => 9, is_bot => 1, team => "TERRORIST" },
		10 => { uniqueid => "STEAM_0:0:2", userid => 10, is_bot => 0, team => "CT" },
	};
	my $policy = $server->get_join_team_policy($joiner);
	is_deeply($policy, { preferred_team => 2, kd_locked => 1 },
		"backend prefers the smaller team for a qualifying player, excluding that player from counts");
	$joiner->{total_kills} = 20;
	$joiner->{lifetime_deaths} = 20;
	$joiner->{team} = "Unassigned";
	$server->{jtc_enabled_checked_at} = 0;
	$policy = $server->get_join_team_policy($joiner);
	is_deeply($policy, { preferred_team => 2, kd_locked => 0 },
		"below-threshold joiners still receive a balanced placement without a KD lock");
	is($server->{jtc_join_reservations}{$joiner->{uniqueid}}, 2,
		"non-locked joiners reserve their preferred team until selection");
	$joiner->{team} = "SPECTATOR";
	$server->{jtc_join_reservations}{$joiner->{uniqueid}} = 2;
	$server->{jtc_enabled_checked_at} = 0;
	$policy = $server->get_join_team_policy($joiner);
	ok($policy->{preferred_team} && !$policy->{kd_locked}
		&& !exists $server->{jtc_join_reservations}{$joiner->{uniqueid}},
		"spectators receive a future placement but do not reserve a playable slot");
}
{
	no warnings qw(redefine once);
	local *HLstats_Server::dorcon = sub {
		my ($self, $command) = @_;
		return '"jtc_enabled" = "1"' if $command eq "jtc_enabled";
		return '"jtc_kd_threshold" = "2.0"' if $command eq "jtc_kd_threshold";
		return "";
	};
	$server->{play_game} = 0;
	$server->{jtc_enabled_checked_at} = 0;
	$server->{jtc_threshold_checked_at} = 0;
	$server->{map_ct_wins} = 0;
	$server->{map_ts_wins} = 0;
	$server->{srv_players} = {
		1 => { uniqueid => "BOT", userid => 1, is_bot => 1, team => "CT" },
		2 => { uniqueid => "STEAM_0:0:2", userid => 2, is_bot => 0, team => "CT" },
		3 => { uniqueid => "STEAM_0:1:3", userid => 3, is_bot => 0, team => "TERRORIST" },
	};
	$server->{jtc_join_reservations} = {};
	my @joiners = map {
		{
			uniqueid => "STEAM_0:1:" . (100 + $_), userid => 100 + $_,
			is_bot => 0, total_kills => 10, lifetime_deaths => 10,
			team => "Unassigned",
		}
	} 1 .. 4;
	$server->{srv_players}{$_->{userid}} = $_ for @joiners;
	my @teams = map { $server->get_join_team_policy($_)->{preferred_team} } @joiners;
	ok(join("", @teams) eq "2323" || join("", @teams) eq "3232",
		"pending join reservations balance a fill-mode burst independently of transient bot teams");
	$server->clear_join_team_reservation($joiners[0]);
	ok(!exists $server->{jtc_join_reservations}{$joiners[0]{uniqueid}},
		"team selection clears the pending join reservation");
	$server->reset_team_balance();
	is_deeply($server->{jtc_join_reservations}, {},
		"map balance reset clears pending join reservations");
}
{
	no warnings qw(redefine once);
	local *HLstats_Server::get_map_team_bias = $real_get_map_team_bias;
	local *main::execCached = sub {
		return bless { row => [80, 20, 5] }, "TestStatement";
	};
	my $bias = $server->get_map_team_bias();
	ok($bias->{active} && $bias->{rounds} == 100 && $bias->{ct_rate} == 0.8,
		"strong side bias activates only after 100 eligible rounds across five matches");
	local *main::execCached = sub {
		return bless { row => [80, 20, 4] }, "TestStatement";
	};
	$bias = $server->get_map_team_bias();
	ok(!$bias->{active}, "four eligible matches are insufficient to activate map bias");
	local *main::execCached = sub {
		return bless { row => [58, 42, 5] }, "TestStatement";
	};
	$bias = $server->get_map_team_bias();
	ok(!$bias->{active}, "small side-rate differences do not create an over-map bias");
	local *main::execCached = sub {
		return bless { row => [30, 29, 5] }, "TestStatement";
	};
	$bias = $server->get_map_team_bias();
	ok(!$bias->{active}, "map bias waits until at least 100 eligible rounds");
}
{
	no warnings qw(redefine once);
	local *HLstats_Server::get_map_team_bias = $real_get_map_team_bias;
	local *main::execCached = sub { die "non-halftime maps must not query or apply map bias" };
	$server->{mp_halftime} = 0;
	my $bias = $server->get_map_team_bias();
	ok(!$bias->{active}, "maps without halftime never apply learned side bias");
}
{
	no warnings qw(redefine once);
	my @archive_queries;
	local *main::execNonQuery = sub { push @archive_queries, $_[0]; return 1 };
	$server->{id} = 7;
	$server->{map_started} = 1234;
	$server->{map_ct_wins} = 8;
	$server->{map_ts_wins} = 4;
	$server->{mp_halftime} = 1;
	$server->{ba_halftime_rotated} = 1;
	$server->{ba_eligible_ct_rounds} = 7;
	$server->{ba_eligible_ts_rounds} = 4;
	ok($server->archive_completed_map_team_results("de_train"),
		"completed halftime map with enough human rounds is archived");
	like($archive_queries[0], qr/\(7, 'de_train', 1234, 7, 4, 1\)/,
		"eligible archive stores only human-qualified rounds and marks the match eligible");
	$server->{map_started} = 1235;
	$server->{mp_halftime} = 0;
	$server->{map_ct_wins} = 8;
	$server->{map_ts_wins} = 4;
	ok($server->archive_completed_map_team_results("aim_mirror"),
		"non-halftime map totals remain available for audit");
	like($archive_queries[1], qr/\(7, 'aim_mirror', 1235, 8, 4, 0\)/,
		"non-halftime map is preserved but explicitly excluded from learning");
	$server->{map_ct_wins} = 5;
	$server->{map_ts_wins} = 4;
	ok(!$server->archive_completed_map_team_results("de_train"),
		"very short map sessions are not added to the side-bias history");
	is(scalar @archive_queries, 2, "short map sessions do not write archive rows");
}

$server = new_server();
$server->schedule_team_balance(1000);
ok($server->{ba_balance_pending}, "round-end schedules a deferred balance");
is($server->{ba_balance_due}, 1002,
	"fill mode begins checking within the round-end window");
is($server->{ba_balance_deadline}, 1015,
	"fill roster stabilization retains a bounded deadline");

$server->note_balance_roster_change(1001);
cmp_ok($server->{ba_balance_due}, '>=', 1002,
	"roster changes push back the pending balance check");

my $waiting_due = $server->{ba_balance_due};
$server->{ba_balance_due} = time() + 2;
$server->{ba_balance_deadline} = time() + 15;
$waiting_due = $server->{ba_balance_due};
$server->run_pending_team_balance($waiting_due);
ok($server->{ba_balance_pending}, "ATB keeps waiting while fill quota is unsettled");
is($server->{ba_balance_due}, $waiting_due + 1,
	"unsettled fill roster is polled without blocking the log loop");

$server = new_server();
$server->schedule_team_balance(1000);
$server->{srv_players} = {
	bot => { is_bot => 1, team => "Unassigned", userid => 99 },
};
{
	local *HLstats_Server::analyze_teams = sub { $_[0]->{test_analyze_count}++ };
	$server->run_pending_team_balance(1010);
	ok($server->{ba_balance_pending},
		"ATB cannot analyze before a fill-mode round-start bot adjustment");
	$server->note_balance_roster_change(1011); # bot is kicked after the human's team join
	$server->{srv_players}{bot}{team} = "TERRORIST";
	$server->run_pending_team_balance(1014);
	ok(!$server->{test_analyze_count},
		"a delayed round-start bot kick cannot be preceded by an early swap decision");
	$server->run_pending_team_balance(1015);
	ok(!$server->{test_analyze_count},
		"a stable-looking roster is observed for the full settling window");
	$server->run_pending_team_balance(1018);
	ok(!$server->{test_analyze_count},
		"bot join delay prevents correction before delayed fill changes can arrive");
	$server->run_pending_team_balance(1025);
	ok(!$server->{test_analyze_count},
		"the fill roster remains pending until the configured delay plus safety margin");
	$server->run_pending_team_balance(1026);
	is($server->{test_analyze_count}, 1,
		"the roster is analyzed after bot fill and team assignments settle");
}

my @roster = (
	{ is_bot => 0, team => "CT" },
	map { { is_bot => 1, team => "TERRORIST" } } 1 .. 5,
);
my %roster_hash = map { ("player$_", $roster[$_]) } 0 .. $#roster;
$server->{srv_players} = \%roster_hash;
ok($server->bot_fill_roster_is_changing(100),
	"the first fill roster observation starts the settling window");
ok($server->bot_fill_roster_is_changing(111),
	"the fill roster is not treated as stable too early");
ok(!$server->bot_fill_roster_is_changing(112),
	"a stable observed roster settles even if bot_quota is not a reliable team-count target");
my ($joining_bot) = grep { $_->{is_bot} } @roster;
$joining_bot->{team} = "Unassigned";
ok($server->bot_fill_roster_is_changing(113),
	"a connected fill bot remains unsettled until it joins a team");
$joining_bot->{team} = "TERRORIST";
ok($server->bot_fill_roster_is_changing(114),
	"a newly assigned fill bot restarts the settling window");
ok(!$server->bot_fill_roster_is_changing(126),
	"the assigned roster settles after remaining unchanged");
{
	no warnings qw(redefine once);
	local *HLstats_Server::analyze_teams = sub { $_[0]->{test_analyze_count}++ };
	$server->{ba_balance_due} = $waiting_due + 2;
	$server->run_pending_team_balance($server->{ba_balance_due});
	ok(!$server->{ba_balance_pending}, "stable roster releases the pending balance");
	is($server->{test_analyze_count}, 1, "stable roster is analyzed exactly once");
	$server->{ba_balance_pending} = 1;
	$server->{ba_balance_due} = $waiting_due + 3;
	$server->{ba_balance_deadline} = $waiting_due + 2;
	$server->{srv_players} = {};
	$server->run_pending_team_balance($server->{ba_balance_due});
	ok($server->{ba_balance_pending}, "the old deadline cannot force a correction after a roster change");
	$server->run_pending_team_balance($server->{ba_balance_due} + 12);
	ok(!$server->{ba_balance_pending}, "a stable observed roster releases the deferred check");
	is($server->{test_analyze_count}, 2, "stable fill state is analyzed exactly once");
}
pop @roster;
%roster_hash = map { ("player$_", $roster[$_]) } 0 .. $#roster;
$server->{srv_players} = \%roster_hash;
ok($server->bot_fill_roster_is_changing(),
	"a missing fill bot keeps ATB waiting");

my $change_time = time();
$server = new_server();
$server->{ba_round_active} = 1;
$server->note_balance_roster_change($change_time);
ok(!$server->{ba_balance_pending},
	"mid-round roster changes do not schedule a balance");
$server->schedule_team_balance($change_time);
ok($server->{ba_balance_pending}, "round end schedules a balance");
{
	local *HLstats_Server::analyze_teams = sub { $_[0]->{test_analyze_count}++ };
	$server->{ba_balance_due} = $change_time;
	$server->{ba_round_active} = 1; # The deferred check crossed into the next round.
	$server->run_pending_team_balance($change_time + 20);
	ok($server->{ba_balance_pending},
		"a deferred round-end check cannot execute during a live round");
	ok(!$server->{test_analyze_count},
		"no mid-round balance analysis occurs after the bot-fill delay");
}

$server = new_server();
$server->prepare_halftime_balance();
is_deeply($server->{winner}, ["ts", "ts", "ct", ""],
	"halftime attributes first-half results to the swapped team groups");
is($server->{ba_ct_wins}, 1, "halftime CT win count is recalculated");
is($server->{ba_ts_wins}, 2, "halftime T win count is recalculated");
ok($server->{ba_halftime_rotated}, "halftime history rotates only once");

$server = new_server();
$server->{mp_maxrounds} = 25;
$server->{mp_timelimit} = 30;
$server->{mp_halftime} = 1;
$server->{map_rounds} = 12;
$server->{rcon_obj} = bless {
	rounds => 0,
	timelimit => 0,
	halftime => 0,
}, "TestMapRules";
ok($server->refresh_map_balance_rules(), "map-specific balance cvars refresh at map load");
is_deeply(
	[$server->{mp_maxrounds}, $server->{mp_timelimit}, $server->{mp_halftime}],
	[0, 0, 0],
	"zero cvars replace previous-map settings instead of leaking halftime");
$server->prepare_halftime_balance();
ok(!$server->{ba_halftime_rotated},
	"a map that disables halftime cannot inherit the previous map's halftime state");
$server->{rcon_obj} = bless {
	rounds => 24,
	timelimit => 0,
	halftime => 1,
}, "TestMapRules";
ok($server->refresh_map_balance_rules(), "halftime-enabled map rules refresh successfully");
$server->prepare_halftime_balance();
ok($server->{ba_halftime_rotated},
	"a delayed halftime check catches the first round at or past the half threshold");
$server->{ba_last_swap} = 3;
$server->{ba_balance_pending} = 1;
$server->{ba_swapped_players}{"human:old"} = 1;
$server->{ba_pending_swaps}{"human:pending"} = { team => "CT", expires => time() + 15 };
$server->reset_team_balance();
is_deeply($server->{winner}, [], "map loading clears round winner history");
is($server->{map_rounds}, 0, "map loading resets the round count");
is($server->{ba_last_swap}, 0, "map loading clears the balance cooldown");
is(scalar keys %{$server->{ba_swapped_players}}, 0,
	"map loading clears the per-player swap limit");
ok(!$server->{ba_balance_pending}, "map loading cancels a pending balance check");
is(scalar keys %{$server->{ba_pending_swaps}}, 0,
	"map loading clears unconfirmed swap requests");
is($server->{ba_eligible_ct_rounds}, 0,
	"map loading clears eligible side-bias rounds");

$server = new_server();
$server->{map_rounds} = 12;
$server->{mp_maxrounds} = 24;
$server->{winner} = [("ct") x 6, "ts"];
$server->{ba_round_orientation} = [(0) x 7];
$server->{ba_ct_wins} = 6;
$server->{ba_ts_wins} = 1;
{
	no warnings qw(redefine once);
	local *HLstats_Server::get_map_team_bias = sub {
		return { active => 1, ct_rate => 0.8, ts_rate => 0.2, rounds => 120 };
	};
	my ($ct_excess, $ts_excess, $has_bias) = $server->get_map_adjusted_team_excess(6, 1);
	ok($has_bias && $ct_excess > -1 && $ts_excess > -1,
		"CT-sided results do not trigger score balancing before halftime when they match map bias");
	$server->prepare_halftime_balance();
	($ct_excess, $ts_excess, $has_bias) = $server->get_map_adjusted_team_excess(1, 6);
	ok($has_bias && $ct_excess > -1 && $ts_excess > -1,
		"halftime reattributes player groups while preserving actual side-bias correction");
}

my $human = ["name", "STEAM_0:1:123", 0, "CT", 0, 0, 0, 1, 17, 0];
my $bot = ["bot", "BOT", 0, "TERRORIST", 0, 0, 0, 1, 3, 1];
$server = new_server();
$server->mark_balance_player_swapped($human);
$server->mark_balance_player_swapped($bot);
ok($server->balance_player_was_swapped($human), "human swap is recorded by stable identity");
ok($server->balance_player_was_swapped($bot), "bot swap is recorded for the current userid");

{
	no warnings qw(redefine once);
	my $restore = new_server();
	$restore->{ba_swap_history_loaded} = 0;
	local *main::execCached = sub {
		return bless { rows => [["STEAM_0:1:123"]] }, "TestRows";
	};
	ok($restore->load_balance_swap_history(), "same-map human swap history restores after daemon startup");
	ok($restore->balance_player_was_swapped($human), "restored human remains protected from a second swap");
	$restore->{ba_swap_history_loaded} = 0;
	local *main::execCached = sub { die "history unavailable" };
	ok(!$restore->load_balance_swap_history(), "swap history load failures are observable");
	ok($restore->balance_player_was_swapped($human), "unknown swap history fails closed for human swaps");
}

$server = new_server();
$server->{ba_pending_swaps} = {};
my $pending_human = {
	name => "pending",
	uniqueid => "STEAM_0:1:456",
	team => "CT",
	userid => 18,
	is_bot => 0,
};
my $pending_entry = ["pending", $pending_human->{uniqueid}, 0, "CT", 0, 0, 0, 1, 18, 0];
$server->{srv_players}{pending} = $pending_human;
ok($server->request_balance_player_swap($pending_entry), "ATB dispatches an eligible swap request");
ok(!$server->balance_player_was_swapped($pending_entry),
	"a dispatched swap does not consume the map limit before team-change acknowledgement");
$pending_human->{team} = "TERRORIST";
my @swap_history_inserts;
{
	no warnings qw(redefine once);
	local *main::execNonQuery = sub { push @swap_history_inserts, $_[0]; return 1 };
	ok($server->confirm_balance_player_swap($pending_human), "matching team event confirms the pending swap");
}
ok($server->balance_player_was_swapped($pending_entry),
	"confirmed swaps consume the once-per-map allowance");
like($swap_history_inserts[0], qr/hlstats_MapTeamBalanceSwaps.*STEAM_0:1:456/s,
	"confirmed human swaps persist by map and stable identity");
my $wrong_team_player = { %$pending_human, userid => 19, uniqueid => "STEAM_0:1:457", team => "CT" };
$server->{ba_pending_swaps}{"human:STEAM_0:1:457"} = { team => "TERRORIST", expires => time() + 10 };
ok(!$server->confirm_balance_player_swap($wrong_team_player),
	"a team event that does not match the requested transfer is not acknowledged");
{
	no warnings qw(redefine once);
	local *HLstats_Server::switch_player = sub { return 0 };
	my $failed_entry = ["failed", "STEAM_0:1:458", 0, "CT", 0, 0, 0, 1, 20, 0];
	ok(!$server->request_balance_player_swap($failed_entry), "failed RCON dispatch is reported");
	ok(!$server->balance_player_was_swapped($failed_entry)
		&& !$server->balance_player_swap_pending($failed_entry),
		"failed dispatch consumes neither pending state nor the map swap allowance");
}
$server->{ba_pending_swaps}{"human:STEAM_0:1:459"} = {
	team => "TERRORIST",
	expires => time() - 1,
};
my $expired_player = {
	name => "expired",
	uniqueid => "STEAM_0:1:459",
	team => "TERRORIST",
	userid => 21,
	is_bot => 0,
};
ok(!$server->confirm_balance_player_swap($expired_player),
	"late team events cannot confirm an expired swap request");
$server->expire_pending_balance_swaps(time());
ok(!exists $server->{ba_pending_swaps}{"human:STEAM_0:1:459"},
	"unacknowledged swap requests expire without consuming the map allowance");

sub balance_player {
	my ($index, $team, $is_bot, $kills) = @_;
	return {
		name => "player$index",
		uniqueid => $is_bot ? "BOT:$index" : "STEAM_0:1:$index",
		skill => 100 - $index,
		team => $team,
		map_kills => $kills || 0,
		map_deaths => 0,
		is_dead => 1,
		userid => $index,
		is_bot => $is_bot,
	};
}

$server = new_server();
$server->{minplayers} = 4;
$server->{num_trackable_players} = 5;
$server->{srv_players} = {
	ct1 => { is_bot => 0, team => "CT" },
	ct2 => { is_bot => 0, team => "CT" },
	ct3 => { is_bot => 0, team => "CT" },
	t1 => { is_bot => 0, team => "TERRORIST" },
	t2 => { is_bot => 0, team => "TERRORIST" },
};
ok($server->map_bias_round_is_eligible(),
	"a bot-free round with enough humans on both sides and more than MinPlayers is eligible");
$server->{srv_players}{bot1} = { is_bot => 1, team => "CT" };
ok(!$server->map_bias_round_is_eligible(),
	"rounds involving bots cannot train map-side bias");
delete $server->{srv_players}{bot1};
$server->{srv_players}{t2}{is_bot} = 1;
ok(!$server->map_bias_round_is_eligible(),
	"bots cannot satisfy the per-side human sample requirement");
$server->{srv_players}{t2}{is_bot} = 0;
$server->{minplayers} = 6;
ok(!$server->map_bias_round_is_eligible(),
	"map-bias samples do not train below MinPlayers");

{
	$server = new_server();
	$server->{minplayers} = 4;
	$server->{balance_analyze_rounds} = 7;
	$server->{srv_players} = {
		ct1 => { is_bot => 0, team => "CT" },
		ct2 => { is_bot => 0, team => "CT" },
		ts1 => { is_bot => 0, team => "TERRORIST" },
		ts2 => { is_bot => 0, team => "TERRORIST" },
	};
	$server->begin_map_bias_round();
	ok($server->{ba_round_bias_eligible}, "eligible roster is snapshotted at round start");
	$server->note_balance_roster_change();
	$server->add_round_winner("ct");
	is($server->{ba_eligible_ct_rounds}, 0,
		"a mid-round roster change prevents that round training map bias");
	$server->begin_map_bias_round();
	$server->add_round_winner("ts");
	is($server->{ba_eligible_ts_rounds}, 1,
		"a bot-free stable round contributes its snapshotted eligibility");
}

$server = new_server();
$server->{map_rounds} = 1;
$server->{num_trackable_players} = 1;
$server->{minplayers} = 6;
$server->{balance_analyze_rounds} = 7;
$server->{balance_start_rounds} = 7;
$server->{balance_rounds_delay} = 3;
$server->{balance_max_wins} = 1;
$server->{balance_ignore_bots} = 0;
$server->{switch_admins} = 0;
$server->{balance_switch_only_dead} = 0;
my %imbalanced_roster = (human => balance_player(1, "TERRORIST", 0, 0));
for my $index (1 .. 5) {
	$imbalanced_roster{"bot$index"} = balance_player($index + 1, "CT", 1, 0);
}
$server->{srv_players} = \%imbalanced_roster;
$server->analyze_teams();
acknowledge_test_swaps($server);
is(scalar @{$server->{test_switches}}, 2,
	"low population corrects a 1v5 count imbalance in one pass");
ok(!(grep { $_ == 1 } @{$server->{test_switches}}),
	"fill-mode count correction moves bots before humans");
is(scalar(grep { $_->{team} eq "TERRORIST" } values %{$server->{srv_players}}), 3,
	"low-population count correction leaves even teams");
my @switched_players = map {
	my $userid = $_;
	my ($player) = grep { $_->{userid} == $userid } values %{$server->{srv_players}};
	$player;
} @{$server->{test_switches}};
ok(@switched_players == 2 && !(grep {
	!$server->balance_player_was_swapped(
		["", $_->{uniqueid}, 0, $_->{team}, 0, 0, 0, 1, $_->{userid}, $_->{is_bot}]
	)
} @switched_players), "corrected players are protected by the map swap lock");

$server = new_server();
$server->{num_trackable_players} = 1;
$server->{minplayers} = 6;
$server->{balance_ignore_bots} = 0;
$server->{balance_switch_only_dead} = 0;
$server->{switch_admins} = 0;
my %two_v_four = (
	t1 => balance_player(1, "TERRORIST", 0, 0),
	t2 => balance_player(2, "TERRORIST", 0, 0),
);
for my $index (1 .. 4) {
	$two_v_four{"ct$index"} = balance_player($index + 2, "CT", 1, 0);
}
$server->{srv_players} = \%two_v_four;
$server->analyze_teams();
acknowledge_test_swaps($server);
is(scalar @{$server->{test_switches}}, 1,
	"a 2v4 low-population gap is corrected with one fill bot move");
is(scalar(grep { $_->{team} eq "TERRORIST" } values %{$server->{srv_players}}), 3,
	"the 2v4 correction leaves a 3v3 roster");

$server = new_server();
$server->{num_trackable_players} = 1;
$server->{minplayers} = 6;
$server->{balance_ignore_bots} = 0;
$server->{balance_switch_only_dead} = 0;
$server->{switch_admins} = 0;
my %twelve_v_fifteen;
for my $index (1 .. 12) {
	$twelve_v_fifteen{"t$index"} = balance_player($index, "TERRORIST", 0, 0);
}
for my $index (1 .. 15) {
	$twelve_v_fifteen{"ct$index"} = balance_player($index + 12, "CT", 0, 0);
}
$server->{srv_players} = \%twelve_v_fifteen;
$server->analyze_teams();
acknowledge_test_swaps($server);
is(scalar @{$server->{test_switches}}, 1,
	"human-only 12v15 is corrected by one move without over-balancing the roster");
is(scalar(grep { $_->{team} eq "TERRORIST" } values %{$server->{srv_players}}), 13,
	"the 12v15 correction leaves the permitted 13v14 one-player gap");

$server = new_server();
$server->{num_trackable_players} = 1;
$server->{minplayers} = 4;
$server->{balance_ignore_bots} = 0;
$server->{srv_players} = {
	ct1 => balance_player(1, "CT", 0, 0),
	ct2 => balance_player(2, "CT", 0, 0),
	ct3 => balance_player(3, "CT", 0, 0),
	ts1 => balance_player(4, "TERRORIST", 0, 0),
	join1 => { is_bot => 0, uniqueid => "STEAM_0:1:71", team => "Unassigned" },
	join2 => { is_bot => 0, uniqueid => "STEAM_0:1:72", team => "Unassigned" },
};
$server->{jtc_join_reservations} = {
	"STEAM_0:1:71" => 2,
	"STEAM_0:1:72" => 2,
};
$server->analyze_teams();
is(scalar @{$server->{test_switches} || []}, 0,
	"round-end count analysis includes pending team reservations without swapping assigned players");

$server = new_server();
$server->{num_trackable_players} = 6;
$server->{minplayers} = 6;
$server->{balance_ignore_bots} = 0;
$server->{balance_switch_only_dead} = 1;
$server->{switch_admins} = 1;
my %four_v_two = (
	ct1 => balance_player(1, "CT", 0, 0),
	ct2 => balance_player(2, "CT", 0, 0),
	ct3 => balance_player(3, "CT", 0, 0),
	ct4 => balance_player(4, "CT", 0, 0),
	ts1 => balance_player(5, "TERRORIST", 0, 0),
	ts2 => balance_player(6, "TERRORIST", 0, 0),
);
$_->{map_kills} = 10 for @four_v_two{qw(ct2 ct3 ct4)};
$four_v_two{ct1}{is_dead} = 1;
$four_v_two{ct1}{map_kills} = 0;
$four_v_two{ct1}{userid} = 1;
$four_v_two{ct1}{is_bot} = 0;
$four_v_two{ct1}{team} = "CT";
$four_v_two{ct2}{is_dead} = 0;
$four_v_two{ct3}{is_dead} = 0;
$four_v_two{ct4}{is_dead} = 0;
$server->{srv_players} = \%four_v_two;
$server->analyze_teams();
acknowledge_test_swaps($server);
is(scalar @{$server->{test_switches} || []}, 1,
	"dead-only count correction does not discard the only dead eligible player");
is_deeply($server->{test_switches}, [1],
	"the only eligible 4v2 target is selected while retaining the dead-only guard");
is(scalar(grep { $_->{team} eq "CT" } values %{$server->{srv_players}}), 3,
	"dead-only 4v2 correction leaves balanced 3v3 teams");

$server = new_server();
$server->{num_trackable_players} = 1;
$server->{minplayers} = 6;
$server->{balance_switch_only_dead} = 1;
$server->{balance_ignore_bots} = 0;
%imbalanced_roster = (human => balance_player(1, "TERRORIST", 0, 0));
for my $index (1 .. 5) {
	$imbalanced_roster{"bot$index"} = balance_player($index + 1, "CT", 1, 0);
	$imbalanced_roster{"bot$index"}{is_dead} = 0;
}
$server->{srv_players} = \%imbalanced_roster;
$server->analyze_teams();
acknowledge_test_swaps($server);
is(scalar @{$server->{test_switches} || []}, 0,
	"dead-only ATB never switches living fill bots to correct a count gap");
$_->{is_dead} = 1 for values %imbalanced_roster;
$server->analyze_teams();
acknowledge_test_swaps($server);
is(scalar @{$server->{test_switches}}, 2,
	"the same count gap is corrected once fill bots are dead");
is(scalar(grep { $_->{team} eq "TERRORIST" } values %{$server->{srv_players}}), 3,
	"dead fill-bot correction leaves the teams even");

$server = new_server();
$server->{map_rounds} = 12;
$server->{num_trackable_players} = 6;
$server->{minplayers} = 6;
$server->{balance_analyze_rounds} = 7;
$server->{balance_start_rounds} = 7;
$server->{balance_rounds_delay} = 3;
$server->{balance_max_wins} = 1;
$server->{balance_ignore_bots} = 0;
$server->{switch_admins} = 0;
$server->{balance_switch_only_dead} = 0;
$server->{ba_ct_wins} = 0;
$server->{ba_ts_wins} = 7;
my %balanced_roster;
for my $index (1 .. 3) {
	$balanced_roster{"t$index"} = balance_player($index, "TERRORIST", 0, 20);
	$balanced_roster{"ct$index"} = balance_player($index + 3, "CT", 0, 0);
}
$server->{srv_players} = \%balanced_roster;
$server->analyze_teams();
acknowledge_test_swaps($server);
is(scalar @{$server->{test_switches} || []}, 0,
	"population at MinPlayers suppresses score-based swaps");

$server = new_server();
$server->{map_rounds} = 12;
$server->{num_trackable_players} = 10;
$server->{minplayers} = 8;
$server->{balance_analyze_rounds} = 7;
$server->{balance_start_rounds} = 7;
$server->{balance_rounds_delay} = 3;
$server->{balance_max_wins} = 1;
$server->{balance_ignore_bots} = 0;
$server->{switch_admins} = 0;
$server->{balance_switch_only_dead} = 0;
$server->{ba_ct_wins} = 0;
$server->{ba_ts_wins} = 7;
%balanced_roster = ();
for my $index (1 .. 3) {
	$balanced_roster{"t$index"} = balance_player($index, "TERRORIST", 0, 24 - ($index * 4));
	$balanced_roster{"ct$index"} = balance_player($index + 3, "CT", 0, ($index - 1) * 4);
}
$server->{srv_players} = \%balanced_roster;
$server->analyze_teams();
acknowledge_test_swaps($server);
is(scalar @{$server->{test_switches}}, 2,
	"above MinPlayers, score balancing performs its planned paired transfer");
is_deeply([sort { $a <=> $b } @{$server->{test_switches}}], [2, 4],
	"score balance excludes the strongest winning player and selects the weakest eligible opponent");
ok(!(grep {
	!$server->balance_player_was_swapped(
		["", $_->{uniqueid}, 0, $_->{team}, 0, 0, 0, 1, $_->{userid}, $_->{is_bot}]
	)
} map {
	my $userid = $_;
	my ($player) = grep { $_->{userid} == $userid } values %{$server->{srv_players}};
	$player;
} @{$server->{test_switches}}), "score-swapped players are locked against a second ATB swap");

$server = new_server();
$server->{map_rounds} = 12;
$server->{num_trackable_players} = 10;
$server->{minplayers} = 8;
$server->{balance_analyze_rounds} = 7;
$server->{balance_start_rounds} = 7;
$server->{balance_rounds_delay} = 3;
$server->{balance_max_wins} = 1;
$server->{balance_ignore_bots} = 0;
$server->{switch_admins} = 0;
$server->{balance_switch_only_dead} = 0;
$server->{ba_ct_wins} = 1;
$server->{ba_ts_wins} = 6;
$server->{winner} = ["ct", ("ts") x 6];
$server->{ba_round_orientation} = [(0) x 7];
%balanced_roster = ();
for my $index (1 .. 3) {
	$balanced_roster{"t$index"} = balance_player($index, "TERRORIST", 0, 20);
	$balanced_roster{"ct$index"} = balance_player($index + 3, "CT", 0, 0);
}
$server->{srv_players} = \%balanced_roster;
{
	no warnings qw(redefine once);
	local *HLstats_Server::get_map_team_bias = sub {
		return { active => 1, ct_rate => 0.2, ts_rate => 0.8, rounds => 120 };
	};
	$server->analyze_teams();
}
acknowledge_test_swaps($server);
is(scalar @{$server->{test_switches} || []}, 0,
	"map-side-adjusted round parity avoids an unnecessary score-based swap on a T-sided map");

$server = new_server();
$server->{num_trackable_players} = 10;
$server->{minplayers} = 8;
$server->{map_rounds} = 12;
$server->{balance_analyze_rounds} = 7;
$server->{balance_start_rounds} = 7;
$server->{balance_rounds_delay} = 3;
$server->{balance_max_wins} = 1;
$server->{balance_ignore_bots} = 0;
$server->{switch_admins} = 0;
$server->{balance_switch_only_dead} = 0;
$server->{ba_ct_wins} = 0;
$server->{ba_ts_wins} = 7;
%balanced_roster = ();
for my $index (1 .. 3) {
	$balanced_roster{"t$index"} = balance_player($index, "TERRORIST", 0, 24 - ($index * 4));
	$balanced_roster{"ct$index"} = balance_player($index + 3, "CT", 0, ($index - 1) * 4);
}
$server->{srv_players} = \%balanced_roster;
{
	no warnings qw(redefine once);
	local *HLstats_Server::is_admin = sub { $_[1] eq "STEAM_0:1:4" };
	$server->analyze_teams();
}
acknowledge_test_swaps($server);
is_deeply([sort { $a <=> $b } @{$server->{test_switches}}], [2, 5],
	"score balancing excludes protected admins and chooses the next eligible player");

{
	local $::g_stdin = 1;
	$server = new_server();
	$server->schedule_team_balance();
	ok(!$server->{ba_balance_pending}, "log-import mode does not schedule live ATB");
}

done_testing();
