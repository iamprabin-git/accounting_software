<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = (int) $request->user()->company_id;

        $reviews = Review::query()
            ->where('company_id', $companyId)
            ->latest('id')
            ->get()
            ->map(fn (Review $review) => [
                'id' => $review->id,
                'author_name' => $review->author_name,
                'author_email' => $review->author_email,
                'title' => $review->title,
                'body' => $review->body,
                'rating' => (int) $review->rating,
                'status' => $review->status,
                'created_at' => optional($review->created_at)?->toDateTimeString(),
            ])
            ->all();

        return Inertia::render('Company/Reviews/Index', [
            'reviews' => $reviews,
        ]);
    }

    public function approve(Request $request, Review $review): RedirectResponse
    {
        $this->assertCompanyReview($request, $review);

        $review->update([
            'status' => Review::STATUS_APPROVED,
        ]);

        return back();
    }

    public function reject(Request $request, Review $review): RedirectResponse
    {
        $this->assertCompanyReview($request, $review);

        $review->update([
            'status' => Review::STATUS_REJECTED,
            'admin_notes' => (string) $request->input('admin_notes', ''),
        ]);

        return back();
    }

    private function assertCompanyReview(Request $request, Review $review): void
    {
        abort_unless((int) $review->company_id === (int) $request->user()->company_id, 404);
    }
}
