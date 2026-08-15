<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }

    public function getCreators(array $filters, int $perPage)
    {
        $query = $this->model->where('role', 'creator');

        if (!empty($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }

        return $query->paginate($perPage);
    }

    public function searchCreators(string $query)
    {
        return $this->model
            ->where('role', 'creator')
            ->where('name', 'like', "%{$query}%")
            ->get();
    }

}