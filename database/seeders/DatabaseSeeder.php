<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data awal aplikasi. Urutan mengikuti dependensi foreign key.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ReportStatusSeeder::class,
            UrgencyLevelSeeder::class,
            PrioritySeeder::class,
            BuildingSeeder::class,
            RoomSeeder::class,
            InfrastructureCategorySeeder::class,
            DeviceTypeSeeder::class,
            DeviceSeeder::class,
            UserSeeder::class,
        ]);
    }
}
