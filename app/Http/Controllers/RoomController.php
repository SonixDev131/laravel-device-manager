<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateRoomAction;
use App\Actions\DeleteRoomAction;
use App\Actions\UpdateRoomAction;
use App\Http\Requests\DeleteRoomRequest;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Computer;
use App\Models\Room;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class RoomController extends Controller
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

    /**
     * Import rooms and computers from JSON data or file upload.
     *
     * @throws ValidationException
     */
    public function import(Request $request)
    {
        $request->validate([
            'jsonFile' => 'required|file|mimes:json',
        ]);

        try {
            $file = $request->file('jsonFile');
            $contents = $file->get();
            $data = json_decode($contents, true);

            debug($data);

            foreach ($data['rooms'] as $roomData) {
                $room = Room::updateOrCreate(
                    ['name' => $roomData['name']],
                    ['grid_rows' => $roomData['grid_rows'], 'grid_cols' => $roomData['grid_cols']],
                );

                foreach ($roomData['computers'] as $computerData) {
                    Computer::updateOrCreate(
                        ['mac_address' => $computerData['mac_address']],
                        [
                            'room_id' => $room->id,
                            'hostname' => $computerData['hostname'] ?? '',
                            'pos_row' => $computerData['pos_row'],
                            'pos_col' => $computerData['pos_col'],
                        ]
                    );
                }
            }

            return redirect()->back()->with('success', 'Import thành công');
        } catch (Exception $e) {
            Log::error("Lỗi import file: {$e->getMessage()}");

            return response()->json(['error' => 'Lỗi import file'], 500);
        }
    }
}
