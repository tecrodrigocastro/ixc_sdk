<?php

namespace RedRodrigo\IxcSdk\Contracts;

use RedRodrigo\IxcSdk\Exceptions\IxcRequestException;
use RedRodrigo\IxcSdk\Exceptions\IxcResponseException;

/**
 * Abstrai o transporte HTTP usado para falar com a API do IXC Soft.
 *
 * Implementações recebem os parâmetros já resolvidos (tipicamente vindos de
 * RedRodrigo\IxcSdk\Query\QueryBuilder::toArray()) e devem devolver o corpo
 * da resposta já decodificado como array associativo.
 */
interface HttpClientInterface
{
    /**
     * @param  string  $endpoint  Caminho do endpoint IXC, ex: "/cliente"
     * @param  array<string, mixed>  $params  Parâmetros do protocolo de listagem do IXC
     * @return array<string, mixed> Corpo da resposta decodificado
     *
     * @throws IxcRequestException  Falha de transporte/HTTP
     * @throws IxcResponseException Resposta com corpo que não é um JSON válido
     */
    public function get(string $endpoint, array $params): array;

    /**
     * Como get(), mas devolve o corpo bruto da resposta sem tentar decodificar
     * como JSON. Necessário para endpoints que retornam binário (ex: /get_boleto,
     * que devolve os bytes de um PDF).
     *
     * @param  string  $endpoint  Caminho do endpoint IXC, ex: "/get_boleto"
     * @param  array<string, mixed>  $params  Parâmetros da requisição
     * @return string Corpo bruto da resposta
     *
     * @throws IxcRequestException Falha de transporte/HTTP
     */
    public function getRaw(string $endpoint, array $params): string;

    /**
     * Grava dados na API do IXC Soft (inserir/editar/ação). Diferente de
     * get(), o corpo é o mapa de campos direto do recurso — o IXC não usa o
     * protocolo qtype/query/oper para escrita.
     *
     * @param  string  $endpoint  Caminho do endpoint IXC, ex: "/su_oss_chamado"
     * @param  array<string, mixed>  $fields  Campos do recurso a gravar
     * @return array<string, mixed> Corpo da resposta decodificado
     *
     * @throws IxcRequestException  Falha de transporte/HTTP
     * @throws IxcResponseException Resposta com corpo que não é um JSON válido
     */
    public function post(string $endpoint, array $fields): array;
}
