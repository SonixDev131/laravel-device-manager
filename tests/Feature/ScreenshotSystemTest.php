<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Command;
use App\Models\Computer;
use App\Models\Role;
use App\Models\Room;
use App\Models\Screenshot;
use App\Models\User;
use App\Models\UserRoomAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScreenshotSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Create SuperAdmin role if it doesn't exist
        if (! Role::where('name', 'SuperAdmin')->exists()) {
            Role::create(['name' => 'SuperAdmin']);
        }
    }

    public function test_agent_can_upload_screenshot(): void
    {
        // Create test data
        $room = Room::factory()->create();
        $computer = Computer::factory()->create(['room_id' => $room->id]);
        $command = Command::factory()->create([
            'computer_id' => $computer->id,
            'type' => 'SCREENSHOT',
        ]);

        // Create fake image file
        $file = UploadedFile::fake()->image('screenshot.png', 1920, 1080);

        // Upload screenshot
        $response = $this->postJson('/api/agent/screenshot', [
            'command_id' => $command->id,
            'computer_id' => $computer->id,
            'screenshot' => $file,
            'taken_at' => now()->toISOString(),
        ]);

        // Assert response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'screenshot_id',
                'file_url',
            ]);

        // Assert database record created
        $this->assertDatabaseHas('screenshots', [
            'command_id' => $command->id,
            'computer_id' => $computer->id,
            'mime_type' => 'image/png',
        ]);

        // Assert file was stored
        $screenshot = Screenshot::first();
        Storage::disk('public')->assertExists($screenshot->file_path);
    }

    public function test_upload_validates_required_fields(): void
    {
        $response = $this->postJson('/api/agent/screenshot', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'command_id',
                'computer_id',
                'screenshot',
                'taken_at',
            ]);
    }

    public function test_upload_validates_file_type(): void
    {
        $room = Room::factory()->create();
        $computer = Computer::factory()->create(['room_id' => $room->id]);
        $command = Command::factory()->create(['computer_id' => $computer->id]);

        // Try to upload non-image file
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->postJson('/api/agent/screenshot', [
            'command_id' => $command->id,
            'computer_id' => $computer->id,
            'screenshot' => $file,
            'taken_at' => now()->toISOString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['screenshot']);
    }

    public function test_can_list_room_screenshots(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SuperAdmin'); // Give SuperAdmin role to bypass room access check

        $room = Room::factory()->create();
        // Assign room to user for access
        UserRoomAssignment::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'is_active' => true,
            'assigned_at' => now(),
        ]);
        $computer = Computer::factory()->create(['room_id' => $room->id]);
        $command = Command::factory()->create(['computer_id' => $computer->id]);

        // Create some screenshots
        Screenshot::factory()->count(3)->create([
            'command_id' => $command->id,
            'computer_id' => $computer->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/rooms/{$room->id}/screenshots");

        $response->assertStatus(200);

        // Debug output
        if (count($response->json('data')) !== 3) {
            dump('Expected 3 screenshots, got:', count($response->json('data')));
            dump('Response data:', $response->json('data'));
            dump('Room ID:', $room->id);
            dump('Computer ID:', $computer->id);
            dump('Computer room_id:', $computer->room_id);
            dump('Screenshots in DB:', Screenshot::all()->toArray());

            // Test the query directly
            $directQuery = Screenshot::query()
                ->with(['computer', 'command'])
                ->whereHas('computer', function ($computerQuery) use ($room) {
                    $computerQuery->where('room_id', $room->id);
                })
                ->get();
            dump('Direct query result:', $directQuery->toArray());
        }

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'computer_name',
                    'file_name',
                    'file_size_formatted',
                    'file_url',
                    'taken_at_formatted',
                ],
            ],
            'pagination' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_filter_screenshots_by_computer(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');

        $room = Room::factory()->create();
        $computer1 = Computer::factory()->create(['room_id' => $room->id]);
        $computer2 = Computer::factory()->create(['room_id' => $room->id]);
        $command1 = Command::factory()->create(['computer_id' => $computer1->id]);
        $command2 = Command::factory()->create(['computer_id' => $computer2->id]);

        // Create screenshots for different computers
        Screenshot::factory()->count(2)->create([
            'command_id' => $command1->id,
            'computer_id' => $computer1->id,
        ]);
        Screenshot::factory()->count(3)->create([
            'command_id' => $command2->id,
            'computer_id' => $computer2->id,
        ]);

        // Assign room to user for access
        UserRoomAssignment::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/rooms/{$room->id}/screenshots?computer_id={$computer1->id}");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_view_single_screenshot(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');

        $room = Room::factory()->create();
        $computer = Computer::factory()->create(['room_id' => $room->id]);
        $command = Command::factory()->create(['computer_id' => $computer->id]);
        $screenshot = Screenshot::factory()->create([
            'command_id' => $command->id,
            'computer_id' => $computer->id,
        ]);

        // Assign room to user for access
        UserRoomAssignment::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/rooms/{$room->id}/screenshots/{$screenshot->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'computer_name',
                'file_name',
                'file_url',
                'taken_at_formatted',
            ]);
    }

    public function test_cannot_view_screenshot_from_different_room(): void
    {
        $user = User::factory()->create();
        $user->assignRole('SuperAdmin');

        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();
        $computer = Computer::factory()->create(['room_id' => $room2->id]);
        $command = Command::factory()->create(['computer_id' => $computer->id]);
        $screenshot = Screenshot::factory()->create([
            'command_id' => $command->id,
            'computer_id' => $computer->id,
        ]);

        // Assign room1 to user for access (but screenshot belongs to room2)
        UserRoomAssignment::create([
            'user_id' => $user->id,
            'room_id' => $room1->id,
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/rooms/{$room1->id}/screenshots/{$screenshot->id}");

        $response->assertStatus(404);
    }

    public function test_screenshot_model_relationships(): void
    {
        $room = Room::factory()->create();
        $computer = Computer::factory()->create(['room_id' => $room->id]);
        $command = Command::factory()->create(['computer_id' => $computer->id]);
        $screenshot = Screenshot::factory()->create([
            'computer_id' => $computer->id,
            'command_id' => $command->id,
        ]);

        $this->assertInstanceOf(Computer::class, $screenshot->computer);
        $this->assertInstanceOf(Command::class, $screenshot->command);
        $this->assertEquals($computer->id, $screenshot->computer->id);
        $this->assertEquals($command->id, $screenshot->command->id);
    }

    public function test_screenshot_file_url_accessor(): void
    {
        $screenshot = Screenshot::factory()->create([
            'file_path' => 'screenshots/2025/01/15/test.png',
        ]);

        $this->assertStringContainsString('storage/screenshots/2025/01/15/test.png', $screenshot->file_url);
    }

    public function test_computer_has_screenshots_relationship(): void
    {
        $computer = Computer::factory()->create();
        Screenshot::factory()->count(3)->create(['computer_id' => $computer->id]);

        $this->assertCount(3, $computer->screenshots);
        $this->assertInstanceOf(Screenshot::class, $computer->screenshots->first());
    }

    public function test_command_has_screenshots_relationship(): void
    {
        $command = Command::factory()->create();
        Screenshot::factory()->count(2)->create(['command_id' => $command->id]);

        $this->assertCount(2, $command->screenshots);
        $this->assertInstanceOf(Screenshot::class, $command->screenshots->first());
    }
}
