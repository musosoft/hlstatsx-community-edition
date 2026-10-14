#!/usr/bin/env python3
"""Count-only roster stress model; tactical policy is tested against Perl methods."""

from dataclasses import dataclass
from pathlib import Path
import random
import unittest


ROOT = Path(__file__).parents[1]


@dataclass
class Player:
    key: str
    team: int
    is_bot: bool
    skill: int
    swapped: bool = False
    swap_count: int = 0
    dead: bool = True
    admin: bool = False
    kills_diff: int = 0


def team_counts(players):
    return tuple(sum(player.team == team for player in players) for team in (2, 3))


def fill_bots(players, quota, next_bot):
    humans = sum(not player.is_bot for player in players)
    target = max(0, quota - humans)
    bots = [player for player in players if player.is_bot]
    while len(bots) > target:
        counts = team_counts(players)
        larger = 2 if counts[0] > counts[1] else 3
        player = next((p for p in bots if p.team == larger), bots[0])
        players.remove(player)
        bots.remove(player)
    while len(bots) < target:
        counts = team_counts(players)
        team = 2 if counts[0] <= counts[1] else 3
        bot = Player(f"bot:{next_bot}", team, True, (next_bot * 7) % 13)
        next_bot += 1
        players.append(bot)
        bots.append(bot)
    return next_bot


def fill_roster_is_stable(players, quota):
    humans = sum(not player.is_bot for player in players)
    bots = sum(player.is_bot for player in players)
    return bots == max(0, quota - humans)


def balance_counts_once_per_map(players, dead_only=True):
    before = team_counts(players)
    if abs(before[0] - before[1]) <= 1:
        return 0
    larger = 2 if before[0] > before[1] else 3
    smaller = 3 if larger == 2 else 2
    difference = abs(before[0] - before[1])
    needed = difference // 2
    candidates = [p for p in players if p.team == larger]
    # Fill-mode count correction moves a bot before a human when one is
    # available, avoiding human churn for bot-only team-count skew.
    candidates.sort(key=lambda player: (not player.is_bot, player.kills_diff, player.key))
    # Retain the legacy human-protection rule only when no bot can absorb it.
    if not any(player.is_bot for player in candidates):
        candidates = candidates[1:]
    moved = 0
    for player in candidates:
        if moved >= needed:
            break
        if player.swapped or player.admin or (dead_only and not player.dead):
            continue
        player.team = smaller
        player.swapped = True
        player.swap_count += 1
        moved += 1
    return moved


def run_scenario(humans, rounds, churn, seed):
    rng = random.Random(seed)
    players = []
    # Include the reported worst case: one human versus five fill bots.
    for index in range(humans):
        team = 2 if index % 2 == 0 else 3
        players.append(Player(f"human:{index}", team, False, rng.randrange(1, 101)))
    next_bot = 0
    next_bot = fill_bots(players, 6, next_bot)
    if humans == 1:
        for bot in (p for p in players if p.is_bot):
            bot.team = 3

    moves = 0
    worst_delta = 0
    next_human = humans
    for round_number in range(1, rounds + 1):
        # Roster changes happen before the deferred balance check. Frequent mode
        # stresses consecutive join/leave events; normal mode changes every 6 rounds.
        changes = 2 if churn == "frequent" and round_number % 2 == 0 else 0
        changes = max(changes, 1 if churn == "normal" and round_number % 6 == 0 else 0)
        for _ in range(changes):
            human_players = [p for p in players if not p.is_bot]
            if human_players and (len(players) >= 32 or rng.random() < 0.5):
                leaving = rng.choice(human_players)
                players.remove(leaving)
            else:
                counts = team_counts(players)
                team = 2 if counts[0] <= counts[1] else 3
                players.append(Player(f"human:{next_human}", team, False, rng.randrange(1, 101)))
                next_human += 1
            next_bot = fill_bots(players, 6, next_bot)

        # Model the game completing its fill adjustment before ATB's observed
        # roster-stability window releases a correction.
        humans_now = sum(not p.is_bot for p in players)
        bots_now = sum(p.is_bot for p in players)
        assert bots_now == max(0, 6 - humans_now)
        # Model the round-end seam, where the real candidate filters apply.
        # Score-based choices are intentionally left to the real-method Perl tests.
        for player in players:
            player.dead = True
        moves += balance_counts_once_per_map(players)
        counts = team_counts(players)
        delta = abs(counts[0] - counts[1])
        worst_delta = max(worst_delta, delta)
        assert delta <= 1, (humans, rounds, churn, round_number, counts)

        # Halftime exchanges sides and score ownership, but is not an ATB swap
        # for the one-ATB-transfer-per-player-per-map rule.
        if round_number == rounds // 2:
            for player in players:
                player.team = 5 - player.team

    assert all(player.swap_count <= 1 for player in players)
    return moves, worst_delta, len(players)


