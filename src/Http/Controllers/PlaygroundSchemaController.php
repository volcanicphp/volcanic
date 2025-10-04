<?php

declare(strict_types=1);

namespace Volcanic\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Volcanic\Playground;
use Volcanic\Services\SchemaService;

class PlaygroundSchemaController extends Controller
{
    public function __construct(protected SchemaService $schemaService) {}

    /**
     * Get the API schema for the playground.
     * Only accessible from the same application (localhost/same-origin).
     */
    public function __invoke(Request $request): JsonResponse
    {
        // First check if playground is enabled
        if (! Playground::check()) {
            abort(403, 'Playground is not accessible in this environment.');
        }

        // Only enforce same-origin check in production/non-local environments
        if (! $this->isLocalEnvironment()) {
            $this->ensureSameOriginRequest($request);
        }

        $schema = $this->schemaService->getSchema();

        return response()->json($schema);
    }

    /**
     * Check if we're in a local/development environment.
     */
    protected function isLocalEnvironment(): bool
    {
        $env = config('app.env');

        return in_array($env, ['local', 'testing', 'development'], true);
    }

    /**
     * Ensure the request is coming from the same application.
     * Blocks external applications and cross-origin requests.
     */
    protected function ensureSameOriginRequest(Request $request): void
    {
        // Get the request origin and referer
        $origin = $request->header('Origin');
        $referer = $request->header('Referer');
        $host = $request->getHost();
        $appUrl = parse_url((string) config('app.url'));
        $appHost = $appUrl['host'] ?? $host;

        // If no Origin and no Referer headers are present
        if (! $origin && ! $referer) {
            // Check if it's an AJAX request (XMLHttpRequest)
            if ($request->ajax()) {
                return; // Allow AJAX requests from same domain
            }

            // Check if it's a local/internal request
            $remoteAddr = $request->ip();

            // Allow localhost and private IP ranges
            if ($this->isLocalOrPrivateIp($remoteAddr)) {
                return;
            }

            // Block external access without proper headers
            abort(403, 'Schema endpoint is only accessible from the same application.');
        }

        // Parse and validate Origin header
        if ($origin) {
            $originParsed = parse_url($origin);
            $originHost = $originParsed['host'] ?? '';

            // Check if origin matches the application host
            if ($originHost !== $host && $originHost !== $appHost) {
                abort(403, 'Schema endpoint is only accessible from the same application.');
            }
        }

        // Parse and validate Referer header
        if ($referer) {
            $refererParsed = parse_url($referer);
            $refererHost = $refererParsed['host'] ?? '';

            // Check if referer matches the application host
            if ($refererHost !== $host && $refererHost !== $appHost) {
                abort(403, 'Schema endpoint is only accessible from the same application.');
            }
        }
    }

    /**
     * Check if the IP address is local or in a private range.
     */
    protected function isLocalOrPrivateIp(string $ip): bool
    {
        // Localhost
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
            return true;
        }

        // Private IPv4 ranges
        return str_starts_with($ip, '192.168.') ||
            str_starts_with($ip, '10.') ||
            preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip);
    }
}
