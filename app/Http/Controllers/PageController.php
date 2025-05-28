<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PageController extends Controller
{
    public function home()
    {
        return view('pages.home');
    }

    public function about()
    {
        return view('pages.about');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function profile()
    {
        return view('pages.profile');
    }

    public function editProfile()
    {
        $user = Auth::user();
        return view('pages.profile_edit', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:20|regex:/^[\p{L}\s\'\-]+$/u',
        ], [
            'name.regex' => 'The name may only contain letters, spaces, apostrophes, and hyphens.',
            'name.max' => 'The name may not be greater than 20 characters.',
        ]);

        $user = Auth::user();

        // Sanitize the name: trim whitespace and normalize spaces
        $sanitizedName = trim(preg_replace('/\s+/', ' ', $request->name));

        $user->name = $sanitizedName;
        $user->save();

        return redirect()->route('profile')->with('success', 'Profile updated successfully!');
    }
}
