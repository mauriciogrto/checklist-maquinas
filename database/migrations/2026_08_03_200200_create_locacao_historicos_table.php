<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('locacao_historicos')) {
            Schema::create('locacao_historicos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->foreignId('maquina_id')->constrained('maquinas')->cascadeOnDelete();
                $table->unsignedBigInteger('locacao_cliente_id')->nullable();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('status_anterior')->nullable();
                $table->string('status_novo')->nullable();
                $table->string('cliente_anterior')->nullable();
                $table->string('cliente_novo')->nullable();
                $table->string('local_anterior')->nullable();
                $table->string('local_novo')->nullable();
                $table->date('contrato_inicio')->nullable();
                $table->date('contrato_fim')->nullable();
                $table->date('devolucao_prevista')->nullable();
                $table->date('devolucao_realizada')->nullable();
                $table->text('observacao')->nullable();
                $table->timestamps();

                $table->index(['empresa_id', 'maquina_id']);
                $table->index('locacao_cliente_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('locacao_historicos');
    }
};
