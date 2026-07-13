<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Comercial e vendas — IXC Soft.
 *
 * Contratos ativados/cancelados/renovados, bloqueios, negativações,
 * vendedores/equipes e OS por tipo de serviço.
 *
 * Todos os métodos de contratos/OS aceitam $start e $end no formato 'Y-m-d'.
 *
 * Endpoints cobertos:
 *   GET /cliente_contrato — contratos de clientes
 *   GET /vd_contratos     — planos de venda
 *   GET /vendedor         — cadastro de vendedores
 *   GET /usuarios         — usuários/equipes do sistema
 *   GET /su_oss_chamado   — ordens de serviço
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class ComercialResource extends AbstractResource
{
    // =========================================================================
    // CONTRATOS
    // =========================================================================

    /** Contratos ativados no período (novos e renovações). */
    public function getContratosAtivos(string $start, string $end): array
    {
        $query = QueryBuilder::for('cliente_contrato.id_vendedor')
            ->perPage(400)
            ->sortBy('cliente_contrato.id', 'desc')
            ->filter('cliente_contrato.status', '=', 'A')
            ->filter('cliente_contrato.data_ativacao', '>=', $start)
            ->filter('cliente_contrato.data_ativacao', '<=', $end)
            ->filter('cliente_contrato.status_internet', '=', 'A');

        return $this->query('/cliente_contrato', $query);
    }

    /** Contratos de clientes NOVOS ativados no período (exclui renovações). */
    public function getContratosNovos(string $start, string $end): array
    {
        $query = QueryBuilder::for('cliente_contrato.id_vendedor')
            ->perPage(800)
            ->sortBy('cliente_contrato.id', 'desc')
            ->filter('cliente_contrato.status', '=', 'A')
            ->filter('cliente_contrato.data_ativacao', '>=', $start)
            ->filter('cliente_contrato.data_ativacao', '<=', $end)
            ->filter('cliente_contrato.status_internet', '=', 'A')
            ->filter('cliente_contrato.motivo_inclusao', '=', 'I');

        return $this->query('/cliente_contrato', $query);
    }

    /** Contratos cancelados por inadimplência (débito) no período. */
    public function getContratosCanceladosDebito(string $start, string $end): array
    {
        $query = QueryBuilder::for('cliente_contrato.id_vendedor')
            ->perPage(200)
            ->sortBy('cliente_contrato.id', 'desc')
            ->filter('cliente_contrato.data_cancelamento', '>=', $start)
            ->filter('cliente_contrato.data_cancelamento', '<=', $end)
            ->filter('cliente_contrato.motivo_cancelamento', '=', '20');

        return $this->query('/cliente_contrato', $query);
    }

    /** Contratos cancelados por rescisão no período. */
    public function getContratosCanceladosRescisao(string $start, string $end): array
    {
        $query = QueryBuilder::for('cliente_contrato.id_vendedor')
            ->perPage(200)
            ->sortBy('cliente_contrato.id', 'desc')
            ->filter('cliente_contrato.data_cancelamento', '>=', $start)
            ->filter('cliente_contrato.data_cancelamento', '<=', $end)
            ->filter('cliente_contrato.motivo_cancelamento', '=', '8');

        return $this->query('/cliente_contrato', $query);
    }

    /** Contratos renovados no período. */
    public function getContratosRenovados(string $start, string $end): array
    {
        $query = QueryBuilder::for('cliente_contrato.id_vendedor')
            ->perPage(200)
            ->sortBy('cliente_contrato.id', 'desc')
            ->filter('cliente_contrato.data_renovacao', '>=', $start)
            ->filter('cliente_contrato.data_renovacao', '<=', $end);

        return $this->query('/cliente_contrato', $query);
    }

    /** Contratos bloqueados automaticamente no período. */
    public function getContratosBloqueados(string $start, string $end): array
    {
        $query = QueryBuilder::for('cliente_contrato.id_vendedor')
            ->perPage(200)
            ->sortBy('cliente_contrato.id', 'desc')
            ->filter('cliente_contrato.status_internet', '=', 'CA')
            ->filter('cliente_contrato.dt_ult_bloq_auto', '>=', $start)
            ->filter('cliente_contrato.dt_ult_bloq_auto', '<=', $end);

        return $this->query('/cliente_contrato', $query);
    }

    /** Contratos negativados no período. */
    public function getContratosNegativados(string $start, string $end): array
    {
        $query = QueryBuilder::for('cliente_contrato.id_vendedor')
            ->perPage(200)
            ->sortBy('cliente_contrato.id', 'desc')
            ->filter('cliente_contrato.data_negativacao', '>=', $start)
            ->filter('cliente_contrato.data_negativacao', '<=', $end);

        return $this->query('/cliente_contrato', $query);
    }

    /** Contratos em débito (inadimplentes), agrupáveis por vendedor. */
    public function getContratosDebitoVendedor(string $start, string $end): array
    {
        $query = QueryBuilder::for('cliente_contrato.id_vendedor')
            ->perPage(300)
            ->sortBy('cliente_contrato.id', 'desc')
            ->filter('cliente_contrato.status', '=', 'A')
            ->filter('cliente_contrato.data_ativacao', '>=', $start)
            ->filter('cliente_contrato.data_ativacao', '<=', $end)
            ->filter('cliente_contrato.status_internet', '!=', 'A')
            ->filter('cliente_contrato.status_internet', '!=', 'AA')
            ->filter('cliente_contrato.motivo_inclusao', '=', 'I');

        return $this->query('/cliente_contrato', $query);
    }

    /**
     * Contratos bloqueados automaticamente desde $data até hoje.
     *
     * Diferente de getContratosBloqueados(): também exige status='A' (contrato
     * ainda ativo, apenas com a internet bloqueada), e aceita $data em qualquer
     * formato reconhecido por strtotime() em vez de exigir Y-m-d.
     *
     * @param  string|null  $data  Data inicial. Se omitida, usa a data de hoje.
     * @return array<string, mixed>
     */
    public function getAllBloqueados(?string $data = null): array
    {
        $initialDate = $data ? date('Y-m-d', strtotime($data)) : date('Y-m-d');
        $finalDate = date('Y-m-d');

        $query = QueryBuilder::for('cliente_contrato.id_cliente')
            ->perPage(2000)
            ->sortBy('cliente_contrato.id', 'asc')
            ->filter('cliente_contrato.status_internet', '=', 'CA')
            ->filter('cliente_contrato.status', '=', 'A')
            ->filter('cliente_contrato.dt_ult_bloq_auto', '>=', $initialDate)
            ->filter('cliente_contrato.dt_ult_bloq_auto', '<=', $finalDate);

        return $this->query('/cliente_contrato', $query);
    }

    // =========================================================================
    // VENDEDORES E PLANOS
    // =========================================================================

    /** Nome de um vendedor pelo ID, ou 'Vendedor padrão' se não encontrado. */
    public function getVendedorById(string $id): string
    {
        $query = QueryBuilder::for('vendedor.id')
            ->query($id)
            ->perPage(1)
            ->sortBy('vendedor.nome', 'asc')
            ->filter('vendedor.status', '=', 'A');

        $dados = $this->query('/vendedor', $query);

        if (empty($dados['total']) || $dados['total'] == '0') {
            return 'Vendedor padrão';
        }

        return $dados['registros'][0]['nome'] ?? 'Vendedor padrão';
    }

    /**
     * Todos os vendedores ativos, indexados pelo ID: `[id => [id]]`.
     *
     * @return array<int|string, array{0: int|string}>
     */
    public function getTodosVendedores(): array
    {
        $query = QueryBuilder::for('vendedor.id')
            ->perPage(150)
            ->sortBy('vendedor.nome', 'asc')
            ->filter('vendedor.status', '=', 'A');

        $indexed = [];
        foreach ($this->list('/vendedor', $query)->items as $vendedor) {
            $indexed[$vendedor['id']] = [$vendedor['id']];
        }

        return $indexed;
    }

    /** Valor mensal de um plano/contrato de venda. */
    public function getValorContrato(string $idVdContrato): float
    {
        $query = QueryBuilder::for('vd_contratos.id')
            ->query($idVdContrato)
            ->perPage(1)
            ->sortBy('vd_contratos.nome', 'asc')
            ->filter('vd_contratos.Ativo', '=', 'S');

        $registro = $this->list('/vd_contratos', $query)->first();

        return (float) ($registro['valor_contrato'] ?? 0);
    }

    /** Equipes de campo cadastradas no IXC (usuários do grupo 4 com nome "EQUIPE..."). */
    public function getEquipes(): array
    {
        $query = QueryBuilder::for('usuarios.id_grupo')
            ->query('4')
            ->perPage(200)
            ->sortBy('usuarios.id', 'desc')
            ->filter('usuarios.nome', 'L', 'EQUIPE');

        return $this->query('/usuarios', $query);
    }

    // =========================================================================
    // OS POR TIPO (su_oss_chamado — status='F', por período)
    //
    // id_assunto mapeamento (confirmar com a instalação do IXC do cliente):
    //   5  → Mudança de Endereço      23 → Mudança de Titularidade
    //   25 → Upgrade de Plano         28 → Rescisão de Contrato
    //   33 → Reativação               60 → Cancelamento por Débito II
    //   78 → Cortesia 100%            79 → Cortesia 50%
    // =========================================================================

    public function getOsUpgrades(string $start, string $end): array
    {
        return $this->getOsPorAssunto($start, $end, '25');
    }

    public function getOsMudancaEndereco(string $start, string $end): array
    {
        return $this->getOsPorAssunto($start, $end, '5');
    }

    public function getOsMudancaTitularidade(string $start, string $end): array
    {
        return $this->getOsPorAssunto($start, $end, '23');
    }

    public function getOsReativacao(string $start, string $end): array
    {
        return $this->getOsPorAssunto($start, $end, '33');
    }

    public function getOsRescisao(string $start, string $end): array
    {
        return $this->getOsPorAssunto($start, $end, '28');
    }

    public function getOsCortesia50(string $start, string $end): array
    {
        return $this->getOsPorAssunto($start, $end, '79');
    }

    public function getOsCortesia100(string $start, string $end): array
    {
        return $this->getOsPorAssunto($start, $end, '78');
    }

    public function getOsCancelamentoDebitoII(string $start, string $end): array
    {
        return $this->getOsPorAssunto($start, $end, '60');
    }

    /** OS fechadas no período filtrando por id_assunto. */
    private function getOsPorAssunto(string $start, string $end, string $idAssunto): array
    {
        $query = QueryBuilder::for('su_oss_chamado.id')
            ->perPage(400)
            ->sortBy('su_oss_chamado.id', 'desc')
            ->filter('su_oss_chamado.data_abertura', '>=', $start)
            ->filter('su_oss_chamado.data_fechamento', '<=', $end)
            ->filter('su_oss_chamado.id_assunto', '=', $idAssunto)
            ->filter('su_oss_chamado.status', '=', 'F');

        return $this->query('/su_oss_chamado', $query);
    }
}
