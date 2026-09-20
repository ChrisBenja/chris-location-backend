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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();

            $table->string('brand', 100);
            $table->string('model', 100);
            $table->unsignedSmallInteger('year');

            $table->string('type', 50);
            $table->unsignedTinyInteger('seats');

            $table->decimal('price_per_day', 10, 2);

            $table->text('description')->nullable();

            $table->string('image')->nullable();

            $table->string('status', 30)->default('available');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
