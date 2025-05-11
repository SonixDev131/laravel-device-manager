<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AgentPackage;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZanySoft\Zip\Facades\Zip;
use ZipArchive;

final class CreateDeltaPackageAction
{
    public function handle(array $clientHashTable)
    {
        // 1. Compare hash table with server hash table
        $serverHashTable = AgentPackage::query()
            ->where('is_latest', true)
            ->select(['file_name', 'file_hash'])
            ->get()
            ->pluck('file_hash', 'file_name')
            ->toArray();

        // Collect all unique filenames from both hash tables
        $allFilenames = array_unique(array_merge(array_keys($serverHashTable), array_keys($clientHashTable)));
        $deltaPackage = [];

        // Files that are new on the server ($clientHash is null).
        // Files that exist on both but have different hashes.
        foreach ($allFilenames as $filename) {
            $serverHash = $serverHashTable[$filename] ?? null;
            $clientHash = $clientHashTable[$filename] ?? null;

            // Add if file is new on server or has changed
            if ($serverHash !== $clientHash) {
                $deltaPackage[] = $filename;
            }
        }

        // 2. Zip all delta package files
        // Get the file paths for the files in the delta package
        $filePaths = AgentPackage::query()
            ->whereIn('file_name', $deltaPackage)
            ->where('is_latest', true)
            ->pluck('path', 'file_name')
            ->toArray();

        $zip = new ZipArchive;
        $zipFileName = 'delta_package.zip';
        $zipPath = Storage::path('temp/'.$zipFileName);

        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            foreach ($filePaths as $filename => $path) {
                Log::info('Adding file to delta package', ['filename' => $filename, 'path' => $path]);
                $fullPath = Storage::path($path);
                $zip->addFile($fullPath, $filename);
            }
            $zip->close();

            Log::info('Delta package created', ['path' => $zipPath]);
        } else {
            Log::error('Failed to create delta package');
            throw new Exception('Could not create ZIP file');
        }

        // 3. Return zip file
        return response()->download($zipPath);
    }
}
