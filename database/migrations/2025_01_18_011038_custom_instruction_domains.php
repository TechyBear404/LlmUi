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
        Schema::create('custom_instruction_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_instruction_id')->constrained()->onDelete('cascade');
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['custom_instruction_id', 'domain_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_instruction_domains');
    }
};
