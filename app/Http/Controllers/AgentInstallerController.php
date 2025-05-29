<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Installer;
use Illuminate\Http\Request;

class AgentInstallerController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $installerId)
    {
        $installer = Installer::findOrFail($installerId);

        return response()->download(storage_path('app/public/'.$installer->file_path));
    }
}
