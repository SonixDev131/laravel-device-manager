<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateAllAgentsAction;
use App\Http\Requests\UploadAgentRequest;
use App\Http\Requests\UploadInstallerRequest;
use App\Models\AgentPackage;
use App\Models\Computer;
use App\Models\Installer;
use App\Services\RabbitMQService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AgentsController extends Controller
{
    /**
     * Display the agents management page.
     */
    public function index(): Response
    {
        // Get computers summarized by status
        $computerStats = Computer::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status->value => $item->count];
            })
            ->toArray();

        // Get total count
        $totalComputers = Computer::query()->count();

        // Get the 10 most recently active computers
        $recentComputers = Computer::query()
            ->with('room')
            ->orderByDesc('last_heartbeat_at')
            ->limit(10)
            ->get();

        // Get all agent packages
        $packages = AgentPackage::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'file_name' => $package->file_name,
                    'version' => $package->version,
                    'size' => $package->size,
                    'is_latest' => $package->is_latest,
                    'created_at' => $package->created_at->toISOString(),
                ];
            });

        // Get all installers
        $installers = Installer::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($installer) {
                return [
                    'id' => $installer->id,
                    'name' => $installer->name,
                    'description' => $installer->description,
                    'file_name' => $installer->file_name,
                    'file_size' => $installer->file_size,
                    'auto_install' => $installer->auto_install,
                    'install_args' => $installer->install_args,
                    'created_at' => $installer->created_at->toISOString(),
                ];
            });

        return Inertia::render('agents/Index', [
            'stats' => [
                'total' => $totalComputers,
                'online' => $computerStats['online'] ?? 0,
                'idle' => $computerStats['idle'] ?? 0,
                'offline' => $computerStats['offline'] ?? 0,
                'timeout' => $computerStats['timeout'] ?? 0,
            ],
            'recentComputers' => $recentComputers,
            'packages' => $packages,
            'installers' => $installers,
        ]);
    }

    /**
     * Initiate update for all agents.
     */
    public function updateAll(Request $request, UpdateAllAgentsAction $action): JsonResponse
    {
        $result = $action->handle($request);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'total' => $result['total'],
                    'updated' => $result['updated'],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'data' => [
                'total' => $result['total'] ?? 0,
                'updated' => $result['updated'] ?? 0,
                'failed' => $result['failed'] ?? 0,
            ],
        ], 500);
    }

    public function uploadPackage(UploadAgentRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $file = $validated['file'];
            $version = $validated['version'];

            Storage::putFileAs('updates', $file, "{$version}.zip");

            AgentPackage::query()->where('is_latest', true)->update(['is_latest' => false]);
            AgentPackage::query()->create([
                'name' => "{$version}.zip",
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'path' => "updates/{$version}.zip",
                'disk' => config('app.uploads.disk'),
                'file_hash' => hash_file(config('app.uploads.hash'), Storage::path("updates/{$version}.zip")),
                'version' => $version,
                'is_latest' => true,
                'size' => $file->getSize(),
            ]);

            return redirect()->back()->with('success', 'Agent package uploaded successfully');
        } catch (Throwable $e) {
            Storage::delete("updates/{$version}.zip");
            Log::error("Agent package upload error: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Upload failed: '.$e->getMessage());
        }
    }

    /**
     * Delete an agent package.
     */
    public function deletePackage(string $packageId): RedirectResponse
    {
        try {
            $package = AgentPackage::query()->findOrFail($packageId);

            // Delete the file from storage
            Storage::delete($package->path);

            // If this was the latest package, set the most recent remaining package as latest
            if ($package->is_latest) {
                $latestPackage = AgentPackage::query()
                    ->where('id', '!=', $package->id)
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestPackage) {
                    $latestPackage->update(['is_latest' => true]);
                }
            }

            // Delete the package record
            $package->delete();

            return redirect()->back()->with('success', 'Agent package deleted successfully');
        } catch (Throwable $e) {
            Log::error("Agent package deletion error: {$e->getMessage()}", [
                'package_id' => $packageId,
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Failed to delete package: '.$e->getMessage());
        }
    }

    /**
     * Upload an installer file and store its information.
     */
    public function uploadInstaller(UploadInstallerRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $file = $request->file('file');

            if (! $file) {
                return redirect()->back()->with('error', 'No file was uploaded.');
            }

            $fileName = $validated['name'].'_'.time().'.'.$file->getClientOriginalExtension();
            $filePath = $file->storeAs('installers', $fileName, 'public');

            Installer::query()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType() ?? '',
                'file_size' => $file->getSize(),
                'file_hash' => hash_file('sha256', $file->getRealPath()),
                'auto_install' => $validated['auto_install'] ?? false,
                'install_args' => $validated['install_args'] ?? null,
            ]);

            return redirect()->back()->with('success', 'Installer uploaded successfully');
        } catch (Throwable $e) {
            Log::error("Installer upload error: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Upload failed: '.$e->getMessage());
        }
    }

    /**
     * Broadcast installer download command to all agents.
     */
    public function broadcastInstaller(string $installerId, RabbitMQService $rabbitMQ): RedirectResponse
    {
        try {
            $installer = Installer::query()->findOrFail($installerId);

            // Generate download URL
            $downloadUrl = Storage::url($installer->file_path);

            // Send broadcast command to all agents
            $success = $rabbitMQ->broadcastInstallerDownload(
                $installer->id,
                $installer->name,
                $downloadUrl,
                $installer->auto_install,
                $installer->install_args
            );

            if ($success) {
                return redirect()->back()->with('success', 'Installer download command sent to all agents');
            }

            return redirect()->back()->with('error', 'Failed to send installer command - RabbitMQ unavailable');
        } catch (Throwable $e) {
            Log::error("Installer broadcast error: {$e->getMessage()}", [
                'installer_id' => $installerId,
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Failed to broadcast installer: '.$e->getMessage());
        }
    }

    /**
     * Delete an installer.
     */
    public function deleteInstaller(string $installerId): RedirectResponse
    {
        try {
            $installer = Installer::query()->findOrFail($installerId);

            // Delete the file from storage
            Storage::disk('public')->delete($installer->file_path);

            // Delete the installer record
            $installer->delete();

            return redirect()->back()->with('success', 'Installer deleted successfully');
        } catch (Throwable $e) {
            Log::error("Installer deletion error: {$e->getMessage()}", [
                'installer_id' => $installerId,
                'exception' => $e,
            ]);

            return redirect()->back()->with('error', 'Failed to delete installer: '.$e->getMessage());
        }
    }
}
