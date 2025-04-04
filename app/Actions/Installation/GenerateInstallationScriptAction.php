<?php

declare(strict_types=1);

namespace App\Actions\Installation;

use App\Services\Installation\InstallationTokenService;
use App\Services\Installation\PythonScriptGeneratorService;
use InvalidArgumentException;

class GenerateInstallationScriptAction
{
    public function __construct(
        private readonly InstallationTokenService $tokenService,
        private readonly PythonScriptGeneratorService $scriptGenerator,
    ) {}

    /**
     * Generate an installation script for the requested OS.
     *
     * Currently, only Windows is supported.
     */
    public function execute(
        string $osType,
        string $serverUrl,
        ?string $roomId = null,
        bool $autoRegister = true
    ): string {
        // Generate and store installation token
        $token = $this->tokenService->createToken($roomId);

        // Currently only supporting Windows
        if ($osType !== 'windows') {
            throw new InvalidArgumentException('Currently only Windows installation is supported with Python scripts');
        }

        return $this->scriptGenerator->generateWindowsScript(
            $serverUrl,
            $roomId,
            $token,
            $autoRegister
        );
    }
}
