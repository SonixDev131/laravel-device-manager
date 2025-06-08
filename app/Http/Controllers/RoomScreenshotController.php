<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Screenshot;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

class RoomScreenshotController extends Controller
{
    /**
     * Get screenshots for a specific room
     */
    public function index(Request $request, Room $room): JsonResponse
    {
        $query = Screenshot::query()
            ->with(['computer', 'command'])
            ->whereHas('computer', function ($computerQuery) use ($room) {
                $computerQuery->where('room_id', $room->id);
            })
            ->orderBy('taken_at', 'desc');

        // Filter by command_id if provided
        if ($request->has('command_id')) {
            $query->where('command_id', $request->get('command_id'));
        }

        // Filter by computer_id if provided
        if ($request->has('computer_id')) {
            $query->where('computer_id', $request->get('computer_id'));
        }

        // Filter by date range if provided
        if ($request->has('from_date')) {
            $query->where('taken_at', '>=', $request->get('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('taken_at', '<=', $request->get('to_date'));
        }

        $screenshots = $query->paginate(20);

        // Transform the data to include file URLs
        $transformedData = $screenshots->getCollection()->map(function (Screenshot $screenshot) {
            try {
                return [
                    'id' => $screenshot->id,
                    'command_id' => $screenshot->command_id,
                    'computer_id' => $screenshot->computer_id,
                    'computer_name' => $screenshot->computer->hostname ?? 'Unknown',
                    'file_name' => $screenshot->file_name,
                    'file_size' => $screenshot->file_size,
                    'file_size_formatted' => $this->formatFileSize($screenshot->file_size),
                    'mime_type' => $screenshot->mime_type,
                    'file_url' => $screenshot->file_url,
                    'taken_at' => $screenshot->taken_at?->toISOString(),
                    'taken_at_formatted' => $screenshot->taken_at?->format('Y-m-d H:i:s') ?? 'Unknown',
                    'created_at' => $screenshot->created_at?->toISOString(),
                ];
            } catch (Exception $e) {
                Log::error('Error transforming screenshot: '.$e->getMessage(), [
                    'screenshot_id' => $screenshot->id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return null; // This will be filtered out
            }
        })->filter(); // Remove null values

        return response()->json([
            'data' => $transformedData->values()->toArray(),
            'pagination' => [
                'current_page' => $screenshots->currentPage(),
                'last_page' => $screenshots->lastPage(),
                'per_page' => $screenshots->perPage(),
                'total' => $screenshots->total(),
            ],
        ]);
    }

    /**
     * Get a specific screenshot
     */
    public function show(Room $room, Screenshot $screenshot): JsonResponse
    {
        // Load the computer relationship first
        $screenshot->load(['computer', 'command']);

        // Ensure the screenshot belongs to a computer in this room
        if ($screenshot->computer === null || $screenshot->computer->room_id !== $room->id) {
            return response()->json(['message' => 'Screenshot not found'], 404);
        }

        return response()->json([
            'id' => $screenshot->id,
            'command_id' => $screenshot->command_id,
            'computer_id' => $screenshot->computer_id,
            'computer_name' => $screenshot->computer->hostname,
            'file_name' => $screenshot->file_name,
            'file_size' => $screenshot->file_size,
            'file_size_formatted' => $this->formatFileSize($screenshot->file_size),
            'mime_type' => $screenshot->mime_type,
            'file_url' => $screenshot->file_url,
            'taken_at' => $screenshot->taken_at?->toISOString(),
            'taken_at_formatted' => $screenshot->taken_at?->format('Y-m-d H:i:s') ?? 'Unknown',
            'created_at' => $screenshot->created_at?->toISOString(),
        ]);
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }
}
