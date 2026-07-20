<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Query\QueryBuilder;

/**
 * Clientes e usuários PPPoE — IXC Soft.
 *
 * Endpoints cobertos:
 *   GET /cliente          — dados cadastrais
 *   GET /cliente_contrato — contratos de internet
 *   GET /radusuarios      — logins PPPoE
 *   GET /cidade           — tabela de cidades (id → nome/UF)
 *
 * @see https://wikiixcsoft.ixcsoft.com.br/
 */
final class ClienteResource extends AbstractResource
{
    /**
     * Busca clientes por ID exato (quando $termo é numérico) ou por
     * nome/razão social com LIKE (quando $termo é texto).
     *
     * @return list<array<string, mixed>>
     */
    public function searchCliente(string $termo): array
    {
        $isId = is_numeric($termo);

        $query = QueryBuilder::for($isId ? 'cliente.id' : 'cliente.razao')
            ->query($termo)
            ->operator($isId ? '=' : 'L')
            ->perPage(10)
            ->sortBy('cliente.id', 'desc');

        return $this->list('/cliente', $query)->items;
    }

    /**
     * Retorna todos os contratos de internet vinculados a um cliente.
     *
     * @return list<array<string, mixed>>
     */
    public function getInfoContrato(int $idCliente): array
    {
        $query = QueryBuilder::for('cliente_contrato.id_cliente')
            ->query($idCliente)
            ->perPage(200)
            ->sortBy('cliente_contrato.id', 'desc');

        return $this->list('/cliente_contrato', $query)->items;
    }

    /**
     * Retorna os dados de login PPPoE (radusuarios) pelo id_cliente.
     *
     * @return list<array<string, mixed>>
     */
    public function getRadClientePorCliente(int $idCliente): array
    {
        $query = QueryBuilder::for('radusuarios.id_cliente')
            ->query($idCliente)
            ->perPage(20)
            ->sortBy('radusuarios.id', 'desc');

        return $this->list('/radusuarios', $query)->items;
    }

    /**
     * Retorna os dados de login PPPoE pelo ID do próprio registro em radusuarios.
     *
     * @return list<array<string, mixed>>
     */
    public function getRadClientePorLogin(string $idLogin): array
    {
        $query = QueryBuilder::for('radusuarios.id')
            ->query($idLogin)
            ->perPage(20)
            ->sortBy('radusuarios.id', 'desc');

        return $this->list('/radusuarios', $query)->items;
    }

    /**
     * Retorna o serial/MAC da ONU (campo `onu_mac`) vinculado a um contrato.
     */
    public function getOnuSerialByContrato(string $contractId): ?string
    {
        $query = QueryBuilder::for('radusuarios.id_contrato')
            ->query($contractId)
            ->perPage(1)
            ->sortBy('radusuarios.id', 'desc');

        $registro = $this->list('/radusuarios', $query)->first();

        return $registro ? (($registro['onu_mac'] ?? null) ?: null) : null;
    }

    /**
     * Autentica um cliente pelo e-mail e senha do hotsite (portal do cliente).
     *
     * ATENÇÃO: o IXC compara a senha em texto puro neste endpoint.
     * Use apenas em contextos controlados (portal interno / hotsite).
     *
     * @return array{total?: int, registros?: list<array<string, mixed>>}
     */
    public function loginHotsite(string $email, string $senha): array
    {
        $query = QueryBuilder::for('cliente.hotsite_email')
            ->query($email)
            ->perPage(20)
            ->sortBy('cliente.id', 'desc')
            ->filter('cliente.senha', '=', $senha);

        return $this->query('/cliente', $query);
    }

    /**
     * Retorna um contrato de internet pelo seu ID (PK de cliente_contrato).
     *
     * @return array<string, mixed>
     */
    public function getContratoById(int $idContrato): array
    {
        $query = QueryBuilder::for('cliente_contrato.id')
            ->query($idContrato)
            ->perPage(1)
            ->sortBy('cliente_contrato.id', 'desc');

        return $this->list('/cliente_contrato', $query)->first() ?? [];
    }

    /**
     * Todos os clientes ativos, paginados (200 por página).
     *
     * @return array<string, mixed>
     */
    public function getAllClientes(int $page = 1): array
    {
        $query = QueryBuilder::for('cliente.id')
            ->page($page)
            ->perPage(200)
            ->sortBy('cliente.data_cadastro', 'desc')
            ->filter('cliente.ativo', 'L', 'S');

        return $this->query('/cliente', $query);
    }

    /**
     * Um cliente ativo pelo ID, ou array vazio se não encontrado.
     *
     * @return array<string, mixed>
     */
    public function getClienteById(int $id): array
    {
        $query = QueryBuilder::for('cliente.id')
            ->query($id)
            ->perPage(200)
            ->sortBy('cliente.data_cadastro', 'desc')
            ->filter('cliente.ativo', 'L', 'S');

        return $this->list('/cliente', $query)->first() ?? [];
    }

    /**
     * Todas as cidades cadastradas (tabela `cidade`) — id, nome e UF.
     *
     * O campo `cliente.cidade` retorna só o ID numérico da cidade, sem nome
     * resolvido; use este método pra montar um cache local id→nome (a tabela
     * é pequena e estável, uma chamada só resolve tudo — mesmo padrão já
     * usado para técnicos em `FuncionarioResource::getAllFuncionarios()`).
     *
     * @return list<array{id: string, nome: string, uf: string}>
     */
    public function getAllCidades(): array
    {
        // O Brasil tem ~5570 municípios e o IXC cadastra a tabela cidade com
        // a lista nacional inteira (confirmado: total real = 5564) — não é
        // só as cidades onde o provedor atua. 10000 dá margem confortável
        // sem precisar paginar (a API honra qualquer "rp" pedido, sem cap).
        $query = QueryBuilder::for('cidade.id')
            ->perPage(10000)
            ->sortBy('cidade.id', 'asc');

        return $this->list('/cidade', $query)->items;
    }

    /**
     * Clientes ativos que fazem aniversário em $diasAntecedencia dias (0 = hoje).
     *
     * O IXC não suporta MONTH()/DAY() como valor de coluna comum — usamos essas
     * expressões diretamente como nome de campo no grid_param, que a API aceita.
     *
     * @return list<array{id: mixed, id_cliente: mixed, razao: string, whatsapp: string, data_aniversario: string}>
     */
    public function getClientesAniversariantes(int $diasAntecedencia = 0): array
    {
        $data = date('Y-m-d', strtotime("+{$diasAntecedencia} days"));

        $query = QueryBuilder::for('cliente.id')
            ->operator('>=')
            ->perPage(2000)
            ->sortBy('cliente.razao', 'asc')
            ->filter('cliente.ativo', '=', 'S')
            ->filter('MONTH(cliente.data_nascimento)', '=', date('m', strtotime($data)))
            ->filter('DAY(cliente.data_nascimento)', '=', date('d', strtotime($data)));

        $registros = $this->list('/cliente', $query)->items;

        return array_map(static fn (array $c): array => [
            'id' => $c['id'],
            'id_cliente' => $c['id'],
            'razao' => $c['razao'] ?? '',
            'whatsapp' => $c['whatsapp'] ?? '',
            'data_aniversario' => $c['data_nascimento'] ?? '',
        ], $registros);
    }
}
