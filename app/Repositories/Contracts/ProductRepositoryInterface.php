<?php

namespace App\Repositories\Contracts;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveProducts(array $filters = [], int $perPage = 15);
    public function getByBrand(int $brandId);
    public function getHighCommission(int $limit = 10);
    public function getCategories(): array;
}