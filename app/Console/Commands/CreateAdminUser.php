<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create an admin (verified) user for production';

    public function handle(): int
    {
        $name = text(
            label: 'Name',
            required: true,
        );

        $email = text(
            label: 'Email',
            required: true,
            validate: function (string $value) {
                if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return 'Please enter a valid email address.';
                }

                if (User::where('email', $value)->exists()) {
                    return 'A user with that email already exists.';
                }

                return null;
            },
        );

        $password = password(
            label: 'Password',
            required: true,
            validate: fn (string $value) => strlen($value) < 8
                ? 'Password must be at least 8 characters.'
                : null,
        );

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $this->info("Admin user [{$email}] created successfully.");

        return self::SUCCESS;
    }
}
