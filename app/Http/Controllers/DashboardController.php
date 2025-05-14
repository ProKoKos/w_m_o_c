<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\MinerSyncController; // To access static methods

class DashboardController extends Controller
{
    public function showMinerDashboard()
    {
        return Inertia::render("miner-dashboard");
    }

    public function getMinerLog(Request $request)
    {
        $minerId = $request->input("miner_id", "unknown_miner");
        $messages = MinerSyncController::getMinerMessages($minerId);
        return response()->json($messages);
    }

    public function sendMinerCommand(Request $request)
    {
        $minerId = $request->input("miner_id", "unknown_miner");
        $commandType = $request->input("command_type");
        $targetApiCommand = $request->input("target_api_command_cmd");
        $targetApiParams = $request->input("target_api_command_param_json"); // This should be an object/array

        if (!$commandType || !$targetApiCommand) {
            return response()->json(["status" => "error", "message" => "Command type and target API command are required."], 400);
        }

        $commandDetails = [
            "command_type" => $commandType,
            "target_api_command_cmd" => $targetApiCommand,
            "target_api_command_param_json" => $targetApiParams ?? new \stdClass(), // Ensure it's an object if null
            // Add other relevant fields like "expected_data_in_report", "timeout_seconds" if needed from dashboard
        ];

        $wmocCommandId = MinerSyncController::addCommandForMiner($minerId, $commandDetails);

        return response()->json(["status" => "success", "message" => "Command queued", "wmoc_command_id" => $wmocCommandId]);
    }

    public function clearMinerLog(Request $request)
    {
        $minerId = $request->input("miner_id", "unknown_miner");
        MinerSyncController::clearMinerData($minerId);
        return response()->json(["status" => "success", "message" => "Data cleared for miner: " . $minerId]);
    }
}

