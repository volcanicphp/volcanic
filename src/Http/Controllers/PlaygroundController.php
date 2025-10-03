<?php

declare(strict_types=1);

namespace Volcanic\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Volcanic\Playground;

class PlaygroundController extends Controller
{
    /**
     * Display the API playground interface.
     */
    public function __invoke(): View
    {
        if (! Playground::check()) {
            abort(403, 'Playground is not accessible in this environment.');
        }

        return view('volcanic::playground');
    }
}
