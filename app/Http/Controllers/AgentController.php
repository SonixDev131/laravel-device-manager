<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Api\Agent\RegisterAgentRequest;
use App\Models\Computer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    public function register(RegisterAgentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $mac_address = $validated['mac_address'];
        $hostname = $validated['hostname'];

        try {
            // So khớp MAC
            $computer = Computer::where('mac_address', $mac_address)->first();

            if (! $computer) {
                Log::warning("Không tìm thấy máy với MAC: {$mac_address}");

                return response()->json(['error' => 'MAC address not found'], 404);
            }

            // Cập nhật thông tin
            $computer->update([
                'hostname' => $hostname,
            ]);

            Log::info("Đã đăng ký máy {$hostname} (MAC: {$mac_address}) vào phòng {$computer->room->name}");

            return response()->json([
                'room_id' => $computer->room->id,
                'computer_id' => $computer->id,
                'message' => 'Agent registered successfully',
            ], 200);
        } catch (Exception $e) {
            Log::error("Lỗi đăng ký agent: {$e->getMessage()}");

            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
