<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\RabbitMQServiceProvider;

return [
    AppServiceProvider::class,
    RabbitMQServiceProvider::class,
];
