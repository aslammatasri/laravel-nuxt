<?php

namespace App\Repositories\Contracts;

interface ApplicationRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCreator(int $creatorId, ?string $status = null);
    public function getByProduct(int $productId, ?string $status = null);
    public function getByBrand(int $brandId, ?string $status = null);
    public function creatorAlreadyApplied(int $creatorId, int $productId): bool;
    public function approve(int $applicationId);
    public function reject(int $applicationId, string $reason);
}