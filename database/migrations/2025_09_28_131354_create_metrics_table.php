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
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('category')->nullable()->index();
            $table->nullableMorphs('measurable');
            $table->unsignedInteger('year');
            $table->unsignedInteger('month');
            $table->unsignedInteger('day');
            $table->unsignedInteger('value');
            $table->timestamps();

            $table->index(['year', 'month', 'day']);

            $table->unique([
                'name',
                'category',
                'year',
                'month',
                'day',
                'measurable_type',
                'measurable_id',
            ], 'metrics_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
