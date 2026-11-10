<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('locacao_clientes')) {
            Schema::create('locacao_clientes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->string('nome');
                $table->string('documento')->nullable();
                $table->string('email')->nullable();
                $table->string('telefone')->nullable();
                $table->text('endereco')->nullable();
                $table->text('observacoes')->nullable();
                $table->string('status')->default('ativo');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('locacao_clientes');
    }
};
