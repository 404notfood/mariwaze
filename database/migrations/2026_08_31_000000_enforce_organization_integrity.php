<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->foreignId('personal_owner_id')->nullable()->after('owner_id')->constrained('users')->nullOnDelete();
            $table->unique('personal_owner_id');
        });

        DB::table('organizations')
            ->where('type', 'personal')
            ->whereNotNull('owner_id')
            ->update(['personal_owner_id' => DB::raw('owner_id')]);

        DB::table('organizations')
            ->whereNotNull('owner_id')
            ->orderBy('id')
            ->eachById(function (object $organization): void {
                DB::table('organization_user')->updateOrInsert(
                    ['organization_id' => $organization->id, 'user_id' => $organization->owner_id],
                    ['role' => 'owner', 'created_at' => now(), 'updated_at' => now()],
                );
            });

        $legacyOrganizationId = null;

        foreach (['boats', 'cruises'] as $table) {
            if (DB::table($table)->whereNull('organization_id')->whereNull('user_id')->exists()) {
                $legacyOrganizationId ??= $this->legacyOrganizationId();
                DB::table($table)
                    ->whereNull('organization_id')
                    ->whereNull('user_id')
                    ->update(['organization_id' => $legacyOrganizationId]);
            }

            DB::table($table)
                ->whereNull('organization_id')
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->eachById(function (object $row) use ($table): void {
                    $organizationId = $this->personalOrganizationId((int) $row->user_id);
                    DB::table($table)->where('id', $row->id)->update(['organization_id' => $organizationId]);
                });
        }

        Schema::table('boats', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable(false)->change();
            $table->unique(['id', 'organization_id'], 'boats_id_organization_unique');
        });

        Schema::table('cruises', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable(false)->change();
            $table->foreign(['boat_id', 'organization_id'], 'cruises_boat_organization_foreign')
                ->references(['id', 'organization_id'])
                ->on('boats');
        });
    }

    public function down(): void
    {
        Schema::table('cruises', function (Blueprint $table): void {
            $table->dropForeign('cruises_boat_organization_foreign');
            $table->foreignId('organization_id')->nullable()->change();
        });

        Schema::table('boats', function (Blueprint $table): void {
            $table->dropUnique('boats_id_organization_unique');
            $table->foreignId('organization_id')->nullable()->change();
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('personal_owner_id');
        });
    }

    private function personalOrganizationId(int $userId): int
    {
        $organizationId = DB::table('organizations')
            ->where('personal_owner_id', $userId)
            ->value('id');

        if ($organizationId === null) {
            $user = DB::table('users')->where('id', $userId)->first(['name']);
            $organizationId = DB::table('organizations')->insertGetId([
                'name' => $user->name,
                'type' => 'personal',
                'owner_id' => $userId,
                'personal_owner_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('organization_user')->updateOrInsert(
            ['organization_id' => $organizationId, 'user_id' => $userId],
            ['role' => 'owner', 'created_at' => now(), 'updated_at' => now()],
        );

        return (int) $organizationId;
    }

    private function legacyOrganizationId(): int
    {
        $organizationId = DB::table('organizations')
            ->where('type', 'legacy')
            ->value('id');

        return $organizationId === null
            ? DB::table('organizations')->insertGetId([
                'name' => 'Données historiques',
                'type' => 'legacy',
                'created_at' => now(),
                'updated_at' => now(),
            ])
            : (int) $organizationId;
    }
};
