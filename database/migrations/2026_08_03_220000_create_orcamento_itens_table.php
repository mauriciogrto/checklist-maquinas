<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orcamento_itens')) {
            Schema::create('orcamento_itens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('orcamento_id')->constrained('orcamentos')->cascadeOnDelete();
                $table->unsignedBigInteger('maquina_id')->nullable();
                $table->string('descricao')->nullable();
                $table->decimal('quantidade', 10, 2)->default(1);
                $table->decimal('valor_unitario', 12, 2)->default(0);
                $table->timestamps();

                $table->index('maquina_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamento_itens');
    }
};
