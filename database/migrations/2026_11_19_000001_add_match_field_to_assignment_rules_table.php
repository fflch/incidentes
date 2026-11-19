<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite que a regra case pelo nome do serviço ou pelo tipo do alerta.
     * A coluna service_name passa a se chamar pattern (o valor comparado).
     */
    public function up(): void
    {
        Schema::table('assignment_rules', function (Blueprint $table) {
            $table->dropUnique(['service_name']);
        });

        Schema::table('assignment_rules', function (Blueprint $table) {
            $table->renameColumn('service_name', 'pattern');
        });

        Schema::table('assignment_rules', function (Blueprint $table) {
            $table->string('match_field', 20)->default('service_name')->after('id');
            $table->unique(['match_field', 'pattern']);
        });
    }

    public function down(): void
    {
        Schema::table('assignment_rules', function (Blueprint $table) {
            $table->dropUnique(['match_field', 'pattern']);
        });

        Schema::table('assignment_rules', function (Blueprint $table) {
            $table->dropColumn('match_field');
        });

        Schema::table('assignment_rules', function (Blueprint $table) {
            $table->renameColumn('pattern', 'service_name');
        });

        Schema::table('assignment_rules', function (Blueprint $table) {
            $table->unique('service_name');
        });
    }
};
