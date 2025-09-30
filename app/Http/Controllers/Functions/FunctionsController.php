<?php

namespace App\Http\Controllers\Functions;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class FunctionsController extends Controller
{
    /**
     * Display the Functions landing page.
     *
     * @return View The functions index view
     */
    public function index(): View
    {
        /** @var View $view */
        $view = view('functions.index');
        return $view;
    }
}
