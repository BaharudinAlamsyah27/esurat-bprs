<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'incoming_letter.view_any', 'incoming_letter.view', 'incoming_letter.create', 'incoming_letter.update',
            'incoming_letter.forward', 'incoming_letter.archive', 'disposition.create', 'disposition.update_own',
            'disposition.close', 'outgoing_letter.view_any', 'outgoing_letter.view', 'outgoing_letter.create',
            'outgoing_letter.update_own', 'outgoing_letter.submit', 'outgoing_letter.cancel', 'outgoing_letter.send',
            'outgoing_letter.archive', 'approval.act', 'report.view', 'audit.view', 'user.manage', 'master.manage',
            'setting.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $all = Permission::query()->where('guard_name', 'web')->get();

        $rolePermissions = [
            'super_admin' => [
                'user.manage', 'master.manage', 'setting.manage', 'report.view', 'audit.view',
            ],
            'admin_persuratan' => [
                'incoming_letter.view_any', 'incoming_letter.view', 'incoming_letter.create',
                'incoming_letter.update', 'incoming_letter.forward', 'incoming_letter.archive',
                'outgoing_letter.view_any', 'outgoing_letter.view', 'outgoing_letter.create',
                'outgoing_letter.send', 'outgoing_letter.archive', 'report.view',
            ],
            'direksi' => [
                'incoming_letter.view_any', 'incoming_letter.view', 'disposition.create',
                'disposition.update_own', 'disposition.close', 'outgoing_letter.view_any',
                'outgoing_letter.view', 'outgoing_letter.create', 'outgoing_letter.update_own',
                'outgoing_letter.submit', 'outgoing_letter.cancel', 'approval.act', 'report.view',
            ],
            'kepala_unit' => [
                'incoming_letter.view_any', 'incoming_letter.view', 'disposition.create',
                'disposition.update_own', 'disposition.close', 'outgoing_letter.view_any',
                'outgoing_letter.view', 'outgoing_letter.create', 'outgoing_letter.update_own',
                'outgoing_letter.submit', 'approval.act', 'report.view',
            ],
            'staf' => [
                'incoming_letter.view', 'disposition.update_own', 'outgoing_letter.view',
                'outgoing_letter.create', 'outgoing_letter.update_own', 'outgoing_letter.submit',
            ],
            'auditor' => [
                'incoming_letter.view_any', 'incoming_letter.view', 'outgoing_letter.view_any',
                'outgoing_letter.view', 'report.view', 'audit.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($all->whereIn('name', $permissionNames));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
