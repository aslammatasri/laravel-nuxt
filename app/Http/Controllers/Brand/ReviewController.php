<?php

namespace App\Http\Controllers\Brand;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\CollaborationTask;
use App\Models\ProductApplication;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // PATCH /api/brand/tasks/{id}/review
    public function review(Request $request, int $id)
    {
        $task = CollaborationTask::whereHas('application.product', fn ($q) => $q->where('brand_id', $request->user()->id))
            ->findOrFail($id);

        abort_unless($task->needsReview(), 422, 'This task does not need review.');
        abort_unless($task->completed_at !== null, 422, 'The creator has not marked this task done.');

        $data = $request->validate([
            'status' => 'required|in:approved,changes_requested',
            'note'   => 'required_if:status,changes_requested|nullable|string|max:500',
        ]);

        $task->update([
            'review_status' => $data['status'],
            'review_note'   => $data['status'] === 'changes_requested' ? $data['note'] : null,
            'reviewed_at'   => now(),
        ]);

        return response()->json(['data' => [
            'id'          => $task->id,
            'review'      => $task->review_status,
            'review_note' => $task->review_note,
        ]]);
    }

    // PATCH /api/brand/applications/{id}/payout
    public function markPaid(Request $request, int $id)
    {
        $application = ProductApplication::with(['product', 'tasks'])
            ->whereHas('product', fn ($q) => $q->where('brand_id', $request->user()->id))
            ->where('status', ApplicationStatus::APPROVED->value)
            ->findOrFail($id);

        abort_unless($application->feeStatus() === 'due', 422, 'The fee is not due yet. Approve all deliverables first.');

        $data = $request->validate(['reference' => 'nullable|string|max:100']);

        $application->update(['paid_at' => now(), 'payment_ref' => $data['reference'] ?? null]);

        return response()->json(['data' => $application->fresh(['product', 'tasks'])->feeSummary()]);
    }
}
