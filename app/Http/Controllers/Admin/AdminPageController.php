<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Navigation;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminPageController extends Controller
{
    public function overview(): Response
    {
        return Inertia::render('admin/Overview');
    }

    /** Each admin section requires its own permission, checked here on the server. */
    public function section(string $section): Response
    {
        $item = Navigation::find('admin', $section);
        abort_if($item === null || $item['key'] === 'overview', 404);

        $permission = Navigation::permissionFor($section);
        if ($permission !== null) {
            Gate::authorize($permission->value);
        }

        return Inertia::render('admin/Placeholder', ['title' => $item['label'], 'batch' => $item['batch']]);
    }
}
