<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LegalDocumentController extends Controller
{
    public function terms(): InertiaResponse
    {
        return Inertia::render('legal/terms');
    }

    public function privacy(): InertiaResponse
    {
        return Inertia::render('legal/privacy');
    }
}
