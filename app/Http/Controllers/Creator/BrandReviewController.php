<?php

namespace App\Http\Controllers\Creator;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\BrandReview;
use App\Models\ProductApplication;
use App\Notifications\BrandReviewed;
use Illuminate\Http\Request;

class BrandReviewController extends Controller
{
    // PUT /api/creator/collaborations/{id}/review  (id = application id). Creates or edits.
    public function save(Request $request, int $id)
    {
        $application = $this->mine($request, $id);

        abort_unless(
            $application->canBeReviewed(),
            422,
            'You can review a brand after your deliverables are approved or the brand has paid you.'
        );

        $data = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'body'   => 'nullable|string|max:1000',
        ]);
        $body = isset($data['body']) ? trim($data['body']) : null;

        $review = BrandReview::firstOrNew(['application_id' => $application->id]);
        $isNew = ! $review->exists;
        $review->fill([
            'creator_id' => $request->user()->id,
            'brand_id'   => $application->product->brand_id,
            'rating'     => $data['rating'],
            'body'       => $body === '' ? null : $body,
        ])->save();

        if ($isNew) {
            $application->product->brand->notify(new BrandReviewed($review->setRelation('application', $application)));
        }

        return response()->json(['data' => self::present($review)]);
    }

    // DELETE /api/creator/collaborations/{id}/review
    public function destroy(Request $request, int $id)
    {
        $this->mine($request, $id)->review()->delete();

        return response()->json(['message' => 'Review removed']);
    }

    public static function present(?BrandReview $r): ?array
    {
        return $r ? ['id' => $r->id, 'rating' => $r->rating, 'body' => $r->body, 'updated_at' => $r->updated_at] : null;
    }

    private function mine(Request $request, int $id): ProductApplication
    {
        return ProductApplication::with(['product.brand', 'tasks'])
            ->where('creator_id', $request->user()->id)
            ->where('status', ApplicationStatus::APPROVED->value)
            ->findOrFail($id);
    }
}
