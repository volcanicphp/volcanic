<?php

declare(strict_types=1);

namespace Volcanic\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Volcanic\Playground;
use Volcanic\Services\SchemaService;

class PlaygroundSchemaController extends Controller
{
    public function __construct(protected SchemaService $schemaService) {}

    /**
     * Get the API schema for the playground.
     */
    public function __invoke(): JsonResponse
    {
        if (! Playground::check()) {
            abort(403, 'Playground is not accessible in this environment.');
        }

        $schema = $this->schemaService->getSchema();

        return response()->json($schema);
    }
}
