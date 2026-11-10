<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maquinas', function (Blueprint $table) {
            if (! Schema::hasColumn('maquinas', 'locacao_status')) {
                $table->string('locacao_status')->default('disponivel')->after('art_seguranca_vencimento');
            }

            if (! Schema::hasColumn('maquinas', 'locacao_cliente')) {
                $table->string('locacao_cliente')->nullable()->after('locacao_status');
            }

            if (! Schema::hasColumn('maquinas', 'locacao_local')) {
                $table->string('locacao_local')->nullable()->after('locacao_cliente');
            }

            if (! Schema::hasColumn('maquinas', 'contrato_inicio')) {
                $table->date('contrato_inicio')->nullable()->after('locacao_local');
            }

            if (! Schema::hasColumn('maquinas', 'contrato_fim')) {
                $table->date('contrato_fim')->nullable()->after('contrato_inicio');
            }

            if (! Schema::hasColumn('maquinas', 'devolucao_prevista')) {
                $table->date('devolucao_prevista')->nullable()->after('contrato_fim');
            }

            if (! Schema::hasColumn('maquinas', 'devolucao_realizada')) {
                $table->date('devolucao_realizada')->nullable()->after('devolucao_prevista');
            }

            if (! Schema::hasColumn('maquinas', 'frete_responsavel')) {
                $table->string('frete_responsavel')->nullable()->after('devolucao_realizada');
            }

            if (! Schema::hasColumn('maquinas', 'frete_tipo')) {
                $table->string('frete_tipo')->nullable()->after('frete_responsavel');
            }

            if (! Schema::hasColumn('maquinas', 'frete_observacao')) {
                $table->text('frete_observacao')->nullable()->after('frete_tipo');
            }
        });

        Schema::table('maquina_arquivos', function (Blueprint $table) {
            if (! Schema::hasColumn('maquina_arquivos', 'categoria')) {
                $table->string('categoria')->default('arquivo')->after('maquina_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('maquinas', function (Blueprint $table) {
            foreach ([
                'frete_observacao',
                'frete_tipo',
                'frete_responsavel',
                'devolucao_realizada',
                'devolucao_prevista',
                'contrato_fim',
                'contrato_inicio',
                'locacao_local',
                'locacao_cliente',
                'locacao_status',
            ] as $column) {
                if (Schema::hasColumn('maquinas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('maquina_arquivos', function (Blueprint $table) {
            if (Schema::hasColumn('maquina_arquivos', 'categoria')) {
                $table->dropColumn('categoria');
            }
        });
    }
};