class HLXATBSimulationTests(unittest.TestCase):
    def test_production_defers_and_reconciles_fill_bot_rosters(self):
        server = (ROOT / "src/scripts/HLstats_Server.pm").read_text()
        events = (ROOT / "src/scripts/HLstats_EventHandlers.plib").read_text()
        daemon = (ROOT / "src/scripts/hlstats.pl").read_text()
        self.assertIn("sub run_pending_team_balance", server)
        self.assertIn("sub bot_fill_roster_is_changing", server)
        self.assertIn("ba_fill_roster_stable_since", server)
        self.assertIn("my @roster = sort map", server)
        self.assertNotIn("my $expected_bots", server)
        self.assertIn("return if $::g_stdin != 0 || $self->{ba_enabled} == 0", server)
        self.assertIn("bot_quota_mode", server)
        self.assertIn("apply_team_balance_config_defaults(\\%s_cfg)", daemon)
        self.assertIn("schedule_team_balance()", events)
        self.assertIn("note_balance_roster_change()", events)
        team_selection = events.split("sub doEvent_TeamSelection", 1)[1].split("sub doEvent_RoleSelection", 1)[0]
        self.assertIn("note_balance_roster_change()", team_selection)
        self.assertIn("confirm_balance_player_swap($player)", team_selection)
        self.assertIn("run_pending_team_balance(time())", daemon)
        self.assertIn("begin_map_bias_round()", events)
        self.assertIn("ba_round_bias_eligible", server)
        self.assertNotIn("select(undef, undef, undef, 4.5)", server)

    def test_routine_analysis_does_not_broadcast_chat(self):
        server = (ROOT / "src/scripts/HLstats_Server.pm").read_text()
        analyze = server.split("sub analyze_teams", 1)[1].split("sub updateDB", 1)[0]
        self.assertNotIn("ATB - Checking Teams", analyze)

    def test_human_swap_allowance_survives_daemon_reload(self):
        server = (ROOT / "src/scripts/HLstats_Server.pm").read_text()
        sql = (ROOT / "src/sql/install.sql").read_text()
        updater = ROOT / "src/web/updater/95.php"
        self.assertIn("sub load_balance_swap_history", server)
        self.assertIn("sub persist_balance_player_swap", server)
        self.assertIn("hlstats_MapTeamBalanceSwaps", sql)
        self.assertTrue(updater.is_file())

    def test_fill_count_correction_moves_bots_before_humans(self):
        players = [
            Player("human:0", 2, False, 50),
            *[Player(f"bot:{i}", 3, True, i) for i in range(5)],
        ]
        self.assertEqual(balance_counts_once_per_map(players), 2)
        self.assertFalse(next(p for p in players if p.key == "human:0").swapped)
        self.assertEqual(team_counts(players), (3, 3))

    def test_map_swaps_are_limited_and_halftime_state_is_prepared(self):
        server = (ROOT / "src/scripts/HLstats_Server.pm").read_text()
        events = (ROOT / "src/scripts/HLstats_EventHandlers.plib").read_text()
        self.assertIn("ba_swapped_players", server)
        self.assertIn("balance_player_was_swapped", server)
        self.assertIn("sub prepare_halftime_balance", server)
        self.assertIn("mp_timelimit", server)
        self.assertIn("ba_halftime_rotated", server)
        self.assertIn("$g_servers{$s_addr}->reset_team_balance();", events)
        commencing = events.split('if ($action eq "Game_Commencing")', 1)[1].split("$g_servers{$s_addr}->updateDB()", 1)[0]
        self.assertIn("reset_team_balance()", commencing)

    def test_fill_bot_join_after_human_leave_cancels_an_unneeded_swap(self):
        players = [
            Player("human:0", 2, False, 50),
            Player("human:1", 3, False, 50),
        ]
        for index, team in enumerate((2, 2, 3, 3)):
            players.append(Player(f"bot:{index}", team, True, 10))

        # A human leaves the overfull side; the quota bot has not joined yet.
        players.remove(next(p for p in players if p.key == "human:0"))
        self.assertFalse(fill_roster_is_stable(players, quota=6))

        # The fill bot arrives on the smaller side before ATB is allowed to act.
        fill_bots(players, quota=6, next_bot=4)
        self.assertTrue(fill_roster_is_stable(players, quota=6))
        self.assertEqual(balance_counts_once_per_map(players), 0)
        self.assertEqual(team_counts(players), (3, 3))

    def test_1_to_32_humans_normal_and_frequent_churn_20_and_25_rounds(self):
        scenario_count = 0
        max_team_delta = 0
        total_moves = 0
        for humans in range(1, 33):
            for churn in ("normal", "frequent"):
                for rounds in (20, 25):
                    moves, worst_delta, roster_size = run_scenario(
                        humans, rounds, churn, seed=humans * 1000 + rounds
                    )
                    scenario_count += 1
                    total_moves += moves
                    max_team_delta = max(max_team_delta, worst_delta)
                    self.assertLessEqual(roster_size, 32)
        self.assertEqual(scenario_count, 128)
        self.assertLessEqual(max_team_delta, 1)
        self.assertGreaterEqual(total_moves, 1)


if __name__ == "__main__":
    unittest.main(verbosity=2)
