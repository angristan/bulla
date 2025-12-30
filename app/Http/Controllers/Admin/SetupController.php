<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SetupAdmin;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    /**
     * Show setup wizard.
     */
    public function show(): Response
    {
        return Inertia::render('Setup');
    }

    /**
     * Handle setup submission.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_url' => ['required', 'url', 'max:1024'],
            'username' => ['required', 'string', 'min:3', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        SetupAdmin::run(
            $validated['username'],
            $validated['email'],
            $validated['password'],
            $validated['site_name'],
            $validated['site_url']
        );

        // Auto-login after setup
        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);

        return redirect()->route('admin.dashboard');
    }
}
