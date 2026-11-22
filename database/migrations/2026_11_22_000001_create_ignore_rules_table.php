<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ignore_rules', function (Blueprint $table) {
            $table->id();
            $table->string('pattern');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('pattern');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->foreignId('ignore_rule_id')->nullable()->after('parent_id')
                ->constrained('ignore_rules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ignore_rule_id');
        });

        Schema::dropIfExists('ignore_rules');
    }
};
