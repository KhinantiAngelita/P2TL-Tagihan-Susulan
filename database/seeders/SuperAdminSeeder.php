<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Bikin/update satu akun super admin dari kredensial di .env.
     * Aman dijalankan berkali-kali (updateOrCreate) — gak bikin akun dobel.
     *
     * Tambahkan di .env:
     *   SUPER_ADMIN_NAME="Admin PLN"
     *   SUPER_ADMIN_EMAIL=admin@pln.co.id
     *   SUPER_ADMIN_PASSWORD=GantiPasswordIni123!
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command->warn('SUPER_ADMIN_EMAIL / SUPER_ADMIN_PASSWORD belum diset di .env — seeder dilewati.');
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password'          => Hash::make($password),
                'role'              => 'super_admin',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Akun super admin siap: {$email}");
    }
}