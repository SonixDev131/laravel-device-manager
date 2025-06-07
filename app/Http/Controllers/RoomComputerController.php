<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateComputerAction;
use App\Actions\DeleteComputerAction;
use App\Actions\UpdateComputerAction;
use App\Http\Requests\StoreComputerRequest;
use App\Http\Requests\UpdateComputerRequest;
use App\Models\Computer;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;

final class RoomComputerController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreComputerRequest $request,
        Room $room,
        CreateComputerAction $action
    ): RedirectResponse {
        $action->handle($room, $request->validated());

        return redirect()->back();
    }

    /**
     * Update the specified computer.
     */
    public function update(
        UpdateComputerRequest $request,
        Room $room,
        Computer $computer,
        UpdateComputerAction $action
    ): RedirectResponse {
        // Verify computer belongs to this room
        if ($computer->room_id !== $room->id) {
            abort(404, 'Computer not found in this room');
        }

        $action->handle($computer, $request->validated());

        return redirect()->back()->with('success', 'Computer updated successfully');
    }

    /**
     * Remove the specified computer.
     */
    public function destroy(
        Room $room,
        Computer $computer,
        DeleteComputerAction $action
    ): RedirectResponse {
        // Verify computer belongs to this room
        if ($computer->room_id !== $room->id) {
            abort(404, 'Computer not found in this room');
        }

        $action->handle($computer);

        return redirect()->back()->with('success', 'Computer deleted successfully');
    }
}
