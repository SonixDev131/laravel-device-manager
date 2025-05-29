<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PublishCommandAction;
use App\Http\Requests\PublishCommandRequest;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

final class RoomCommandController extends Controller
{
    /**
     * Publish a command to computers in a room
     */
    public function publish(
        Room $room,
        PublishCommandRequest $request,
        PublishCommandAction $action
    ): RedirectResponse|JsonResponse {
        Log::info('PublishCommandController', $request->validated());
        $result = $action->handle($room, $request->validated());

        if ($result) {
            /* return response()->json([ */
            /*     'success' => true, */
            /*     'message' => 'Command published successfully', */
            /* ]); */
            return to_route('rooms.show', $room->id);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to publish command',
        ], 500);
    }
}
