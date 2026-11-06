import re
import unittest
from pathlib import Path


SOURCE = Path(__file__).parents[1] / "src/sourcemod/scripting/hlstatsx.sp"


class HLStatsXSwapSafetyTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        source = SOURCE.read_text()
        cls.swap = source.split(
            "swap_player_internal(player_index, bool:allow_alive, bool:is_atb)\n{", 1
        )[1].split("public CreateHLstatsXMenuMain", 1)[0]
        cls.source = source

    def test_live_atb_targets_are_allowed_with_explicit_override(self):
        self.assertIn("bool was_alive = IsPlayerAlive(player_index)", self.swap)
        self.assertIn("if (was_alive && !allow_alive)", self.swap)
        self.assertNotIn("if (was_alive && (!allow_alive || is_atb))", self.swap)
        self.assertIn("swap_player_internal(client, true, true)", self.source)
        self.assertIn("swap_player_internal(first_client, true, true)", self.source)
        self.assertIn("swap_player_internal(second_client, true, true)", self.source)
        self.assertIn("Refusing team swap: target is alive", self.swap)

    def test_only_manual_admin_swaps_can_respawn_live_targets(self):
        manual_respawns = re.findall(
            r"if \(allow_alive && was_alive && !is_atb\)\s*"
            r"\{\s*CS_RespawnPlayer\(player_index\);\s*\}", self.swap
        )
        self.assertEqual(len(manual_respawns), 2)
        self.assertEqual(self.swap.count("CS_RespawnPlayer("), 2)
        self.assertIn("swap_player_internal(player_index, false, false)", self.source)
        self.assertIn("swap_player_internal(player_index, true, false)", self.source)
        admin_command = self.source.split("public Action sm_swap", 1)[1].split(
            "public Action:hlx_sm_redirect", 1
        )[0]
        self.assertIn("swap_player_admin(target_client)", admin_command)


if __name__ == "__main__":
    unittest.main()
