<?php

namespace App\Services;

use App\Models\Formulario;
use App\Models\Maquina;
use App\Support\ChecklistPeriodicidade;

class FormularioService
{
    public const TIPOS_MANUTENCAO = [
        'preventiva' => 'Preventiva',
        'preditiva' => 'Preditiva',
        'corretiva' => 'Corretiva',
    ];

    public function copiarModeloParaMaquina(Formulario $modelo, Maquina $maquina, ?string $periodicidade = null): Formulario
    {
        $formulario = Formulario::create([
            'nome' => $modelo->nome.' - '.$maquina->nome,
            'empresa_id' => $maquina->empresa_id,
            'tipo' => 'checklist_maquina',
            'periodicidade' => $periodicidade,
            'maquina_id' => $maquina->id,
            'origem_modelo_id' => $modelo->id,
        ]);

        foreach ($modelo->itens as $item) {
            $formulario->itens()->create($item->only([
                'pergunta',
                'tipo_resposta',
                'ordem',
                'dispara_alerta',
                'obrigatorio',
            ]));
        }

        return $formulario;
    }

    public function garantirModelosPadrao(?int $empresaId = null): void
    {
        foreach (array_keys(self::TIPOS_MANUTENCAO) as $tipoManutencao) {
            $this->modeloManutencaoPadrao($empresaId, $tipoManutencao);
        }

        foreach (ChecklistPeriodicidade::PADROES as $periodicidade) {
            $this->modeloChecklistPadrao($empresaId, $periodicidade);
        }
    }

