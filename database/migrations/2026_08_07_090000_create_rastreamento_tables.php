<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rastreador_dispositivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('maquina_id')->nullable()->constrained('maquinas')->nullOnDelete();
            $table->string('imei')->nullable()->unique();
            $table->string('modelo')->default('Queclink GV50CG');
            $table->string('provedor')->default('flespi');
            $table->string('flespi_device_id')->nullable();
            $table->string('operadora')->nullable();
            $table->string('chip_numero')->nullable();
            $table->string('status')->default('aguardando');
            $table->timestamp('ultimo_sinal_em')->nullable();
            $table->decimal('ultima_latitude', 10, 7)->nullable();
            $table->decimal('ultima_longitude', 10, 7)->nullable();
            $table->decimal('ultima_velocidade', 8, 2)->nullable();
            $table->boolean('ignicao')->nullable();
            $table->decimal('tensao_externa', 8, 2)->nullable();
            $table->decimal('bateria', 8, 2)->nullable();
            $table->integer('sinal')->nullable();
            $table->text('observacao')->nullable();
            $table->json('payload_resumo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['empresa_id', 'status']);
            $table->index(['maquina_id', 'ultimo_sinal_em']);
        });

        Schema::create('rastreador_posicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rastreador_dispositivo_id')->constrained('rastreador_dispositivos')->cascadeOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('maquina_id')->nullable()->constrained('maquinas')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('velocidade', 8, 2)->nullable();
            $table->boolean('ignicao')->nullable();
            $table->decimal('tensao_externa', 8, 2)->nullable();
            $table->decimal('bateria', 8, 2)->nullable();
            $table->integer('sinal')->nullable();
            $table->timestamp('gps_em')->nullable();
            $table->timestamp('recebido_em')->useCurrent();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['maquina_id', 'gps_em']);
            $table->index(['rastreador_dispositivo_id', 'gps_em']);
        });

        Schema::create('rastreador_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rastreador_dispositivo_id')->nullable()->constrained('rastreador_dispositivos')->nullOnDelete();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->foreignId('maquina_id')->nullable()->constrained('maquinas')->nullOnDelete();
            $table->string('tipo')->default('telemetria');
            $table->string('descricao')->nullable();
            $table->timestamp('evento_em')->nullable();
            $table->timestamp('recebido_em')->useCurrent();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['maquina_id', 'evento_em']);
            $table->index(['tipo', 'recebido_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rastreador_eventos');
        Schema::dropIfExists('rastreador_posicoes');
        Schema::dropIfExists('rastreador_dispositivos');
    }
};
