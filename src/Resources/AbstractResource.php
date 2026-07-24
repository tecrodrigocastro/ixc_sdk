<?php

namespace RedRodrigo\IxcSdk\Resources;

use RedRodrigo\IxcSdk\Contracts\HttpClientInterface;
use RedRodrigo\IxcSdk\Query\QueryBuilder;
use RedRodrigo\IxcSdk\Support\ListResponse;

abstract class AbstractResource
{
    public function __construct(protected readonly HttpClientInterface $client)
    {
    }

    /**
     * Executa uma consulta de listagem e devolve o corpo bruto decodificado.
     *
     * @return array<string, mixed>
     */
    protected function query(string $endpoint, QueryBuilder $query): array
    {
        return $this->client->get($endpoint, $query->toArray());
    }

    /**
     * Executa uma consulta de listagem e normaliza o resultado em ListResponse.
     */
    protected function list(string $endpoint, QueryBuilder $query): ListResponse
    {
        return ListResponse::fromArray($this->query($endpoint, $query));
    }

    /**
     * Chamada de baixo nível para endpoints que não seguem o protocolo
     * qtype/query/oper (ex: /desbloqueio_confianca, que só recebe um `id`).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function raw(string $endpoint, array $params): array
    {
        return $this->client->get($endpoint, $params);
    }

    /**
     * Chamada de baixo nível que devolve o corpo bruto da resposta sem
     * decodificar como JSON (ex: /get_boleto, que retorna bytes de um PDF).
     *
     * @param  array<string, mixed>  $params
     */
    protected function bytes(string $endpoint, array $params): string
    {
        return $this->client->getRaw($endpoint, $params);
    }

    /**
     * Insere/edita/executa uma ação de escrita. O IXC não usa o protocolo
     * qtype/query/oper para escrita — os campos vão direto no corpo.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function insert(string $endpoint, array $fields): array
    {
        return $this->client->post($endpoint, $fields);
    }
}
