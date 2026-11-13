<?php

namespace App\Http\Controllers\Creator;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\CollaborationTask;
use App\Models\ProductApplication;
use Illuminate\Http\Request;

class CollaborationController extends Controller
{
    // GET /api/creator/collaborations
    public function index(Request $request)
    {
        $collabs = ProductApplication::with(['product.brand:id,name,avatar', 'product.variations', 'tasks'])
            ->where('creator_id', $request->user()->id)
            ->where('status', ApplicationStatus::APPROVED->value)
            ->latest('updated_at')
            ->get()
            ->each(fn ($a) => $this->seedChecklist($a))
            ->map(function ($a) {
                $p = $a->product;
                $end = $p->campaign_end;
                $daysLeft = $end ? (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false) : null;

                $state = match (true) {
                    $p->status === 'closed', $daysLeft !== null && $daysLeft < 0 => 'ended',
                    $daysLeft !== null && $daysLeft <= 14 => 'ending_soon',
                    default => 'active',
                };

                return [
                    'id'            => $a->id,
                    'approved_at'   => $a->updated_at,
                    'pitch_message' => $a->pitch_message,
                    'state'         => $state,
                    'tasks'         => $a->tasks->map(fn ($t) => $this->present($t))->values(),
                    'days_left'     => $daysLeft,
                    'product'       => [
                        'id'              => $p->id,
                        'name'            => $p->name,
                        'description'     => $p->description,
                        'images'          => $p->images,
                        'category'        => $p->category,
                        'price'           => $p->price,
                        'commission_rate' => $p->commission_rate,
                        'commission_type' => $p->commission_type,
                        'campaign_start'  => $p->campaign_start,
                        'campaign_end'    => $p->campaign_end,
                        'status'          => $p->status,
                        'platforms'       => $p->platforms,
                        'content_types'   => $p->content_types,
                        'posts_required'  => $p->posts_required,
                        'brief'           => $p->brief,
                        'hashtags'        => $p->hashtags,
                        'product_url'     => $p->product_url,
                        'free_sample'     => $p->free_sample,
                        'flat_fee'        => $p->flat_fee,
                        'brand'           => ['name' => $p->brand?->name, 'avatar' => $p->brand?->avatar],
                        'variations'      => $p->variations->map(fn ($v) => [
                            'id'      => $v->id,
                            'name'    => $v->name,
                            'options' => $v->options,
                        ])->values(),
                    ],
                ];
            });

        return response()->json(['data' => $collabs]);
    }

    // POST /api/creator/collaborations/{id}/tasks
    public function storeTask(Request $request, int $id)
    {
        $application = $this->ownApplication($request, $id);
        $data = $this->validated($request);

        $task = $application->tasks()->create($data + [
            'position' => (int) $application->tasks()->max('position') + 1,
        ]);

        return response()->json(['data' => $this->present($task)], 201);
    }

    // PATCH /api/creator/collaborations/{id}/tasks/{taskId}
    public function updateTask(Request $request, int $id, int $taskId)
    {
        $task = $this->ownApplication($request, $id)->tasks()->findOrFail($taskId);

        $data = $request->validate([
            'title'    => 'sometimes|required|string|max:120',
            'type'     => 'sometimes|in:' . implode(',', CollaborationTask::TYPES),
            'due_date' => 'sometimes|nullable|date',
            'link'     => 'sometimes|nullable|url|max:255',
            'done'     => 'sometimes|boolean',
        ]);

        if (array_key_exists('done', $data)) {
            $data['completed_at'] = $data['done'] ? ($task->completed_at ?? now()) : null;
            unset($data['done']);
        }

        $task->update($data);

        return response()->json(['data' => $this->present($task->fresh())]);
    }

    // DELETE /api/creator/collaborations/{id}/tasks/{taskId}
    public function destroyTask(Request $request, int $id, int $taskId)
    {
        $this->ownApplication($request, $id)->tasks()->findOrFail($taskId)->delete();

        return response()->json(['message' => 'Task deleted.']);
    }

    private function ownApplication(Request $request, int $id): ProductApplication
    {
        return ProductApplication::where('creator_id', $request->user()->id)
            ->where('status', ApplicationStatus::APPROVED->value)
            ->findOrFail($id);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'    => 'required|string|max:120',
            'type'     => 'sometimes|in:' . implode(',', CollaborationTask::TYPES),
            'due_date' => 'nullable|date',
            'link'     => 'nullable|url|max:255',
        ]);
    }

    private function present(CollaborationTask $t): array
    {
        return [
            'id'           => $t->id,
            'title'        => $t->title,
            'type'         => $t->type,
            'due_date'     => $t->due_date?->toDateString(),
            'link'         => $t->link,
            'done'         => $t->completed_at !== null,
            'completed_at' => $t->completed_at,
        ];
    }

    // First view of an approved collab: build checklist from the campaign brief
    private function seedChecklist(ProductApplication $a): void
    {
        if ($a->checklist_seeded) {
            return;
        }

        $p = $a->product;
        $due = $p->campaign_end?->toDateString();
        $types = $p->content_types ?? [];
        $type = in_array('short_video', $types) || in_array('review', $types) || in_array('unboxing', $types) ? 'video' : 'post';
        $rows = [];

        if ($p->free_sample) {
            $rows[] = ['title' => 'Receive free sample', 'type' => 'sample', 'due_date' => null];
        }
        $n = max(1, (int) $p->posts_required);
        for ($i = 1; $i <= $n; $i++) {
            $rows[] = ['title' => $n > 1 ? "Publish post {$i} of {$n}" : 'Publish post', 'type' => $type, 'due_date' => $due];
        }
        if ($p->hashtags) {
            $rows[] = ['title' => "Use hashtags {$p->hashtags}", 'type' => 'other', 'due_date' => null];
        }

        foreach ($rows as $i => $row) {
            $a->tasks()->create($row + ['position' => $i]);
        }
        $a->update(['checklist_seeded' => true]);
        $a->load('tasks');
    }
}
