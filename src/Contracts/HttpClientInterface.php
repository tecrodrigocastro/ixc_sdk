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
}
