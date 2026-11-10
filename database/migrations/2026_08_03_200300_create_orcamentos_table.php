<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orcamentos')) {
            Schema::create('orcamentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->unsignedBigInteger('locacao_cliente_id')->nullable();
                $table->unsignedBigInteger('maquina_id')->nullable();
                $table->string('numero')->nullable();
                $table->string('titulo');
                $table->text('descricao')->nullable();
                $table->string('contato_nome')->nullable();
                $table->string('contato_email')->nullable();
                $table->string('contato_telefone')->nullable();
                $table->date('data_orcamento');
                $table->date('validade')->nullable();
                $table->date('contrato_inicio')->nullable();
                $table->date('contrato_fim')->nullable();
                $table->string('tempo_contrato')->nullable();
                $table->string('frete_tipo')->nullable();
                $table->decimal('valor_locacao', 12, 2)->default(0);
                $table->decimal('valor_frete', 12, 2)->default(0);
                $table->decimal('desconto', 12, 2)->default(0);
                $table->decimal('desconto_percentual', 8, 2)->default(0);
                $table->decimal('imposto_percentual', 8, 2)->default(0);
                $table->text('condicoes_pagamento')->nullable();
                $table->text('observacoes')->nullable();
                $table->string('status')->default('rascunho');
                $table->timestamps();

                $table->index(['empresa_id', 'status']);
                $table->index('locacao_cliente_id');
                $table->index('maquina_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};
