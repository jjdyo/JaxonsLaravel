<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Display the home page
     *
     * @return \Illuminate\Contracts\View\View The home page view
     */
    public function home(): View
    {
        /** @var View $view */
        $view = view('pages.home');
        return $view;
    }

    /**
     * Display the about page
     *
     * @return \Illuminate\Contracts\View\View The about page view
     */
    public function about(): View
    {
        /** @var View $view */
        $view = view('pages.about');
        return $view;
    }

    /**
     * Display the functions page
     *
     * @return \Illuminate\Contracts\View\View The functions page view
     */
    public function functions(): View
    {
        /** @var View $view */
        $view = view('pages.functions');
        return $view;
    }


}
