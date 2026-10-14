#!/usr/bin/env python3
"""Source contract checks for dead-only HLstatsX team swaps."""

from pathlib import Path
import unittest


ROOT = Path(__file__).parents[1]
PLUGIN = ROOT / "src/sourcemod/scripting/hlstatsx.sp"


class HLStatsXTeamSwapTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.source = PLUGIN.read_text()
        cls.swap = cls.source.split("swap_player(player_index)\n{", 1)[1].split(
            "public CreateHLstatsXMenuMain", 1
        )[0]

    def test_swap_execution_rechecks_alive_state(self):
        self.assertIn("IsClientInGame(player_index) && !IsPlayerAlive(player_index)", self.swap)
        self.assertIn("Refusing team swap: target is alive", self.swap)

    def test_swap_execution_never_respawns_a_player(self):
        self.assertNotIn("CS_RespawnPlayer", self.swap)
        self.assertNotIn("if (IsPlayerAlive(player_index))", self.swap)


if __name__ == "__main__":
    unittest.main()
