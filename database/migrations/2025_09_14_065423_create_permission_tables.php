<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return never
     */
    public function up()
    {
        // Determine if teams feature is enabled in permission config
        $teams = (bool) (config('permission.teams') ?? false);
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        // Ensure we have arrays before accessing offsets
        if (! is_array($columnNames)) {
            $columnNames = [];
        }
        if (! is_array($tableNames)) {
            $tableNames = [];
        }

        // Resolve pivot column names for role/permission relations
        $pivotRole = is_string($columnNames['role_pivot_key'] ?? null) ? $columnNames['role_pivot_key'] : 'role_id';
        $pivotPermission = is_string($columnNames['permission_pivot_key'] ?? null) ? $columnNames['permission_pivot_key'] : 'permission_id';

        throw_if(empty($tableNames), new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.'));
        $teamForeignKey = is_string($columnNames['team_foreign_key'] ?? null) ? $columnNames['team_foreign_key'] : null;
        throw_if($teams && empty($teamForeignKey), new \Exception('Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.'));

        $permissionsTable = is_string($tableNames['permissions'] ?? null) ? $tableNames['permissions'] : 'permissions';
        Schema::create($permissionsTable, static function (Blueprint $table): void {
            // $table->engine('InnoDB');
            $table->bigIncrements('id'); // permission id
            $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
            $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        $rolesTable = is_string($tableNames['roles'] ?? null) ? $tableNames['roles'] : 'roles';
        Schema::create($rolesTable, static function (Blueprint $table) use ($teams, $columnNames): void {
            // $table->engine('InnoDB');
            $table->bigIncrements('id'); // role id
            $testing = config('permission.testing');
            if ($teams || $testing) { // permission.testing is a fix for sqlite testing
                $teamForeignKey = is_string($columnNames['team_foreign_key'] ?? null) ? $columnNames['team_foreign_key'] : 'team_id';
                $table->unsignedBigInteger($teamForeignKey)->nullable();
                $table->index($teamForeignKey, 'roles_team_foreign_key_index');
            }
            $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
            $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
            $table->timestamps();
            if ($teams || $testing) {
                $teamForeignKey = is_string($columnNames['team_foreign_key'] ?? null) ? $columnNames['team_foreign_key'] : 'team_id';
                $table->unique([$teamForeignKey, 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        $modelHasPermissionsTable = is_string($tableNames['model_has_permissions'] ?? null) ? $tableNames['model_has_permissions'] : 'model_has_permissions';
        Schema::create($modelHasPermissionsTable, static function (Blueprint $table) use ($permissionsTable, $columnNames, $pivotPermission, $teams): void {
            $table->unsignedBigInteger($pivotPermission);

            $table->string('model_type');
            $modelMorphKey = is_string($columnNames['model_morph_key'] ?? null) ? $columnNames['model_morph_key'] : 'model_id';
            $table->unsignedBigInteger($modelMorphKey);
            $table->index([$modelMorphKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on($permissionsTable)
                ->onDelete('cascade');
            if ($teams) {
                $teamForeignKey = is_string($columnNames['team_foreign_key'] ?? null) ? $columnNames['team_foreign_key'] : 'team_id';
                $table->unsignedBigInteger($teamForeignKey);
                $table->index($teamForeignKey, 'model_has_permissions_team_foreign_key_index');

                $table->primary(
                    [$teamForeignKey, $pivotPermission, $modelMorphKey, 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );
            } else {
                $table->primary(
                    [$pivotPermission, $modelMorphKey, 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );
            }
        });

        $modelHasRolesTable = is_string($tableNames['model_has_roles'] ?? null) ? $tableNames['model_has_roles'] : 'model_has_roles';
        Schema::create($modelHasRolesTable, static function (Blueprint $table) use ($rolesTable, $columnNames, $pivotRole, $teams): void {
            $pivotRoleColumn = $pivotRole;
            $table->unsignedBigInteger($pivotRoleColumn);

            $table->string('model_type');
            $modelMorphKey = is_string($columnNames['model_morph_key'] ?? null) ? $columnNames['model_morph_key'] : 'model_id';
            $table->unsignedBigInteger($modelMorphKey);
            $table->index([$modelMorphKey, 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRoleColumn)
                ->references('id') // role id
                ->on($rolesTable)
                ->onDelete('cascade');
            if ($teams) {
                $teamForeignKey = is_string($columnNames['team_foreign_key'] ?? null) ? $columnNames['team_foreign_key'] : 'team_id';
                $table->unsignedBigInteger($teamForeignKey);
                $table->index($teamForeignKey, 'model_has_roles_team_foreign_key_index');

                $table->primary(
                    [$teamForeignKey, $pivotRoleColumn, $modelMorphKey, 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            } else {
                $table->primary(
                    [$pivotRoleColumn, $modelMorphKey, 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            }
        });

        $roleHasPermissionsTable = is_string($tableNames['role_has_permissions'] ?? null) ? $tableNames['role_has_permissions'] : 'role_has_permissions';
        Schema::create($roleHasPermissionsTable, static function (Blueprint $table) use ($permissionsTable, $rolesTable, $pivotRole, $pivotPermission): void {
            $pivotPermissionStr = $pivotPermission;
            $pivotRoleStr = $pivotRole;

            $table->unsignedBigInteger($pivotPermissionStr);
            $table->unsignedBigInteger($pivotRoleStr);

            $table->foreign($pivotPermissionStr)
                ->references('id') // permission id
                ->on($permissionsTable)
                ->onDelete('cascade');

            $table->foreign($pivotRoleStr)
                ->references('id') // role id
                ->on($rolesTable)
                ->onDelete('cascade');

            $table->primary([$pivotPermissionStr, $pivotRoleStr], 'role_has_permissions_permission_id_role_id_primary');
        });

        $cacheStore = config('permission.cache.store');
        $cacheKey = config('permission.cache.key');

        $storeParam = is_string($cacheStore) && $cacheStore !== 'default' ? $cacheStore : null;
        $keyParam = is_string($cacheKey) ? $cacheKey : 'spatie.permission.cache';

        app('cache')
            ->store($storeParam)
            ->forget($keyParam);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $tableNames = is_array($tableNames) ? $tableNames : [];

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        }

        $roleHasPermissionsTable = is_string($tableNames['role_has_permissions'] ?? null) ? $tableNames['role_has_permissions'] : 'role_has_permissions';
        $modelHasRolesTable = is_string($tableNames['model_has_roles'] ?? null) ? $tableNames['model_has_roles'] : 'model_has_roles';
        $modelHasPermissionsTable = is_string($tableNames['model_has_permissions'] ?? null) ? $tableNames['model_has_permissions'] : 'model_has_permissions';
        $rolesTable = is_string($tableNames['roles'] ?? null) ? $tableNames['roles'] : 'roles';
        $permissionsTable = is_string($tableNames['permissions'] ?? null) ? $tableNames['permissions'] : 'permissions';

        Schema::drop($roleHasPermissionsTable);
        Schema::drop($modelHasRolesTable);
        Schema::drop($modelHasPermissionsTable);
        Schema::drop($rolesTable);
        Schema::drop($permissionsTable);
    }
};
