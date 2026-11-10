<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orcamento_itens')) {
            return;
        }

        Schema::table('orcamento_itens', function (Blueprint $table) {
            if (! Schema::hasColumn('orcamento_itens', 'contrato_inicio')) {
                $table->date('contrato_inicio')->nullable()->after('descricao');
            }

            if (! Schema::hasColumn('orcamento_itens', 'contrato_fim')) {
                $table->date('contrato_fim')->nullable()->after('contrato_inicio');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orcamento_itens')) {
            return;
        }

        Schema::table('orcamento_itens', function (Blueprint $table) {
            if (Schema::hasColumn('orcamento_itens', 'contrato_fim')) {
                $table->dropColumn('contrato_fim');
            }

            if (Schema::hasColumn('orcamento_itens', 'contrato_inicio')) {
                $table->dropColumn('contrato_inicio');
            }
        });
    }
};
