<?php

namespace App\Http\Controllers;

use App\Actions\DeleteRoomAction;
use App\Http\Requests\DeleteRoomRequest;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render(
            'rooms/RoomsIndex',
            [
                'rooms' => RoomResource::collection(Room::all()),
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoomRequest $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomRequest $request, Room $room)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        DeleteRoomRequest $request,
        Room $room,
        DeleteRoomAction $action
    ): RedirectResponse {
        $action->handle($room);

        return to_route('rooms.index');
    }

    /**
     * Display the specified resource.
     */
    public function showLayout(Room $room)
    {
        //
    }
}
