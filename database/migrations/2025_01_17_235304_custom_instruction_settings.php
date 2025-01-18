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
        Schema::create('custom_instruction_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_instruction_id')->constrained()->onDelete('cascade');
            $table->foreignId('setting_type_id')->constrained();
            $table->foreignId('setting_option_id')->constrained();
            $table->foreignId('domain_id')->nullable()->constrained();
            $table->text('custom_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_instruction_settings');
    }
};
