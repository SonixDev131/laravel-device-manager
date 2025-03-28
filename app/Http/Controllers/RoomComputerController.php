<?php

namespace App\Http\Controllers;

use App\Actions\CreateComputerAction;
use App\Http\Requests\StoreComputerRequest;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;

class RoomComputerController extends Controller
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
}
