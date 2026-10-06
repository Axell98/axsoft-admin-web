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
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name');
            $table->string('slogan')->nullable()->after('ruc');
            $table->string('email_2')->nullable()->after('email');
            $table->string('phone', 30)->nullable()->after('email_2');
            $table->string('whatsapp_2', 30)->nullable()->after('whatsapp');
            $table->string('maps_url', 500)->nullable()->after('address');
            $table->string('business_hours')->nullable()->after('maps_url');
            $table->string('website')->nullable()->after('business_hours');
            $table->string('linkedin')->nullable()->after('tiktok');
            $table->string('behance')->nullable()->after('linkedin');
            $table->string('meta_title', 70)->nullable()->after('description');
            $table->string('meta_description', 160)->nullable()->after('meta_title');
            $table->string('favicon_path')->nullable()->after('logo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name', 'slogan', 'email_2', 'phone', 'whatsapp_2', 'maps_url',
                'business_hours', 'website', 'linkedin', 'behance',
                'meta_title', 'meta_description', 'favicon_path',
            ]);
        });
    }
};
