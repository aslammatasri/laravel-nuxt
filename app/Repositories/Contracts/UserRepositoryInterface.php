<?php

namespace App\Repositories\Contracts;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email);
    public function getCreators(array $filters, int $perPage);
    public function searchCreators(string $query);
}