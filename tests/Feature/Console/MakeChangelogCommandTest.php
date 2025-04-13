<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;

test('make:changelog command creates a properly formatted changelog file', function () {
    $taskId = 'TEST-123';
    $developer = 'Test Developer';

    $this->artisan('make:changelog', [
        'taskId' => $taskId,
        'developer' => $developer,
    ])->assertSuccessful();

    $date = now()->format('Y-m-d');
    $filename = "docs/changelogs/changelog-{$taskId}-{$date}.md";

    expect(File::exists(base_path($filename)))->toBeTrue();

    $content = File::get(base_path($filename));
    expect($content)
        ->toContain("**Task ID**: {$taskId}")
        ->toContain("**Developer**: {$developer}")
        ->toContain("**Date**: {$date}");
});
