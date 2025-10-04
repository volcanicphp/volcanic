<?php

declare(strict_types=1);

use Volcanic\Playground;

beforeEach(function (): void {
    // Enable playground for tests using the canAccess callback
    Playground::canAccess(true);
});

test('schema endpoint allows requests in testing environment', function (): void {
    // In testing/local environments, same-origin checks are skipped
    $response = $this->get(route('volcanic.schema'));

    $response->assertOk();
    $response->assertJsonStructure([
        'routes',
    ]);
});

test('schema endpoint allows same-origin requests with referer', function (): void {
    $response = $this->get(route('volcanic.schema'), [
        'Referer' => 'http://localhost/volcanic',
    ]);

    $response->assertOk();
});

test('schema endpoint allows XMLHttpRequest from same domain', function (): void {
    $response = $this->get(route('volcanic.schema'), [
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();
});

test('schema endpoint respects playground enabled check', function (): void {
    Playground::canAccess(false);

    $response = $this->get(route('volcanic.schema'));

    $response->assertStatus(403);
    $response->assertSee('Playground is not accessible in this environment');
});

test('schema endpoint blocks external requests in production', function (): void {
    // Temporarily set environment to production to test security
    config(['app.env' => 'production']);

    $response = $this->get(route('volcanic.schema'), [
        'Origin' => 'https://external-app.com',
    ]);

    $response->assertStatus(403);
});

test('schema endpoint blocks external referer in production', function (): void {
    // Temporarily set environment to production to test security
    config(['app.env' => 'production']);

    $response = $this->get(route('volcanic.schema'), [
        'Referer' => 'https://external-app.com/page',
    ]);

    $response->assertStatus(403);
});
