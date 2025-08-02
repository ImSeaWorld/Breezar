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
        Schema::table('clients', function (Blueprint $table) {
            $table->text('fly_api_token')->nullable()->after('fly_org_id');
            $table->string('fly_token_type')->default('org')->after('fly_api_token'); // org, app, readonly
            $table->timestamp('fly_token_expires_at')->nullable()->after('fly_token_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['fly_api_token', 'fly_token_type', 'fly_token_expires_at']);
        });
    }
};
