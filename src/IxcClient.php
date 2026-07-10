<?php

namespace RedRodrigo\IxcSdk;

use RedRodrigo\IxcSdk\Contracts\HttpClientInterface;
use RedRodrigo\IxcSdk\Http\GuzzleHttpClient;
use RedRodrigo\IxcSdk\Resources\ClienteResource;
use RedRodrigo\IxcSdk\Resources\ComercialResource;
use RedRodrigo\IxcSdk\Resources\ContratoResource;
use RedRodrigo\IxcSdk\Resources\EstoqueResource;
use RedRodrigo\IxcSdk\Resources\FinanceiroResource;
use RedRodrigo\IxcSdk\Resources\OsResource;
use RedRodrigo\IxcSdk\Resources\VeiculoResource;

/**
 * Ponto de entrada da SDK. Dá acesso lazy aos resources de domínio,
 * todos compartilhando a mesma implementação de HttpClientInterface.
 *
 * @example Uso direto (fora do Laravel)
 * $ixc = IxcClient::make('https://central.example.com.br/webservice/v1', '129', 'token-secreto');
 * $clientes = $ixc->cliente()->searchCliente('João');
 *
 * @example Injetando um transporte customizado (ex: com cache)
 * $http = new CachingHttpClient(new GuzzleHttpClient($baseUrl, $userId, $token), $psr16Cache);
 * $ixc = new IxcClient($http);
 */
final class IxcClient
{
    private ?ClienteResource $cliente = null;

    private ?ComercialResource $comercial = null;

    private ?ContratoResource $contrato = null;

    private ?EstoqueResource $estoque = null;

    private ?FinanceiroResource $financeiro = null;

    private ?OsResource $os = null;

    private ?VeiculoResource $veiculo = null;

    public function __construct(private readonly HttpClientInterface $http)
    {
    }

    public static function make(string $baseUrl, string $userId, string $token): self
    {
        return new self(new GuzzleHttpClient($baseUrl, $userId, $token));
    }

    public function cliente(): ClienteResource
    {
        return $this->cliente ??= new ClienteResource($this->http);
    }

    public function comercial(): ComercialResource
    {
        return $this->comercial ??= new ComercialResource($this->http);
    }

    public function contrato(): ContratoResource
    {
        return $this->contrato ??= new ContratoResource($this->http);
    }

    public function estoque(): EstoqueResource
    {
        return $this->estoque ??= new EstoqueResource($this->http);
    }

    public function financeiro(): FinanceiroResource
    {
        return $this->financeiro ??= new FinanceiroResource($this->http);
    }

    public function os(): OsResource
    {
        return $this->os ??= new OsResource($this->http);
    }

    public function veiculo(): VeiculoResource
    {
        return $this->veiculo ??= new VeiculoResource($this->http);
    }
}
