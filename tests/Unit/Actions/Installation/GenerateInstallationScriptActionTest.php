<?php

declare(strict_types=1);

use App\Actions\Installation\GenerateInstallationScriptAction;
use App\Services\Installation\InstallationTokenService;
use App\Services\Installation\PythonScriptGeneratorService;

test('generates windows python script', function () {
    // Create mocks with interface contracts
    $tokenService = Mockery::mock(InstallationTokenService::class);
    $tokenService->shouldReceive('createToken')
        ->once()
        ->with('123')
        ->andReturn('generated-token');

    $scriptGenerator = Mockery::mock(PythonScriptGeneratorService::class);
    $scriptGenerator->shouldReceive('generateWindowsScript')
        ->once()
        ->with('https://example.com', '123', 'generated-token', true)
        ->andReturn('python script content');

    // Create action with mocked dependencies
    $action = new GenerateInstallationScriptAction($tokenService, $scriptGenerator);

    // Execute action
    $result = $action->execute('windows', 'https://example.com', '123', true);

    // Assert
    expect($result)->toBe('python script content');
});

test('throws exception for unsupported operating system', function () {
    // Create mocks with interface contracts
    $tokenService = Mockery::mock(InstallationTokenService::class);
    $tokenService->shouldReceive('createToken')
        ->once()
        ->with('123')
        ->andReturn('generated-token');

    $scriptGenerator = Mockery::mock(PythonScriptGeneratorService::class);

    // Create action with mocked dependencies
    $action = new GenerateInstallationScriptAction($tokenService, $scriptGenerator);

    // Execute action with unsupported OS
    expect(fn () => $action->execute('linux', 'https://example.com', '123', true))
        ->toThrow(InvalidArgumentException::class);
});

test('supports optional room id parameter', function () {
    // Create mocks with interface contracts
    $tokenService = Mockery::mock(InstallationTokenService::class);
    $tokenService->shouldReceive('createToken')
        ->once()
        ->with(null)
        ->andReturn('generated-token');

    $scriptGenerator = Mockery::mock(PythonScriptGeneratorService::class);
    $scriptGenerator->shouldReceive('generateWindowsScript')
        ->once()
        ->with('https://example.com', null, 'generated-token', true)
        ->andReturn('python script content');

    // Create action with mocked dependencies
    $action = new GenerateInstallationScriptAction($tokenService, $scriptGenerator);

    // Execute action without room id
    $result = $action->execute('windows', 'https://example.com', null, true);

    // Assert
    expect($result)->toBe('python script content');
});
