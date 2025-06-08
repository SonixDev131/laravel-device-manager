<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Screenshot;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class StoreScreenshotAction
{
    /**
     * Handle storing a screenshot file and creating the database record
     *
     * @param  array{
     *     command_id: string,
     *     computer_id: string,
     *     screenshot: UploadedFile,
     *     taken_at: string
     * }  $data Screenshot data
     * @return Screenshot The created screenshot record
     */
    public function handle(array $data): Screenshot
    {
        $file = $data['screenshot'];

        // Generate unique filename
        $filename = $this->generateUniqueFilename($file);

        // Define storage path (organized by date)
        $date = now()->format('Y/m/d');
        $storagePath = "screenshots/{$date}";

        // Store the file
        $filePath = $file->storeAs($storagePath, $filename, 'public');

        // Create database record
        return Screenshot::create([
            'command_id' => $data['command_id'],
            'computer_id' => $data['computer_id'],
            'file_path' => $filePath,
            'file_name' => $filename,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'taken_at' => $data['taken_at'],
        ]);
    }

    /**
     * Generate a unique filename for the screenshot
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $uuid = Str::uuid()->toString();

        return "screenshot_{$timestamp}_{$uuid}.{$extension}";
    }
}
