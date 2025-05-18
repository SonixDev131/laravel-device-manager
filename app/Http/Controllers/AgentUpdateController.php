<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SendUpdateFileAction;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgentUpdateController extends Controller
{
    public function __invoke(string $version, SendUpdateFileAction $sendUpdateFileAction): BinaryFileResponse
    {
        return $sendUpdateFileAction->handle($version);
    }
}
