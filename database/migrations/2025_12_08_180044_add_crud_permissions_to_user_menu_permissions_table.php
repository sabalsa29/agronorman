<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_menu_permissions', function (Blueprint $table) {
            $table->boolean('can_create')->default(true)->after('permitted');
            $table->boolean('can_edit')->default(true)->after('can_create');
            $table->boolean('can_delete')->default(true)->after('can_edit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_menu_permissions', function (Blueprint $table) {
            $table->dropColumn(['can_create', 'can_edit', 'can_delete']);
        });
    }
};
