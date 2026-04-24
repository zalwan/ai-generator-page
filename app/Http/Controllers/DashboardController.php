<?php

namespace App\Http\Controllers;

use App\Services\ContextManager;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ContextManager $context)
    {
        $user = $request->user();
        $bundle = $context->buildForUser($user);
        $recent = $user->salesPages()->limit(5)->get();

        return view('dashboard', [
            'recent' => $recent,
            'context' => $bundle,
            'totalPages' => $user->salesPages()->count(),
        ]);
    }
}
