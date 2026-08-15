<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id)
    {
        return $this->model->with('variations')->findOrFail($id);
    }

    public function getActiveProducts(array $filters = [], int $perPage = 15)
    {
        $query = $this->model
            ->with('brand')
            ->where('status', 'active')
            ->where(fn($q) => $q->whereNull('campaign_end')->orWhere('campaign_end', '>=', now()));

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['commission_type'])) {
            $query->where('commission_type', $filters['commission_type']);
        }

        if (!empty($filters['min_commission'])) {
            $query->where('commission_rate', '>=', $filters['min_commission']);
        }

        if (!empty($filters['max_commission'])) {
            $query->where('commission_rate', '<=', $filters['max_commission']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        $sortField = match ($filters['sort'] ?? 'commission_rate') {
            'created_at'     => 'created_at',
            'price'          => 'price',
            'name'           => 'name',
            default          => 'commission_rate',
        };
        $sortDir = ($filters['sort'] ?? '') === 'name' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortDir)->paginate($perPage);
    }

    public function getByBrand(int $brandId)
    {
        return $this->model
            ->where('brand_id', $brandId)
            ->latest()
            ->paginate(15);
    }

    public function getHighCommission(int $limit = 10)
    {
        return $this->model
            ->where('status', 'active')
            ->orderBy('commission_rate', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getCategories(): array
    {
        return $this->model
            ->where('status', 'active')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->toArray();
    }
}