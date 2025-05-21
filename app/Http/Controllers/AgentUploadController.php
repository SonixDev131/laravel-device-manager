<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UploadAgentRequest;
use App\Models\AgentPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ZanySoft\Zip\Facades\Zip;

class AgentUploadController extends Controller
{
    /**
     * Handle the incoming agent package upload request.
     *
     * This method:
     * 1. Validates the uploaded zip file
     * 2. Extracts individual files from the zip to a temporary directory
     * 3. Renames files to UUID.extension and moves to final destination
     * 4. Creates an AgentPackage record for each file
     * 5. Sets all files from this version as the latest
     */
    public function __invoke(UploadAgentRequest $request): JsonResponse
    {
        try {
            // Validate file presence and integrity
            if (! $request->hasFile('file')) {
                return response()->json(['error' => 'No valid file uploaded'], 422);
            }

            // Get validated data
            $file = $request->file('file');
            $version = $request->validated('version');
            $extractPath = 'agent-packages/'.$version;
            $tempPath = 'temp/'.Str::uuid(); // Temporary directory for extraction

            // Ensure directories exist
            Storage::makeDirectory($extractPath);
            Storage::makeDirectory($tempPath);

            // Open and validate zip file
            $zip = Zip::open($file->path());

            // Extract zip to temporary directory
            $zip->extract(Storage::path($tempPath));
            $zip->close();

            // Mark existing packages as not latest
            AgentPackage::query()->where('is_latest', true)->update(['is_latest' => false]);

            // Process each extracted file
            $createdFiles = [];
            $extractedFiles = Storage::allFiles($tempPath);
            foreach ($extractedFiles as $extractedFile) {
                $originalFileName = basename($extractedFile);
                $extension = pathinfo($extractedFile, PATHINFO_EXTENSION);
                $uuidName = Str::uuid().($extension ? '.'.$extension : '');
                $relativePath = $extractPath.'/'.$uuidName;
                $fullPath = Storage::path($relativePath);

                // Calculate file hash
                $fileHash = hash_file(
                    config('app.uploads.hash'),
                    Storage::path($extractedFile)
                );

                /* Deduplicate physical files */
                $existingPackage = AgentPackage::where('file_hash', $fileHash)->first();

                // Check if a file with the same hash already exists in the same directory
                $existingInSameDir = AgentPackage::where('file_hash', $fileHash)
                    ->where('version', $version)
                    ->first();

                if ($existingInSameDir) {
                    Log::info('File with same hash already exists in target directory', [
                        'file_name' => $originalFileName,
                        'file_hash' => $fileHash,
                        'existing_path' => $existingInSameDir->path,
                        'skipping_path' => $relativePath,
                    ]);

                    $existingInSameDir->is_latest = true;
                    $existingInSameDir->save();

                    // Skip this file as it's already in the target directory
                    continue;
                }
                if ($existingPackage) {
                    Log::info('Copying existing file for new version', [
                        'file_name' => $originalFileName,
                        'file_hash' => $fileHash,
                        'existing_path' => $existingPackage->path,
                        'new_path' => $relativePath,
                    ]);
                    Storage::copy($existingPackage->path, $relativePath);
                } else {
                    Storage::move($extractedFile, $relativePath);
                }

                // Verify file exists
                if (! file_exists($fullPath)) {
                    Storage::deleteDirectory($tempPath);

                    return response()->json(['error' => 'Failed to process file: '.$uuidName], 422);
                }

                // Create AgentPackage record
                $package = AgentPackage::query()->create([
                    'name' => $uuidName, // UUID-based filename
                    'file_name' => $originalFileName, // Original filename
                    'mime_type' => mime_content_type($fullPath),
                    'path' => $relativePath,
                    'disk' => config('app.uploads.disk'),
                    'file_hash' => $fileHash,
                    'version' => $version,
                    'is_latest' => true,
                    'size' => filesize($fullPath),
                ]);

                $createdFiles[] = $package;
            }

            // Clean up temporary directory
            Storage::deleteDirectory($tempPath);

            // Prepare response
            $response = [
                'message' => 'Agent package uploaded and processed successfully',
                'version' => $version,
                'files_processed' => count($createdFiles),
            ];

            return response()->json($response, 201);
        } catch (Throwable $e) {
            Log::error("Agent package upload error: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            // Clean up temporary directory on error if it was created
            if (isset($tempPath)) {
                Storage::deleteDirectory($tempPath);
            }

            return response()->json(['error' => 'Upload failed: '.$e->getMessage()], 422);
        }
    }
}
