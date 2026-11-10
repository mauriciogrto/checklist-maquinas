<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sistema_propostas')) {
            return;
        }

        Schema::create('sistema_propostas', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_nome');
            $table->string('cliente_documento')->nullable();
            $table->string('contato_nome')->nullable();
            $table->string('contato_email')->nullable();
            $table->string('contato_telefone')->nullable();
            $table->unsignedInteger('quantidade_rastreadores')->default(0);
            $table->decimal('valor_rastreador', 12, 2)->default(0);
            $table->decimal('valor_sistema_mensal', 12, 2)->default(0);
            $table->decimal('valor_comodato_mensal', 12, 2)->default(0);
            $table->decimal('valor_treinamento', 12, 2)->default(0);
            $table->decimal('valor_instalacao', 12, 2)->default(0);
            $table->decimal('valor_deslocamento', 12, 2)->default(0);
            $table->string('condicoes_pagamento')->nullable();
            $table->unsignedTinyInteger('vencimento_mensal_dia')->default(10);
            $table->unsignedInteger('prazo_contrato_meses')->nullable();
            $table->unsignedInteger('validade_dias')->default(10);
            $table->text('observacoes')->nullable();
            $table->string('status', 20)->default('rascunho')->index();
            $table->timestamp('enviada_em')->nullable();
            $table->timestamp('respondida_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sistema_propostas');
    }
};
