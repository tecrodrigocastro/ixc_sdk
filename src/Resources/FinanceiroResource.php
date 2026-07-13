<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Financeiro — IXC Soft.
 *
 * Histórico de pagamentos, faturas em aberto e desbloqueio de confiança.
 *
 * Endpoints cobertos:
 *   GET /fn_areceber            — faturas a receber
 *   GET /desbloqueio_confianca  — desbloqueio de confiança
 *   GET /get_pix                — código PIX copia-e-cola de uma fatura
 *   GET /get_boleto              — boleto em PDF de uma fatura
 *
 * Tabela fn_areceber — valores de status:
 *   'A' = Aberto | 'R' = Recebido (pago) | 'C' = Cancelado | 'V' = Vencido
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class FinanceiroResource extends AbstractResource
{
    /**
     * Histórico das últimas 20 faturas pagas de um cliente (mais recente primeiro).
     *
     * @return list<array<string, mixed>>
     */
    public function getHistoricoPagamentos(int $idCliente): array
    {
        $query = QueryBuilder::for('fn_areceber.id_cliente')
            ->query($idCliente)
            ->perPage(20)
            ->sortBy('fn_areceber.data_vencimento', 'desc')
            ->filter('fn_areceber.liberado', '=', 'S')
            ->filter('fn_areceber.status', '!=', 'C')
            ->filter('fn_areceber.status', '=', 'R');

        return $this->list('/fn_areceber', $query)->items;
    }

    /**
     * Todas as faturas em aberto de um cliente, ordenadas por vencimento (mais antiga primeiro).
     *
     * @return list<array<string, mixed>>
     */
    public function getProximasFaturas(int $idCliente): array
    {
        $query = QueryBuilder::for('fn_areceber.id_cliente')
            ->query($idCliente)
            ->perPage(20)
            ->sortBy('fn_areceber.data_vencimento', 'asc')
            ->filter('fn_areceber.liberado', '=', 'S')
            ->filter('fn_areceber.status', '!=', 'C')
            ->filter('fn_areceber.status', '=', 'A');

        return $this->list('/fn_areceber', $query)->items;
    }

    /**
     * Solicita o desbloqueio de confiança para um contrato bloqueado por inadimplência.
     *
     * @return array{type?: string, message?: string}
     */
    public function desbloqueioConfianca(int $idContrato): array
    {
        return $this->raw('/desbloqueio_confianca', ['id' => $idContrato]);
    }

    /**
     * Primeira fatura em aberto de um cliente (mais antiga primeiro), ou
     * array vazio se não houver nenhuma.
     *
     * @return array<string, mixed>
     */
    public function getInvoicesOpenById(int $idCliente): array
    {
        $query = QueryBuilder::for('fn_areceber.id_cliente')
            ->query($idCliente)
            ->perPage(20)
            ->sortBy('fn_areceber.data_vencimento', 'asc')
            ->filter('fn_areceber.liberado', '=', 'S')
            ->filter('fn_areceber.status', '!=', 'C')
            ->filter('fn_areceber.status', '=', 'A');

        return $this->list('/fn_areceber', $query)->first() ?? [];
    }

    /**
     * Todas as faturas em aberto com vencimento no mês atual.
     *
     * @return list<array<string, mixed>>
     */
    public function getAllInvoicesOpen(): array
    {
        $today = date('Y-m-d');

        $query = QueryBuilder::for('fn_areceber.id_cliente')
            ->perPage(2000)
            ->sortBy('fn_areceber.data_vencimento', 'asc')
            ->filter('fn_areceber.liberado', '=', 'S')
            ->filter('fn_areceber.status', '!=', 'C')
            ->filter('fn_areceber.status', '=', 'A')
            ->filter('fn_areceber.data_vencimento', '>=', date('Y-m-01', strtotime($today)))
            ->filter('fn_areceber.data_vencimento', '<=', date('Y-m-t', strtotime($today)));

        return $this->list('/fn_areceber', $query)->items;
    }

    /**
     * Faturas vencidas há pelo menos $diasAtraso dias.
     *
     * @return list<array<string, mixed>>
     */
    public function getClientesComFaturaVencida(int $diasAtraso = 1): array
    {
        $dataLimite = date('Y-m-d', strtotime("-{$diasAtraso} days"));

        $query = QueryBuilder::for('fn_areceber.id_cliente')
            ->perPage(2000)
            ->sortBy('fn_areceber.data_vencimento', 'asc')
            ->filter('fn_areceber.liberado', '=', 'S')
            ->filter('fn_areceber.status', '!=', 'C')
            ->filter('fn_areceber.status', '=', 'A')
            ->filter('fn_areceber.data_vencimento', '<=', $dataLimite);

        return $this->list('/fn_areceber', $query)->items;
    }

    /**
     * Faturas a vencer exatamente em $dias dias.
     *
     * @return list<array<string, mixed>>
     */
    public function getClientesComFaturaAVencer(int $dias = 3): array
    {
        $dataInicio = date('Y-m-d');
        $dataFim = date('Y-m-d', strtotime("+{$dias} days"));

        $query = QueryBuilder::for('fn_areceber.id_cliente')
            ->perPage(2000)
            ->sortBy('fn_areceber.data_vencimento', 'asc')
            ->filter('fn_areceber.liberado', '=', 'S')
            ->filter('fn_areceber.status', '!=', 'C')
            ->filter('fn_areceber.status', '=', 'A')
            ->filter('fn_areceber.data_vencimento', '>=', $dataInicio)
            ->filter('fn_areceber.data_vencimento', '<=', $dataFim);

        return $this->list('/fn_areceber', $query)->items;
    }

    /**
     * Código PIX copia-e-cola (EMV) de uma fatura, ou null se indisponível.
     */
    public function getPixCopiaCola(int $idFatura): ?string
    {
        $dados = $this->raw('/get_pix', ['id_areceber' => $idFatura]);

        return $dados['pix']['dadosPix']['pixCopiaECola'] ?? null;
    }

    /**
     * Gera o boleto em PDF de uma fatura e devolve os bytes brutos do arquivo.
     */
    public function getBoletosByCliente(int $idFatura): string
    {
        return $this->bytes('/get_boleto', [
            'boletos' => $idFatura,
            'juro' => 'N',
            'multa' => 'N',
            'atualiza_boleto' => 'S',
            'tipo_boleto' => 'arquivo',
            'base64' => 'N',
        ]);
    }
}
