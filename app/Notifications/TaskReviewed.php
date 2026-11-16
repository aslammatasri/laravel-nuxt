<?php

namespace App\Notifications;

use App\Models\CollaborationTask;
use Illuminate\Notifications\Notification;

class TaskReviewed extends Notification
{
    public function __construct(
        private CollaborationTask $task
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $changes = $this->task->review_status === 'changes_requested';
        $product = $this->task->application->product->name;

        return [
            'title'          => $changes ? 'Changes requested' : 'Deliverable approved',
            'message'        => $changes
                ? "The brand asked for changes on \"{$this->task->title}\" for \"{$product}\": {$this->task->review_note}"
                : "The brand approved \"{$this->task->title}\" for \"{$product}\".",
            'application_id' => $this->task->application_id,
            'task_id'        => $this->task->id,
            'status'         => $this->task->review_status,
            'url'            => '/creator/collaborations',
        ];
    }
}
