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
        cls.auto_swap = cls.source.split("swap_player(player_index)\n{", 1)[1].split(
            "swap_player_admin(player_index)", 1
        )[0]
        cls.admin_swap = cls.source.split("swap_player_admin(player_index)\n{", 1)[1].split(
            "swap_player_internal(player_index, bool:allow_alive, bool:is_atb)", 1
        )[0]
        cls.swap_internal = cls.source.split(
            "swap_player_internal(player_index, bool:allow_alive, bool:is_atb)", 1
        )[1].split("public CreateHLstatsXMenuMain", 1)[0]

    def test_swap_execution_rechecks_alive_state(self):
        self.assertIn("swap_player_internal(player_index, false, false);", self.auto_swap)
        self.assertIn("if (!IsClientInGame(player_index))", self.swap_internal)
        self.assertIn("if (was_alive && !allow_alive)", self.swap_internal)
        self.assertIn("Refusing team swap: target is alive", self.swap_internal)

    def test_automatic_swap_never_respawns_a_player(self):
        self.assertNotIn("CS_RespawnPlayer", self.auto_swap)
        self.assertIn("swap_player_internal(player_index, true, false);", self.admin_swap)
        self.assertIn("if (allow_alive && was_alive)", self.swap_internal)
        self.assertIn("CS_RespawnPlayer(player_index);", self.swap_internal)

    def test_new_round_balance_controls_are_css_only(self):
        on_start = self.source.split("public OnPluginStart()", 1)[1].split("public OnMapStart()", 1)[0]
        css_gate = on_start.split("if (gamemod == Game_CSS)", 1)[1]
        self.assertIn('RegServerCmd("hlx_sm_team_counts", HLX_ATB_TeamCounts);', css_gate)
        self.assertIn('CreateConVar("hlx_atb_audit"', css_gate)
        self.assertIn("if (gamemod == Game_CSS)", on_start)


if __name__ == "__main__":
    unittest.main()
