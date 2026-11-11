<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {--name=Admin}';

    protected $description = 'Create an admin user (admins cannot be created through the public API)';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (User::where('email', $email)->exists()) {
            $this->error("A user with {$email} already exists.");

            return self::FAILURE;
        }

        $password = $this->secret('Password (min 12 characters)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => 'email', 'password' => 'required|string|min:12'],
        );

        if ($validator->fails() || $password !== $this->secret('Confirm password')) {
            $this->error($validator->fails() ? $validator->errors()->first() : 'Passwords do not match.');

            return self::FAILURE;
        }

        User::create([
            'name'     => $this->option('name'),
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => UserRole::ADMIN,
        ]);

        $this->info("Admin {$email} created.");

        return self::SUCCESS;
    }
}
