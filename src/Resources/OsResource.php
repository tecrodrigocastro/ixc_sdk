<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Ordens de Serviço (OS) — IXC Soft.
 *
 * OS abertas por técnico, OS fechadas no período, assuntos, diagnósticos,
 * materiais utilizados, produtos do catálogo e equipamentos de rede.
 *
 * Tabela su_oss_chamado — valores de status:
 *   'AG' = Agendado | 'F' = Fechado | 'C' = Cancelado | 'A' = Aberto
 *
 * Endpoints cobertos:
 *   GET /su_oss_chamado     — ordens de serviço
 *   GET /su_oss_assunto     — tipos/assuntos de OS
 *   GET /su_diagnostico     — diagnósticos de OS
 *   GET /movimento_produtos — materiais/produtos utilizados
 *   GET /produtos           — catálogo de produtos
 *   GET /radpop_radio       — equipamentos rádio/fibra
 *
 * Dados de referência (assunto, diagnóstico, produto) raramente mudam — se
 * quiser cache, envolva o HttpClientInterface injetado com
 * RedRodrigo\IxcSdk\Http\CachingHttpClient.
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class OsResource extends AbstractResource
{
    /**
     * OS abertas (agendadas, aguardando execução) de um técnico, ordenadas
     * por data_agenda ascendente (mais urgente primeiro).
     *
     * @return list<array<string, mixed>>
     */
    public function getOsAbertasByTecnico(string $idTecnico): array
    {
        $query = QueryBuilder::for('su_oss_chamado.id_tecnico')
            ->query($idTecnico)
            ->perPage(200)
            ->sortBy('su_oss_chamado.data_agenda', 'asc')
            ->filter('su_oss_chamado.status', '=', 'AG')
            ->filter('su_oss_chamado.setor', '=', 1);

        return $this->list('/su_oss_chamado', $query)->items;
    }

    /**
     * OS fechadas (concluídas) de um técnico em um intervalo de datas.
     *
     * @param  string  $data  Data/hora inicial (ex: '2024-07-01 00:00:00')
     * @param  string  $datafinal  Data/hora final (ex: '2024-07-31 23:59:59')
     */
    public function getOsFechadasByTecnico(string $idTecnico, string $data, string $datafinal): array
    {
        $query = QueryBuilder::for('su_oss_chamado.id_tecnico')
            ->query($idTecnico)
            ->perPage(200)
            ->sortBy('su_oss_chamado.data_agenda', 'desc')
            ->filter('su_oss_chamado.data_agenda', '>=', $data)
            ->filter('su_oss_chamado.data_agenda', '<', $datafinal)
            ->filter('su_oss_chamado.status', '=', 'F')
            ->filter('su_oss_chamado.setor', '=', 1);

        return $this->query('/su_oss_chamado', $query);
    }

    /**
     * Assunto/tipo de uma OS pelo ID.
     *
     * @return list<array<string, mixed>>
     */
    public function getAssuntoOs(string $idAssunto): array
    {
        $query = QueryBuilder::for('su_oss_assunto.id')
            ->query($idAssunto)
            ->perPage(200)
            ->sortBy('su_oss_assunto.id', 'desc');

        return $this->list('/su_oss_assunto', $query)->items;
    }

    /**
     * Todos os assuntos/tipos de OS cadastrados (tabela `su_oss_assunto`) —
     * id → nome (ex: 2 => "CLIENTE SEM INTERNET").
     *
     * `su_oss_chamado.id_assunto` só traz o ID numérico, sem nome resolvido;
     * use este método pra montar um cache local id→nome — tabela de
     * referência pequena e estável (106 registros hoje), uma chamada só
     * resolve tudo, mesmo padrão de `ClienteResource::getAllCidades()`.
     *
     * @return list<array{id: string, assunto: string}>
     */
    public function getAllAssuntosOs(): array
    {
        $query = QueryBuilder::for('su_oss_assunto.id')
            ->perPage(2000)
            ->sortBy('su_oss_assunto.id', 'asc');

        return $this->list('/su_oss_assunto', $query)->items;
    }

    /**
     * Produtos/materiais utilizados em uma OS específica.
     */
    public function getProdutosByOs(string $idOs): array
    {
        $query = QueryBuilder::for('movimento_produtos.id_oss_chamado')
            ->query($idOs)
            ->perPage(200)
            ->sortBy('movimento_produtos.id_oss_chamado', 'desc');

        return $this->query('/movimento_produtos', $query);
    }

    /**
     * Diagnóstico registrado em uma OS pelo ID.
     */
    public function getDiagnosticoOs(string $idDiagnostico): array
    {
        $query = QueryBuilder::for('su_diagnostico.id')
            ->query($idDiagnostico)
            ->perPage(200)
            ->sortBy('su_diagnostico.id', 'desc');

        return $this->query('/su_diagnostico', $query);
    }

    /**
     * Dados de um produto pelo ID.
     *
     * @return list<array<string, mixed>>
     */
    public function getProdutoById(string $idProduto): array
    {
        $query = QueryBuilder::for('produtos.id')
            ->query($idProduto)
            ->perPage(1)
            ->sortBy('produtos.id', 'desc');

        return $this->list('/produtos', $query)->items;
    }

    /**
     * OS de um cliente em um período, excluindo canceladas
     * (mais recente primeiro).
     *
     * @return list<array<string, mixed>>
     */
    public function getOsByCliente(string $clienteId, string $dataInicial, string $dataFinal): array
    {
        $query = QueryBuilder::for('su_oss_chamado.id_cliente')
            ->query($clienteId)
            ->perPage(10)
            ->sortBy('su_oss_chamado.data_abertura', 'desc')
            ->filter('su_oss_chamado.data_abertura', '>=', $dataInicial)
            ->filter('su_oss_chamado.data_abertura', '<=', $dataFinal)
            ->filter('su_oss_chamado.status', '<>', 'C');

        return $this->list('/su_oss_chamado', $query)->items;
    }

    /**
     * Busca um equipamento rádio/fibra pelo MAC address ou número de série (LIKE).
     *
     * @return list<array<string, mixed>>
     */
    public function getEquipamentoBySerial(string $serial): array
    {
        $query = QueryBuilder::for('radpop_radio.mac')
            ->query($serial)
            ->operator('L')
            ->perPage(10)
            ->sortBy('radpop_radio.id', 'desc');

        return $this->list('/radpop_radio', $query)->items;
    }

    /**
     * Uma OS específica pelo ID.
     *
     * @return array<string, mixed>
     */
    public function getOsById(int $idOs): array
    {
        $query = QueryBuilder::for('su_oss_chamado.id')
            ->query($idOs)
            ->perPage(1)
            ->sortBy('su_oss_chamado.id', 'desc');

        return $this->list('/su_oss_chamado', $query)->first() ?? [];
    }

    /**
     * OS abertas em um período, com filtro de status opcional (LIKE).
     *
     * @return list<array<string, mixed>>
     */
    public function getOsByPeriod(string $dataInicial, string $dataFinal, string $status = ''): array
    {
        $query = QueryBuilder::for('su_oss_chamado.id')
            ->perPage(2000)
            ->sortBy('su_oss_chamado.id', 'desc')
            ->filter('su_oss_chamado.data_abertura', 'GE', $dataInicial)
            ->filter('su_oss_chamado.data_abertura', 'LE', $dataFinal);

        if ($status !== '') {
            $query->filter('su_oss_chamado.status', 'L', $status);
        }

        return $this->list('/su_oss_chamado', $query)->items;
    }
}
