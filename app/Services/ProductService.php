<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function getMarketplaceProducts(array $filters = [])
    {
        $allowedCategories = $this->productRepository->getCategories();

        if (!empty($filters['category']) && !in_array($filters['category'], $allowedCategories)) {
            throw new \InvalidArgumentException('Invalid category');
        }

        if (!empty($filters['commission_type']) && !in_array($filters['commission_type'], ['percentage', 'fixed'])) {
            throw new \InvalidArgumentException('Invalid commission type');
        }

        $allowedSorts = ['commission_rate', 'created_at', 'price', 'name'];
        if (!empty($filters['sort']) && !in_array($filters['sort'], $allowedSorts)) {
            throw new \InvalidArgumentException('Invalid sort field');
        }

        return $this->productRepository->getActiveProducts($filters);
    }

    public function getProductDetail(int $productId)
    {
        return $this->productRepository->findById($productId);
    }

    public function getBrandProducts(int $brandId)
    {
        return $this->productRepository->getByBrand($brandId);
    }

    public function getCategories(): array
    {
        return $this->productRepository->getCategories();
    }

    public function getBrandProduct(int $productId, int $brandId)
    {
        $product = $this->productRepository->findById($productId);

        if ($product->brand_id !== $brandId) {
            throw new \Exception('Unauthorized', 403);
        }

        return $product;
    }

    public function createProduct(array $data, int $brandId, array $images = [])
    {
        $data['brand_id'] = $brandId;
        $data['status']   = 'active';

        $uploaded = [];
        foreach ($images as $img) {
            $uploaded[] = $img->store('products', 'public');
        }
        if (!empty($uploaded)) {
            $data['images'] = $uploaded;
            $data['image']  = $uploaded[0];
        }

        return $this->productRepository->create($data);
    }

     public function updateProduct(int $productId, array $data, int $brandId, array $images = [], array $deleteImages = [])
    {
        $product = $this->productRepository->findById($productId);

        if ($product->brand_id !== $brandId) {
            throw new \Exception('Unauthorized', 403);
        }

        $existing = $product->images;

        foreach ($deleteImages as $path) {
            Storage::disk('public')->delete($path);
            $existing = array_values(array_filter($existing, fn($p) => $p !== $path));
        }

        foreach ($images as $img) {
            $existing[] = $img->store('products', 'public');
        }

        $data['images'] = $existing;
        $data['image']  = !empty($existing) ? $existing[0] : null;

        unset($data['delete_images']);

        $this->productRepository->update($productId, $data);

        return $this->productRepository->findById($productId);
    }

    public function deleteProduct(int $productId, int $brandId)
    {
        $product = $this->productRepository->findById($productId);

        // Business rule — brand can only delete their own products
        if ($product->brand_id !== $brandId) {
            throw new \Exception('Unauthorized', 403);
        }

        return $this->productRepository->delete($productId);
    }
}