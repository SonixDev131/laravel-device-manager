<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CommandType;
use App\Models\Command;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

final class RoomBlockedWebsitesController extends Controller
{
    /**
     * Get the list of currently blocked websites for a room
     */
    public function index(Room $room): JsonResponse
    {
        // Get all BLOCK_WEBSITE for the room
        $blockedWebsites = Command::query()
            ->where('room_id', $room->id)
            ->where('type', CommandType::BLOCK_WEBSITE)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->get();

        // Extract the URLs from the command params
        $blockedWebsitesUrls = [];
        foreach ($blockedWebsites as $blockedWebsite) {
            if (isset($blockedWebsite->params['urls'])) {
                $blockedWebsitesUrls[] = $blockedWebsite->params['urls'][0];
            }
        }

        return response()->json(['data' => $blockedWebsitesUrls]);
    }
}
