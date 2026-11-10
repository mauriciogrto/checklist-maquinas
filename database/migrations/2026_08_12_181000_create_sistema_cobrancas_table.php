<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sistema_cobrancas')) {
            return;
        }

        Schema::create('sistema_cobrancas', function (Blueprint $table) {
            $table->id();
            $table->string('cliente_nome');
            $table->string('descricao')->nullable();
            $table->date('referencia_mes')->nullable()->index();
            $table->date('vencimento')->index();
            $table->decimal('valor', 12, 2)->default(0);
            $table->string('status', 20)->default('aberta')->index();
            $table->string('forma_pagamento')->nullable();
            $table->text('observacoes')->nullable();
            $table->date('pago_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sistema_cobrancas');
    }
};
