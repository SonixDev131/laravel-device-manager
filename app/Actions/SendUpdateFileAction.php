<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AgentPackage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class SendUpdateFileAction
{
    public function handle(string $version): BinaryFileResponse
    {
        $updateFile = AgentPackage::query()
            ->where('version', $version)
            ->value('path');

        return response()->download(Storage::path($updateFile));
    }
}
