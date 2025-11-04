<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all admin users
        $admins = User::where('role', User::ROLE_ADMIN)->get();

        // Create a welcome notification for each admin
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'Welcome to the Dashboard',
                'message' => 'Welcome back! You have successfully logged in to the Transportation Management System. Check your dashboard for the latest updates.',
                'type' => 'success',
                'is_read' => false,
            ]);
        }
    }
}
