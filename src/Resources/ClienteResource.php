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
}
