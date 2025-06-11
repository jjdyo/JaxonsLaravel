<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Display the home page
     *
     * @return \Illuminate\View\View The home page view
     */
    public function home(): \Illuminate\View\View
    {
        return view('pages.home');
    }

    /**
     * Display the about page
     *
     * @return \Illuminate\View\View The about page view
     */
    public function about(): \Illuminate\View\View
    {
        return view('pages.about');
    }

    /**
     * Display the contact page
     *
     * @return \Illuminate\View\View The contact page view
     */
    public function contact(): \Illuminate\View\View
    {
        return view('pages.contact');
    }

}
