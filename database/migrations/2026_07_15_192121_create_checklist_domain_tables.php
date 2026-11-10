<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('senha');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('operadores', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('usuario')->unique();
            $table->string('senha');
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('maquinas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('setor')->nullable();
            $table->string('modelo')->nullable();
            $table->string('codigo_interno')->nullable();
            $table->string('token')->unique();
            $table->foreignId('formulario_checklist_id')->nullable();
            $table->foreignId('formulario_manutencao_id')->nullable();
            $table->enum('status', ['ativa', 'inativa'])->default('ativa');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('formularios', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('tipo', ['modelo_checklist', 'checklist_maquina', 'manutencao']);
            $table->string('periodicidade')->nullable();
            $table->foreignId('maquina_id')->nullable()->constrained('maquinas')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('maquinas', function (Blueprint $table) {
            $table->foreign('formulario_checklist_id')->references('id')->on('formularios')->nullOnDelete();
            $table->foreign('formulario_manutencao_id')->references('id')->on('formularios')->nullOnDelete();
        });

        Schema::create('formulario_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formulario_id')->constrained('formularios')->cascadeOnDelete();
            $table->text('pergunta');
            $table->enum('tipo_resposta', ['conformidade', 'texto', 'numero', 'imagem']);
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('dispara_alerta')->default(false);
            $table->boolean('obrigatorio')->default(true);
            $table->timestamps();
        });

        Schema::create('registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maquina_id')->constrained('maquinas')->restrictOnDelete();
            $table->foreignId('formulario_id')->constrained('formularios')->restrictOnDelete();
            $table->foreignId('operador_id')->nullable()->constrained('operadores')->restrictOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->enum('tipo', ['checklist', 'manutencao']);
            $table->string('periodicidade')->nullable();
            $table->timestamp('data_hora');
            $table->enum('status_geral', ['conforme', 'com_pendencia'])->default('conforme');
            $table->timestamps();
        });

        Schema::create('registro_respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_id')->constrained('registros')->cascadeOnDelete();
            $table->foreignId('formulario_item_id')->constrained('formulario_itens')->restrictOnDelete();
            $table->text('resposta')->nullable();
            $table->timestamps();
        });

        Schema::create('registro_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_id')->constrained('registros')->cascadeOnDelete();
            $table->foreignId('formulario_item_id')->nullable()->constrained('formulario_itens')->nullOnDelete();
            $table->string('caminho_arquivo');
            $table->timestamps();
        });

        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_id')->constrained('registros')->cascadeOnDelete();
            $table->foreignId('maquina_id')->constrained('maquinas')->restrictOnDelete();
            $table->foreignId('formulario_item_id')->constrained('formulario_itens')->restrictOnDelete();
            $table->foreignId('operador_id')->constrained('operadores')->restrictOnDelete();
            $table->enum('status', ['pendente', 'em_analise', 'resolvido', 'falso_positivo'])->default('pendente');
            $table->text('observacao')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('alerta_status_historicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alerta_id')->constrained('alertas')->cascadeOnDelete();
            $table->string('status_anterior')->nullable();
            $table->string('status_novo');
            $table->foreignId('alterado_por')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('alterado_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerta_status_historicos');
        Schema::dropIfExists('alertas');
        Schema::dropIfExists('registro_anexos');
        Schema::dropIfExists('registro_respostas');
        Schema::dropIfExists('registros');
        Schema::dropIfExists('formulario_itens');
        Schema::table('maquinas', function (Blueprint $table) {
            $table->dropForeign(['formulario_checklist_id']);
            $table->dropForeign(['formulario_manutencao_id']);
        });
        Schema::dropIfExists('formularios');
        Schema::dropIfExists('maquinas');
        Schema::dropIfExists('operadores');
        Schema::dropIfExists('admins');
    }
};