    public function modeloChecklistPadrao(?int $empresaId, string $periodicidade): Formulario
    {
        $formulario = Formulario::query()
            ->with('itens')
            ->where('tipo', 'modelo_checklist')
            ->where('periodicidade', $periodicidade)
            ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId))
            ->first();

        if ($formulario) {
            return $formulario->load('itens');
        }

        $nomes = [
            'diario' => 'Checklist padrão diário',
            'mensal' => 'Checklist padrão mensal',
            'trimestral' => 'Checklist padrão trimestral',
            'anual' => 'Checklist padrão anual',
        ];

        $formulario = Formulario::create([
            'nome' => $nomes[$periodicidade] ?? 'Checklist padrão',
            'empresa_id' => $empresaId,
            'tipo' => 'modelo_checklist',
            'periodicidade' => $periodicidade,
            'maquina_id' => null,
        ]);

        $checklistsPlanilha = require database_path('seeders/data/checklists_pta.php');

        foreach ($checklistsPlanilha[$periodicidade] ?? [] as $index => $item) {
            $formulario->itens()->create([
                'pergunta' => $item['codigo'].' - '.$item['componente'].': '.$item['criterio'],
                'tipo_resposta' => 'conformidade',
                'ordem' => $index + 1,
                'dispara_alerta' => true,
                'obrigatorio' => true,
            ]);
        }

        return $formulario->load('itens');
    }

    public function criarChecklistDaPlanilha(Maquina $maquina, string $periodicidade, array $itens): Formulario
    {
        $nomes = [
            'diario' => 'Checklist Diário',
            'mensal' => 'Checklist Mensal',
            'trimestral' => 'Checklist Trimestral / 150h',
            'anual' => 'Checklist Anual',
        ];

        $formulario = Formulario::create([
            'nome' => ($nomes[$periodicidade] ?? 'Checklist').' - '.$maquina->nome,
            'empresa_id' => $maquina->empresa_id,
            'tipo' => 'checklist_maquina',
            'periodicidade' => $periodicidade,
            'maquina_id' => $maquina->id,
        ]);

        foreach ($itens as $index => $item) {
            $formulario->itens()->create([
                'pergunta' => $item['codigo'].' - '.$item['componente'].': '.$item['criterio'],
                'tipo_resposta' => 'conformidade',
                'ordem' => $index + 1,
                'dispara_alerta' => true,
                'obrigatorio' => true,
            ]);
        }

        return $formulario;
    }

    public function criarFormularioManutencao(Maquina $maquina): Formulario
    {
        return $this->modeloManutencaoPadrao($maquina->empresa_id, 'preventiva');
    }

    public function modeloManutencaoPadrao(?int $empresaId = null, string $tipoManutencao = 'preventiva'): Formulario
    {
        $tipoManutencao = array_key_exists($tipoManutencao, self::TIPOS_MANUTENCAO)
            ? $tipoManutencao
            : 'preventiva';

        $formulario = Formulario::query()
            ->with('itens')
            ->where('tipo', 'manutencao')
            ->where('periodicidade', $tipoManutencao)
            ->whereNull('maquina_id')
            ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId))
            ->latest()
            ->first();

        if (! $formulario) {
            $legado = Formulario::query()
                ->with('itens')
                ->where('tipo', 'manutencao')
                ->whereNull('periodicidade')
                ->whereNull('maquina_id')
                ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId))
                ->latest()
                ->first();

            $formulario = Formulario::create([
                'nome' => 'Manutenção '.strtolower(self::TIPOS_MANUTENCAO[$tipoManutencao]),
                'empresa_id' => $empresaId,
                'tipo' => 'manutencao',
                'periodicidade' => $tipoManutencao,
                'maquina_id' => null,
            ]);

            $itens = $legado?->itens->isNotEmpty()
                ? $legado->itens
                : collect($this->itensManutencaoPadrao());

            foreach ($itens as $index => $item) {
                $pergunta = is_array($item) ? $item['pergunta'] : $item->pergunta;

                if ($this->isItemLegadoManutencao($pergunta)) {
                    continue;
                }

                $formulario->itens()->create([
                    'pergunta' => $pergunta,
                    'tipo_resposta' => is_array($item) ? $item['tipo_resposta'] : $item->tipo_resposta,
                    'ordem' => is_array($item) ? ($item['ordem'] ?? $index + 1) : $item->ordem,
                    'dispara_alerta' => false,
                    'obrigatorio' => is_array($item) ? ($item['obrigatorio'] ?? true) : $item->obrigatorio,
                ]);
            }
        }

        $this->garantirItensBasicosManutencao($formulario);
        $this->removerItensLegadosManutencao($formulario);

        return $formulario->load('itens');
    }

    private function itensManutencaoPadrao(): array
    {
        return [
            ['pergunta' => 'Horímetro', 'tipo_resposta' => 'numero', 'ordem' => 1, 'obrigatorio' => true],
            ['pergunta' => 'Descrição detalhada da intervenção', 'tipo_resposta' => 'texto', 'ordem' => 2, 'obrigatorio' => true],
            ['pergunta' => 'Profissional técnico ou empresa', 'tipo_resposta' => 'texto', 'ordem' => 3, 'obrigatorio' => true],
            ['pergunta' => 'Assinatura / nome do responsável', 'tipo_resposta' => 'texto', 'ordem' => 4, 'obrigatorio' => true],
        ];
    }

    private function garantirItensBasicosManutencao(Formulario $formulario): void
    {
        $formulario->load('itens');

        $existentes = $formulario->itens
            ->map(fn ($item) => $this->chaveItemManutencao($item->pergunta))
            ->filter()
            ->values();

        $ordem = ((int) $formulario->itens->max('ordem')) + 1;

        foreach ($this->itensManutencaoPadrao() as $item) {
            if ($existentes->contains($this->chaveItemManutencao($item['pergunta']))) {
                continue;
            }

            $formulario->itens()->create([
                ...$item,
                'ordem' => $ordem++,
                'dispara_alerta' => false,
            ]);
        }
    }

    private function chaveItemManutencao(string $pergunta): ?string
    {
        $texto = str($pergunta)->lower()->ascii();

        return match (true) {
            $texto->contains('horimetro') => 'horimetro',
            $texto->contains('descricao') || $texto->contains('intervencao') || $texto->contains('foi feito') => 'descricao',
            $texto->contains('profissional') || $texto->contains('tecnico') || $texto->contains('empresa') => 'profissional',
            $texto->contains('assinatura') || $texto->contains('responsavel') => 'assinatura',
            default => null,
        };
    }

    private function removerItensLegadosManutencao(Formulario $formulario): void
    {
        $formulario->load('itens');

        $formulario->itens
            ->filter(fn ($item) => $this->isItemLegadoManutencao($item->pergunta))
            ->each
            ->delete();
    }

    private function isItemLegadoManutencao(string $pergunta): bool
    {
        return str($pergunta)->lower()->ascii()->contains('o que foi feito');
    }
}
