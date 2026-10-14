#!/usr/bin/env python3
"""Source contract checks for bounded asynchronous SourceMod API callbacks."""

from pathlib import Path
import unittest


ROOT = Path(__file__).parents[1]
API = ROOT / "src/sourcemod/scripting/hlstatsx_api.sp"


class HLStatsXApiCallbackTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.source = API.read_text()

    def test_pending_requests_have_a_time_and_size_bound(self):
        self.assertIn("#define API_REQUEST_TTL 30.0", self.source)
        self.assertIn("#define API_MAX_PENDING_REQUESTS 256", self.source)
        self.assertIn("now - data.time > API_REQUEST_TTL", self.source)
        self.assertIn("g_hRequestCallbacks.Length >= API_MAX_PENDING_REQUESTS", self.source)

    def test_pending_requests_are_pruned_when_clients_disconnect(self):
        self.assertIn("public void OnClientDisconnect(int client)", self.source)
        self.assertIn("if (data.client == client)", self.source)
        self.assertIn("g_hRequestCallbacks.Erase(i)", self.source)

    def test_join_policy_responses_share_expiry_and_callback_routing(self):
        self.assertIn('Api_SendRequest(callbackClient, "api_request", "joinpolicy", queryPayload)', self.source)
        self.assertIn("PrunePendingRequests();\n    if (args < 1)", self.source)
        self.assertIn("Call_PushCell(HLX_CALLBACK_TYPE_JOIN_POLICY)", self.source)


if __name__ == "__main__":
    unittest.main()
