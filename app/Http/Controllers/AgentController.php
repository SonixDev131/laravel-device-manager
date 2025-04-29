<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Computer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AgentController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'mac_address' => 'required|string|regex:/^([0-9A-Fa-f]{2}-){5}([0-9A-Fa-f]{2})$/',
            'hostname' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid input data'], 422);
        }

        $mac_address = $request->input('mac_address');
        $hostname = $request->input('hostname');

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
