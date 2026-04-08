<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user() !== null, 403);
        abort_unless((int) $request->user()->company_id > 0, 403);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:1500'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $user = $request->user();

        Review::query()->create([
            'author_name' => $user->name,
            'author_email' => $user->email,
            'title' => $validated['title'] ?: null,
            'body' => $validated['body'],
            'rating' => (int) $validated['rating'],
            'status' => Review::STATUS_PENDING,
            'company_id' => $user->company_id,
        ]);

        return redirect()
            ->route('home')
            ->with('reviewSuccess', true);
    }
}
