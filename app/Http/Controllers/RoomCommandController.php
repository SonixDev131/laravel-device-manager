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
        // Xử lý các trường hợp khác nhau dựa trên target_type
        // Ví dụ: nếu target_type là 'single', bạn có thể cần lấy computer_id từ validated dữ liệu
        // và gửi lệnh đến máy tính cụ thể đó.
        // Nếu target_type là 'group', bạn có thể cần lấy danh sách computer_ids từ validated dữ liệu
        // và gửi lệnh đến tất cả các máy tính trong nhóm đó.
        // Nếu target_type là 'all', bạn có thể gửi lệnh đến tất cả các máy tính trong phòng.
        $computerIds = match ($validated['target_type']) {
            'single' => $validated['computer_id'],
            'group' => $validated['computer_ids'] ?? $room->computers()->pluck('id')->toArray(),
        };

        // Gửi lệnh đến từng máy tính
        foreach ($computerIds as $computerId) {
            $action->handle($computerId, $room->id, $commandType);
        }

        return to_route('rooms.show', $room->id);
    }
}
