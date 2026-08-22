<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;
    protected function ensureBelongsToStore(Request $request, object $resource): void
    {
        abort_unless(
            isset($resource->store_id)
                && $resource->store_id === $request->user()->store_id,
            404
        );
    }
}
