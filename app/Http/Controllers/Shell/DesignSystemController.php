<?php

namespace App\Http\Controllers\Shell;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoData;
use Inertia\Inertia;
use Inertia\Response;

/** Developer reference page with demo data. Exists only in local development and automated tests. */
class DesignSystemController extends Controller
{
    public function __invoke(): Response
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        return Inertia::render('DesignSystem', ['demoCampaigns' => DemoData::campaigns()]);
    }
}
