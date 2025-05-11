<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateDeltaPackageAction;
use App\Http\Requests\Api\Agent\UpdateAgentRequest;

class AgentUpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        UpdateAgentRequest $request,
        CreateDeltaPackageAction $createDeltaPackageAction
    ) {
        $clientHashTable = $request->validated('hash_table');

        // Update - send only changed files
        return $createDeltaPackageAction->handle($clientHashTable);
    }
}
