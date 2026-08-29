<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountSecurityController extends Controller
{
    /**
     * Show the account security page (password change).
     */
    public function edit(Request $request): View
    {
        return view('account.security.edit', [
            'user' => $request->user(),
        ]);
    }
}
