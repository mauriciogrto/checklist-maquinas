<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orcamentos')) {
            return;
        }

        Schema::table('orcamentos', function (Blueprint $table) {
            if (! Schema::hasColumn('orcamentos', 'prazo_faturamento')) {
                $table->string('prazo_faturamento')->nullable()->after('condicoes_pagamento');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orcamentos') || ! Schema::hasColumn('orcamentos', 'prazo_faturamento')) {
            return;
        }

        Schema::table('orcamentos', function (Blueprint $table) {
            $table->dropColumn('prazo_faturamento');
        });
    }
};
