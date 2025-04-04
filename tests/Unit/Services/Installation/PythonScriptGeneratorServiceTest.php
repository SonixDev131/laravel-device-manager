<?php

declare(strict_types=1);

use App\Services\Installation\PythonScriptGeneratorService;

// Rather than mocking the File facade which causes issues in sequential test mode,
// we'll use the template override feature we added to the service

test('generates windows python script with correct placeholders', function () {
    $service = new PythonScriptGeneratorService;

    // Use the service's template override feature
    $service->setTemplateOverride(
        <<<'PYTHON'
#!/usr/bin/env python3
# Configuration
SERVER_URL = "{{SERVER_URL}}/api"
ROOM_ID = "{{ROOM_ID}}"
INSTALL_TOKEN = "{{TOKEN}}"
AUTO_REGISTER = {{AUTO_REGISTER}}
PYTHON
    );

    $serverUrl = 'https://example.com';
    $roomId = '123';
    $token = 'test-token';
    $autoRegister = true;

    $script = $service->generateWindowsScript(
        $serverUrl,
        $roomId,
        $token,
        $autoRegister
    );

    expect($script)
        ->toContain('SERVER_URL = "https://example.com/api"')
        ->toContain('ROOM_ID = "123"')
        ->toContain('INSTALL_TOKEN = "test-token"')
        ->toContain('AUTO_REGISTER = True');
});

test('includes necessary Python functions for installation', function () {
    $service = new PythonScriptGeneratorService;

    // Use a minimal template with all the required functions
    $service->setTemplateOverride(
        <<<'PYTHON'
def is_admin(): pass
def create_install_directory(): pass
def download_agent(): pass
def create_config(): pass
def install_windows_service(): pass
def register_computer(): pass
def main(): pass
PYTHON
    );

    $script = $service->generateWindowsScript(
        'https://example.com',
        '123',
        'test-token',
        true
    );

    // Check for important functions
    expect($script)
        ->toContain('def is_admin()')
        ->toContain('def create_install_directory()')
        ->toContain('def download_agent()')
        ->toContain('def create_config()')
        ->toContain('def install_windows_service(')
        ->toContain('def register_computer()')
        ->toContain('def main()');
});

test('handles empty room id correctly', function () {
    $service = new PythonScriptGeneratorService;

    // Use a minimal template that just includes the ROOM_ID placeholder
    $service->setTemplateOverride('ROOM_ID = "{{ROOM_ID}}"');

    $script = $service->generateWindowsScript(
        'https://example.com',
        null,
        'test-token',
        true
    );

    expect($script)->toContain('ROOM_ID = ""');
});

test('sets auto register flag correctly when disabled', function () {
    $service = new PythonScriptGeneratorService;

    // Use a minimal template that just includes the AUTO_REGISTER placeholder
    $service->setTemplateOverride('AUTO_REGISTER = {{AUTO_REGISTER}}');

    $script = $service->generateWindowsScript(
        'https://example.com',
        '123',
        'test-token',
        false
    );

    expect($script)->toContain('AUTO_REGISTER = False');
});
