<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $body = "From: {$validated['name']} <{$validated['email']}>\n\n{$validated['message']}";

        Mail::raw($body, function ($message) use ($validated) {
            $message->to(config('mail.from.address'))
                ->subject('Ledger contact: '.$validated['name']);
        });

        return redirect()
            ->route('home')
            ->with('contactSuccess', true);
    }
}
