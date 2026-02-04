<?php

declare(strict_types=1);

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
        Schema::create('resource_qualifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resource_id')->constrained();
            $table->foreignId('qualification_id')->constrained();
            $table->string('level')->nullable();
            $table->timestamps();

            $table->unique(['resource_id', 'qualification_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_qualifications');
    }
};
