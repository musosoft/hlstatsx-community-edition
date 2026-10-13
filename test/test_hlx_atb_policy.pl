#!/usr/bin/env perl
use strict;
use warnings;
use Test::More;
use FindBin;

our $opt_libdir = "$FindBin::Bin/../src/scripts";

require "$::opt_libdir/HLstats_Server.pm";
my $real_get_map_team_bias = \&HLstats_Server::get_map_team_bias;

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
		return "";
	}
}

{
	package TestStatement;
	sub fetchrow_array { return @{$_[0]->{row}}; }
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
		rcon => "test",
		rcon_obj => bless({}, "TestRcon"),
		maxplayers => 32,
		srv_players => {},
		ba_swapped_players => {},
		ba_pending_swaps => {},
		ba_round_orientation => [],
		ba_balance_pending => 0,
		ba_balance_due => 0,
		ba_balance_deadline => 0,
		ba_roster_changed_at => 0,
		mp_halftime => 1,
		mp_maxrounds => 25,
		mp_timelimit => 0,
		map_started => time(),
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
	local *HLstats_Server::get_map_team_bias = $real_get_map_team_bias;
	local *main::execCached = sub {
		return bless { row => [48, 12] }, "TestStatement";
	};
	my $bias = $server->get_map_team_bias();
	ok($bias->{active} && $bias->{rounds} == 60 && $bias->{ct_rate} == 0.8,
		"per-map side bias activates at a 60-round sample");
	local *main::execCached = sub {
		return bless { row => [35, 25] }, "TestStatement";
	};
	$bias = $server->get_map_team_bias();
	ok(!$bias->{active}, "small side-rate differences do not create an over-map bias");
	local *main::execCached = sub {
		return bless { row => [30, 29] }, "TestStatement";
	};
	$bias = $server->get_map_team_bias();
	ok(!$bias->{active}, "map bias waits until at least 60 completed rounds");
}
{
	no warnings qw(redefine once);
	my @archive_queries;
	local *main::execNonQuery = sub { push @archive_queries, $_[0]; return 1 };
	$server->{id} = 7;
	$server->{map_started} = 1234;
	$server->{map_ct_wins} = 8;
	$server->{map_ts_wins} = 4;
	ok($server->archive_completed_map_team_results("de_train"),
		"completed map-side results are archived");
	like($archive_queries[0], qr/\(7, 'de_train', 1234, 8, 4\)/,
		"map-side archive stores server, map, match start, and both side counts");
	$server->{map_ct_wins} = 5;
	$server->{map_ts_wins} = 4;
	ok(!$server->archive_completed_map_team_results("de_train"),
		"very short map sessions are not added to the side-bias history");
	is(scalar @archive_queries, 1, "short map sessions do not write archive rows");
}

$server = new_server();
$server->schedule_team_balance();
ok($server->{ba_balance_pending}, "round-end schedules a deferred balance");
is($server->{ba_balance_due}, $server->{ba_balance_deadline},
	"fill mode defers analysis through the bot round-start reconciliation window");

$server->note_balance_roster_change();
cmp_ok($server->{ba_balance_due}, '>=', time() + 1,
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
{
	local *HLstats_Server::analyze_teams = sub { $_[0]->{test_analyze_count}++ };
	$server->run_pending_team_balance(1010);
	ok($server->{ba_balance_pending},
		"ATB cannot analyze before a fill-mode round-start bot adjustment");
	$server->note_balance_roster_change(1011); # bot is kicked after the human's team join
	$server->run_pending_team_balance(1014);
	ok(!$server->{test_analyze_count},
		"a delayed round-start bot kick cannot be preceded by an early swap decision");
	$server->run_pending_team_balance(1015);
	is($server->{test_analyze_count}, 1,
		"the roster is analyzed after the fill reconciliation window");
}

my @roster = (
	{ is_bot => 0, team => "CT" },
	map { { is_bot => 1, team => "TERRORIST" } } 1 .. 5,
);
my %roster_hash = map { ("player$_", $roster[$_]) } 0 .. $#roster;
$server->{srv_players} = \%roster_hash;
ok(!$server->bot_fill_roster_is_changing(),
	"one human plus five bots satisfies bot_quota 6 fill mode");
my ($joining_bot) = grep { $_->{is_bot} } @roster;
$joining_bot->{team} = "Unassigned";
ok($server->bot_fill_roster_is_changing(),
	"a connected fill bot remains unsettled until it joins a team");
$joining_bot->{team} = "TERRORIST";
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
	ok(!$server->{ba_balance_pending}, "deadline prevents a missing bot from blocking ATB forever");
	is($server->{test_analyze_count}, 2, "deadline fallback runs one final balance check");
}
pop @roster;
%roster_hash = map { ("player$_", $roster[$_]) } 0 .. $#roster;
$server->{srv_players} = \%roster_hash;
ok($server->bot_fill_roster_is_changing(),
	"a missing fill bot keeps ATB waiting");

my $change_time = time();
$server = new_server();
$server->note_balance_roster_change($change_time);
ok($server->{ba_balance_pending},
	"roster changes schedule a balance even after an earlier check");
is($server->{ba_balance_due}, $server->{ba_balance_deadline},
	"roster-triggered checks retain the fill settle window");
my $hard_deadline = $server->{ba_balance_deadline};
$server->note_balance_roster_change($change_time + 3);
is($server->{ba_balance_deadline}, $hard_deadline,
	"roster churn does not extend the original hard deadline");
{
	local *HLstats_Server::analyze_teams = sub { $_[0]->{test_analyze_count}++ };
	$server->{ba_balance_due} = $hard_deadline;
	$server->run_pending_team_balance($hard_deadline);
	ok(!$server->{ba_balance_pending},
		"hard deadline wins over a just-observed roster change");
	is($server->{test_analyze_count}, 1,
		"deadline performs exactly one final analysis");
}
{
	no warnings qw(redefine once);
	local *HLstats_Server::dorcon = sub { return '"bot_quota_mode" = "normal"' };
	$server = new_server();
	$server->note_balance_roster_change(time());
	ok(!$server->{ba_balance_pending},
		"non-fill servers retain round-end-only balance scheduling");
}

$server = new_server();
$server->prepare_halftime_balance();
is_deeply($server->{winner}, ["ts", "ts", "ct", ""],
	"halftime attributes first-half results to the swapped team groups");
is($server->{ba_ct_wins}, 1, "halftime CT win count is recalculated");
is($server->{ba_ts_wins}, 2, "halftime T win count is recalculated");
ok($server->{ba_halftime_rotated}, "halftime history rotates only once");
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
ok($server->confirm_balance_player_swap($pending_human), "matching team event confirms the pending swap");
ok($server->balance_player_was_swapped($pending_entry),
	"confirmed swaps consume the once-per-map allowance");
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
$server->{balance_switch_only_dead} = 1;
$server->{balance_ignore_bots} = 0;
%imbalanced_roster = (human => balance_player(1, "TERRORIST", 0, 0));
for my $index (1 .. 5) {
	$imbalanced_roster{"bot$index"} = balance_player($index + 1, "CT", 1, 0);
	$imbalanced_roster{"bot$index"}{is_dead} = 0;
}
$server->{srv_players} = \%imbalanced_roster;
$server->analyze_teams();
is(scalar @{$server->{test_switches} || []}, 0,
	"round-start living players are not switched when dead-only balancing is enabled");
$_->{is_dead} = 1 for values %imbalanced_roster;
$server->analyze_teams();
acknowledge_test_swaps($server);
is(scalar @{$server->{test_switches}}, 2,
	"the next round-end analysis can correct counts after eligible players die");

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
