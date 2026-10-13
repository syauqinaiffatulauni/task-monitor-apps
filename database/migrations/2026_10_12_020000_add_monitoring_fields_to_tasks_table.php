<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('owner')->constrained('users')->nullOnDelete();
            $table->text('progress_note')->nullable()->after('status');
            $table->timestamp('progress_updated_at')->nullable()->after('progress_note');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn(['progress_note', 'progress_updated_at']);
        });
    }
};
