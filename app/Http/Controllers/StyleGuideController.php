<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StyleGuideController extends Controller
{
    /**
     * Display the style guide page.
     */
    public function index()
    {
        return view('style-guide.index');
    }
} 