<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', ['three_days_before', 'one_day_before', 'overdue']);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['task_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reminders');
    }
};
