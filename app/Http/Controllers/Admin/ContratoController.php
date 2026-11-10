<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Orcamento;
use App\Models\SistemaConfiguracao;
use Illuminate\Http\Request;

class ContratoController extends Controller
{
    public function index()
    {
        if (auth('admin')->user()?->isComercial()) {
            return view('admin.contratos.index', [
                'somenteConsulta' => true,
                'orcamentosAprovados' => $this->scopeEmpresa(Orcamento::query())
                    ->with(['cliente', 'itens.maquina'])
                    ->where('status', 'aprovado')
                    ->latest()
                    ->paginate(20),
            ]);
        }

        return view('admin.contratos.index', [
            'somenteConsulta' => false,
            'modelo' => SistemaConfiguracao::valor('modelo_contrato_locacao', $this->modeloPadrao()),
        ]);
    }

    public function update(Request $request)
    {
        abort_if(auth('admin')->user()?->isComercial(), 403);

        $data = $request->validate([
            'modelo' => ['required', 'string', 'max:20000'],
        ]);

        SistemaConfiguracao::definir('modelo_contrato_locacao', $data['modelo']);

        return redirect()->route('admin.contratos.index')->with('status', 'Modelo de contrato atualizado.');
    }

    public static function modeloPadrao(): string
    {
        return <<<'TEXT'
1. OBJETO
1.1. O presente contrato tem por objeto a locação dos equipamentos descritos no quadro de locação, de propriedade ou responsabilidade da LOCADORA, para utilização pela LOCATÁRIA no local informado neste instrumento.
1.2. A LOCATÁRIA declara conhecer as características dos equipamentos contratados e se obriga a utilizá-los exclusivamente para a finalidade compatível com sua natureza, capacidade e manual de operação.

2. PRAZO DA LOCAÇÃO
2.1. A locação vigorará pelo prazo definido nas condições comerciais, iniciando-se na data de entrega ou disponibilização dos equipamentos e encerrando-se somente após sua devolução efetiva à LOCADORA.
2.2. Encerrado o prazo contratado sem devolução dos equipamentos, a locação poderá ser prorrogada automaticamente, com cobrança do período adicional conforme os valores pactuados ou tabela comercial vigente.
2.3. Não haverá cálculo proporcional de locação por devolução antecipada, salvo se houver aceite formal e expresso da LOCADORA.

3. PREÇO, FATURAMENTO E PAGAMENTO
3.1. A LOCATÁRIA pagará à LOCADORA os valores indicados no quadro comercial deste contrato, incluindo locação, frete, complementos, impostos e demais encargos aplicáveis.
3.2. A data prevista de faturamento será a indicada nas condições comerciais deste contrato. O pagamento deverá ocorrer na forma e prazo aprovados no orçamento.
3.3. O não pagamento no vencimento poderá gerar multa de 2%, juros de 1% ao mês, correção monetária e despesas de cobrança.
3.4. Em caso de inadimplemento, a LOCADORA poderá suspender serviços, restringir nova utilização, protestar títulos, adotar medidas de cobrança e retirar os equipamentos locados, sem prejuízo das demais medidas legais cabíveis.

4. ENTREGA, FRETE E DEVOLUÇÃO
4.1. O transporte de entrega, coleta, carga, descarga, içamento e movimentação dos equipamentos será de responsabilidade da parte indicada nas condições comerciais.
4.2. A LOCATÁRIA deverá disponibilizar responsável para acompanhar a entrega e a devolução, assinando os checklists ou documentos equivalentes.
4.3. A devolução somente será considerada concluída após retorno dos equipamentos, conferência e recebimento pela LOCADORA.
4.4. Danos, avarias, ausência de acessórios, sujeira excessiva ou inconformidades constatadas na devolução poderão ser cobrados da LOCATÁRIA.

5. USO, GUARDA E RESPONSABILIDADE DA LOCATÁRIA
5.1. A LOCATÁRIA é responsável pela guarda, conservação, limpeza, segurança e correta utilização dos equipamentos durante todo o período em que estiverem sob sua posse.
5.2. A LOCATÁRIA não poderá emprestar, sublocar, ceder, transferir, modificar ou deslocar os equipamentos para local diverso do contratado sem autorização prévia e formal da LOCADORA.
5.3. A LOCATÁRIA responderá por danos decorrentes de mau uso, negligência, imprudência, imperícia, operação inadequada, sobrecarga, ambiente impróprio, ausência de inspeções diárias, furto, roubo, extravio, sinistro ou caso fortuito enquanto os equipamentos estiverem sob sua responsabilidade.

6. OPERAÇÃO, TREINAMENTO E SEGURANÇA
6.1. A operação dos equipamentos é de responsabilidade exclusiva da LOCATÁRIA, que deverá utilizar operadores aptos, treinados e autorizados.
6.2. A LOCATÁRIA deverá observar os manuais, normas de segurança, limites de carga, condições de solo, área de trabalho e demais orientações da LOCADORA ou do fabricante.
6.3. Caso seja necessária familiarização, treinamento, ART ou documentação complementar, as condições e eventuais valores deverão ser definidos entre as partes.

7. MANUTENÇÃO PREVENTIVA E CORRETIVA
7.1. A LOCATÁRIA deverá comunicar imediatamente qualquer falha, dano, alerta ou anormalidade identificada nos equipamentos.
7.2. Somente técnicos autorizados pela LOCADORA poderão realizar manutenção ou reparo nos equipamentos, salvo autorização formal em sentido contrário.
7.3. A LOCATÁRIA deverá disponibilizar os equipamentos para manutenções preventivas quando solicitado e informar leituras de horímetro sempre que necessário.
7.4. Custos decorrentes de mau uso, danos, avarias, perda de acessórios, deslocamentos, peças, limpeza, lubrificação ou reparos causados por responsabilidade da LOCATÁRIA poderão ser cobrados separadamente.

8. SEGURO
8.1. Havendo seguro contratado pela LOCADORA, sua cobertura observará as condições da respectiva apólice.
8.2. O seguro não afasta a responsabilidade da LOCATÁRIA por mau uso, negligência, operação inadequada, descumprimento de normas de segurança ou utilização fora das condições contratadas.

9. RESCISÃO
9.1. A LOCADORA poderá rescindir o contrato em caso de inadimplência, uso irregular, transferência não autorizada, prestação de informações falsas, danos aos equipamentos, descumprimento contratual ou qualquer fato que coloque em risco os equipamentos ou terceiros.
9.2. A rescisão não afasta a obrigação de pagamento dos valores vencidos, vincendos, multas, indenizações, custos de reparo, fretes e demais despesas devidas.

10. DISPOSIÇÕES FINAIS
10.1. Este contrato obriga as partes, seus sucessores e representantes, constituindo título executivo extrajudicial nos termos da legislação aplicável.
10.2. Qualquer tolerância entre as partes será considerada mera liberalidade, não implicando renúncia de direitos.
10.3. As partes elegem o foro da comarca competente indicada pela LOCADORA para dirimir dúvidas oriundas deste contrato, salvo disposição legal obrigatória em sentido diverso.
TEXT;
    }
}
