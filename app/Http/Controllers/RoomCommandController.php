<?php

namespace App\Http\Controllers;

use App\Actions\PublishComputerCommandAction;
use App\Http\Requests\PublishComputerCommandRequest;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;

class RoomCommandController extends Controller
{
    public function handleCommand(
        PublishComputerCommandRequest $request,
        Room $room,
        PublishComputerCommandAction $action
    ): RedirectResponse {
        $validated = $request->validated();
        $commandType = $validated['command_type'];

        match ($validated['target_type']) {
            'single' => $action->publishCommandToComputer($validated['computer_id'], $room->id, $commandType),
            'group' => ! empty($validated['computer_ids'])
                ? $action->publishCommandToMultipleComputers($validated['computer_ids'], $room->id, $commandType)
                : null,
            'all' => $action->publishCommandToRoom($room->id, $commandType),
            default => null,
        };

        return to_route('rooms.show', $room->id);
    }
}
