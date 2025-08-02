<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, json, encrypted
            $table->string('group')->default('general'); // general, fly_api, email, etc
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default Fly.io settings
        DB::table('settings')->insert([
            [
                'key' => 'fly_api_token',
                'value' => env('FLY_API_TOKEN'),
                'type' => 'encrypted',
                'group' => 'fly_api',
                'description' => 'Fly.io Personal Access Token',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fly_org_id',
                'value' => env('FLY_ORG_ID'),
                'type' => 'string',
                'group' => 'fly_api',
                'description' => 'Default Fly.io Organization ID',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'fly_api_endpoint',
                'value' => 'https://api.fly.io/graphql',
                'type' => 'string',
                'group' => 'fly_api',
                'description' => 'Fly.io GraphQL API Endpoint',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
