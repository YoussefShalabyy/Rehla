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
        // Add new ENUM values
        DB::statement("ALTER TABLE listings MODIFY status ENUM('pending', 'published', 'rejected', 'archived', 'active', 'hidden', 'disabled') DEFAULT 'pending'");
        
        // Update existing records
        DB::table('listings')->where('status', 'published')->update(['status' => 'active']);
        DB::table('listings')->where('status', 'pending')->update(['status' => 'hidden']);
        DB::table('listings')->where('status', 'rejected')->update(['status' => 'archived']);
        
        // Remove old ENUM values and set new default
        DB::statement("ALTER TABLE listings MODIFY status ENUM('active', 'hidden', 'disabled', 'archived') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back
        DB::statement("ALTER TABLE listings MODIFY status ENUM('pending', 'published', 'rejected', 'archived', 'active', 'hidden', 'disabled') DEFAULT 'active'");
        
        DB::table('listings')->where('status', 'active')->update(['status' => 'published']);
        DB::table('listings')->where('status', 'hidden')->update(['status' => 'pending']);
        DB::table('listings')->where('status', 'disabled')->update(['status' => 'pending']);
        
        DB::statement("ALTER TABLE listings MODIFY status ENUM('pending', 'published', 'rejected', 'archived') DEFAULT 'pending'");
    }
};
