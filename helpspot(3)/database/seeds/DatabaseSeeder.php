<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        $this->call(SettingsSeeder::class);
        $this->call(SmsSeeder::class);
        $this->call(StatusSeeder::class);
        $this->call(AvatarSeeder::class);
        $this->call(PermissionSeeder::class);
    }
}
