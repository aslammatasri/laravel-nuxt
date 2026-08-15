<?php

namespace App\Repositories;

use App\Models\ProductApplication;
use App\Repositories\Contracts\ApplicationRepositoryInterface;

class ApplicationRepository extends BaseRepository implements ApplicationRepositoryInterface
{
    public function __construct(ProductApplication $model)
    {
        parent::__construct($model);
    }

    public function getByCreator(int $creatorId, ?string $status = null)
    {
        return $this->model
            ->with(['product', 'creator'])
            ->where('creator_id', $creatorId)
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15);
    }

    public function getByProduct(int $productId, ?string $status = null)
    {
        return $this->model
            ->with(['product', 'creator'])
            ->where('product_id', $productId)
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15);
    }

    public function getByBrand(int $brandId, ?string $status = null)
    {
        return $this->model
            ->with(['product', 'creator'])
            ->whereHas('product', fn($q) => $q->where('brand_id', $brandId))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15);
    }

    public function creatorAlreadyApplied(int $creatorId, int $productId): bool
    {
        return $this->model
            ->where('creator_id', $creatorId)
            ->where('product_id', $productId)
            ->exists();
    }

    public function approve(int $applicationId)
    {
        $application = $this->model->findOrFail($applicationId);
        $application->update(['status' => 'approved']);
        return $application;
    }

    public function reject(int $applicationId, string $reason)
    {
        $application = $this->model->findOrFail($applicationId);
        $application->update(['status' => 'rejected', 'message' => $reason]);
        return $application;
    }
}