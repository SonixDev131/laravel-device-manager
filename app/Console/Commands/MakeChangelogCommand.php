<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeChangelogCommand extends Command
{
    protected $signature = 'make:changelog {taskId? : The task ID} {developer? : The developer name}';

    protected $description = 'Create a new changelog file from template';

    public function handle(): int
    {
        $taskId = $this->argument('taskId') ?? $this->ask('Enter the task ID');
        $developer = $this->argument('developer') ?? $this->ask('Enter your name');

        $date = Carbon::now()->format('Y-m-d');
        $filename = "changelog-{$taskId}-{$date}.md";

        $template = File::get(base_path('docs/changelog-template.md'));
        $content = strtr($template, [
            '[Jira/Github Issue ID]' => $taskId,
            'YYYY-MM-DD' => $date,
            '[Your Name]' => $developer,
        ]);

        File::put(base_path("docs/changelogs/{$filename}"), $content);

        $this->info("Changelog created: docs/changelogs/{$filename}");

        return self::SUCCESS;
    }
}
