<?php

declare(strict_types=1);

use App\Actions\CreateMetricAction;
use App\Console\Commands\UpdateComputerTimeoutStatusCommand;
use App\Services\RabbitMQService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use PhpAmqpLib\Message\AMQPMessage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Define the application's command schedule.
 */
Schedule::command(UpdateComputerTimeoutStatusCommand::class, ['--timeout=5'])
    ->everyMinute()
    ->appendOutputTo(storage_path('logs/computer-timeout-updates.log'))
    ->withoutOverlapping();

Artisan::command('rabbit:listen', function (RabbitMQService $rabbitMQService, CreateMetricAction $action) {
    $callback = function (AMQPMessage $msg) use ($action) {
        $data = json_decode($msg->getBody(), true);
        echo ' [x] Received ', $msg->getBody(), "\n";
        echo " [x] Done\n";
        $action->handle($data);
    };
    $rabbitMQService->consume('metrics', $callback);
});
