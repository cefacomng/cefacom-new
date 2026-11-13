<?php

namespace App\Http\Controllers\Shell;

use App\Http\Controllers\Controller;
use App\Support\Navigation;
use Inertia\Inertia;
use Inertia\Response;

/** Customer pages whose features are built in later batches render an honest placeholder naming the batch. */
class CustomerPageController extends Controller
{
    public function overview(): Response
    {
        return Inertia::render('app/Overview');
    }

    public function section(string $section): Response
    {
        $item = Navigation::find('customer', $section);
        abort_if($item === null || in_array($item['key'], ['overview', 'settings'], true), 404);

        return Inertia::render('app/Placeholder', ['title' => $item['label'], 'batch' => $item['batch']]);
    }
}
