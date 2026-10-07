<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class StaticPageController extends Controller
{
    public function __invoke(string $page): View
    {
        abort_unless(view()->exists("site.pages.{$page}"), 404);

        return view("site.pages.{$page}");
    }
}
