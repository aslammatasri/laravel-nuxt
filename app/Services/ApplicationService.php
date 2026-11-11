<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Conversation;
use App\Models\Product;
use App\Notifications\ApplicationStatusUpdated;
use App\Notifications\NewApplicationReceived;
use App\Repositories\Contracts\ApplicationRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ApplicationService
{
    public function __construct(
        private ApplicationRepositoryInterface $applicationRepository,
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function getCreatorApplications(int $creatorId, ?string $status = null)
    {
        return $this->applicationRepository->getByCreator($creatorId, $status);
    }

    public function getBrandApplications(int $brandId, ?string $status = null, ?int $productId = null)
    {
        return $this->applicationRepository->getByBrand($brandId, $status, $productId);
    }

    public function applyToProduct(int $creatorId, int $productId, string $pitch)
    {
        $application = DB::transaction(function () use ($creatorId, $productId, $pitch) {
            // Lock the product row so concurrent submits are serialized
            $product = Product::whereKey($productId)->lockForUpdate()->firstOrFail();

            // Business rule 1 — product must be active
            if ($product->status !== 'active') {
                throw new \Exception('This product is no longer accepting applications', 422);
            }

            // Business rule 2 — no new application while one is pending or approved
            if ($this->applicationRepository->creatorAlreadyApplied($creatorId, $productId)) {
                throw new \Exception('You have already applied to this product', 422);
            }

            // Business rule 3 — check max affiliates not exceeded
            if ($product->spots_left !== null && $product->spots_left <= 0) {
                throw new \Exception('This product has reached its maximum affiliates', 422);
            }

            return $this->applicationRepository->create([
                'creator_id'    => $creatorId,
                'product_id'    => $productId,
                'pitch_message' => $pitch,
                'status'        => ApplicationStatus::PENDING,
            ]);
        });

        $application->product->brand->notify(new NewApplicationReceived($application->load(['creator', 'product'])));

        return $application;
    }

    public function approveApplication(int $applicationId, int $brandId)
    {
        $application = $this->applicationRepository->findById($applicationId);

        // Business rule — brand can only approve their own product's applications
        if ($application->product->brand_id !== $brandId) {
            throw new \Exception('Unauthorized', 403);
        }

        // Business rule — can only approve pending applications
        if ($application->status !== ApplicationStatus::PENDING) {
            throw new \Exception('This application has already been processed', 422);
        }

        $application = $this->applicationRepository->approve($applicationId);

        // Open the brand <-> creator chat for this collaboration
        Conversation::firstOrCreate(
            ['application_id' => $application->id],
            [
                'product_id' => $application->product_id,
                'brand_id'   => $application->product->brand_id,
                'creator_id' => $application->creator_id,
            ],
        );
        $application->creator->notify(new ApplicationStatusUpdated($application));

        return $application;
    }

    public function rejectApplication(int $applicationId, int $brandId, string $reason)
    {
        $application = $this->applicationRepository->findById($applicationId);

        // Business rule — brand can only reject their own product's applications
        if ($application->product->brand_id !== $brandId) {
            throw new \Exception('Unauthorized', 403);
        }

        // Business rule — can only reject pending applications
        if ($application->status !== ApplicationStatus::PENDING) {
            throw new \Exception('This application has already been processed', 422);
        }

        $application = $this->applicationRepository->reject($applicationId, $reason);
        $application->creator->notify(new ApplicationStatusUpdated($application));

        return $application;
    }
}