<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $perms = ['list users','edit users','delete users','list roles','edit roles'];
        foreach ($perms as $p) {
        Permission::firstOrCreate(['name' => $p]);
        }
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($perms);
        Role::firstOrCreate(['name' => 'user'])->givePermissionTo('list users');
    }
}
