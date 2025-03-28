<?php

namespace App\Http\Controllers;

use App\Actions\CreateRoomAction;
use App\Actions\DeleteRoomAction;
use App\Actions\UpdateRoomAction;
use App\Http\Requests\DeleteRoomRequest;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Contracts\Cache\Store;
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
            'rooms/RoomIndex',
            [
                'rooms' => RoomResource::collection(Room::all()),
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoomRequest $request, CreateRoomAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('rooms.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomRequest $request, Room $room, UpdateRoomAction $action): RedirectResponse
    {
        $action->handle($room, $request->validated());

        return redirect()->back();
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
     * Display the layout of the specified room.
     */
    public function show(Room $room): Response
    {
        $room->load('computers');

        return Inertia::render('rooms/RoomLayout', [
            'room' => RoomResource::make($room),
        ]);
    }
}
