<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function ensureBelongsToStore(Request $request, object $resource): void
    {
        abort_unless(
            isset($resource->store_id)
                && $resource->store_id === $request->user()->store_id,
            404
        );
    }
}
