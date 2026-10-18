<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    protected $signature = 'denapax:admin {email : Email address for the initial administrator} {--name=Admin : Staff display name}';

    protected $description = 'Provision a staff administrator after RBAC migrations and seeding';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $name = trim((string) $this->option('name'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '') {
            $this->error('A valid email and a non-empty name are required.');

            return self::FAILURE;
        }

        if (! Role::query()->where('name', 'super-admin')->where('guard_name', 'web')->exists()) {
            $this->error('Seed RBAC first: php artisan db:seed --class=RolesAndPermissionsSeeder');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('An account with this email already exists. No changes were made.');

            return self::FAILURE;
        }

        $password = $this->secret('Choose a password (minimum 12 characters)');

        if (! is_string($password) || mb_strlen($password) < 12) {
            $this->error('Password must contain at least 12 characters.');

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('super-admin');

        $this->info('Administrator account created. Keep its credentials private.');

        return self::SUCCESS;
    }
}
