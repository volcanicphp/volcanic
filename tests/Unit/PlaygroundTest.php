<?php

declare(strict_types=1);

use Volcanic\Playground;

beforeEach(function (): void {
    Playground::reset();
});

test('playground is accessible in local environment by default', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    expect(Playground::check())->toBeTrue();
});

test('playground is accessible in development environment by default', function (): void {
    app()->detectEnvironment(fn (): string => 'development');

    expect(Playground::check())->toBeTrue();
});

test('playground is not accessible in production environment by default', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    expect(Playground::check())->toBeFalse();
});

test('playground can be enabled globally with boolean true', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    Playground::canAccess(true);

    expect(Playground::check())->toBeTrue();
});

test('playground can be disabled globally with boolean false', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    Playground::canAccess(false);

    expect(Playground::check())->toBeFalse();
});

test('playground can use custom closure for access control', function (): void {
    Playground::canAccess(fn (): bool => app()->environment('staging'));

    app()->detectEnvironment(fn (): string => 'staging');
    expect(Playground::check())->toBeTrue();

    app()->detectEnvironment(fn (): string => 'production');
    expect(Playground::check())->toBeFalse();
});

test('playground reset restores default behavior', function (): void {
    Playground::canAccess(true);
    expect(Playground::check())->toBeTrue();

    Playground::reset();

    app()->detectEnvironment(fn (): string => 'production');
    expect(Playground::check())->toBeFalse();
});
