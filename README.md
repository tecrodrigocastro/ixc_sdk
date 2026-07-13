# ixc-sdk

[![Latest Version on Packagist](https://img.shields.io/packagist/v/redrodrigo/ixc-sdk.svg)](https://packagist.org/packages/redrodrigo/ixc-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/redrodrigo/ixc-sdk.svg)](https://packagist.org/packages/redrodrigo/ixc-sdk)
[![License](https://img.shields.io/packagist/l/redrodrigo/ixc-sdk.svg)](https://github.com/tecrodrigocastro/ixc_sdk/blob/main/LICENSE)

SDK PHP não-oficial para a API REST do [IXC Soft](https://wikiixcsoft.ixcsoft.com.br/) (ERP de provedores de internet).

Núcleo sem dependência de framework (`guzzlehttp/guzzle` + `psr/simple-cache` apenas), com um adapter opcional para Laravel.

## Instalação

Disponível no [Packagist](https://packagist.org/packages/redrodrigo/ixc-sdk) — instale via Composer:

```bash
composer require redrodrigo/ixc-sdk
```

## Uso standalone (fora do Laravel)

```php
use RedRodrigo\IxcSdk\IxcClient;

$ixc = IxcClient::make(
    baseUrl: 'https://central.suaoperadora.com.br/webservice/v1',
    userId: '129',
    token: 'seu-token-de-api',
);

$clientes = $ixc->cliente()->searchCliente('João');
$contratos = $ixc->cliente()->getInfoContrato(10827);
$vendedor = $ixc->comercial()->getVendedorById('5');
$os = $ixc->os()->getOsAbertasByTecnico('15');
```

## Uso no Laravel

O provider é descoberto automaticamente via `composer.json extra.laravel.providers`.

1. Publique o config (opcional):

   ```bash
   php artisan vendor:publish --tag=ixc-config
   ```

2. Configure o `.env`:

   ```env
   IXC_BASE_URL=https://central.suaoperadora.com.br/webservice/v1
   IXC_USER_ID=129
   IXC_API_TOKEN=seu-token-de-api

   # Opcional — cacheia respostas via o cache store padrão do Laravel
   IXC_CACHE_ENABLED=false
   IXC_CACHE_STORE=
   IXC_CACHE_TTL=300
   ```

3. Injete `RedRodrigo\IxcSdk\IxcClient` (singleton) onde precisar:

   ```php
   use RedRodrigo\IxcSdk\IxcClient;

   class ClienteController
   {
       public function __construct(private IxcClient $ixc) {}

       public function show(string $termo)
       {
           return $this->ixc->cliente()->searchCliente($termo);
       }
   }
   ```

   Ou injete um resource específico diretamente (também resolvido pelo container):

   ```php
   use RedRodrigo\IxcSdk\Resources\ComercialResource;

   public function __construct(private ComercialResource $comercial) {}
   ```

## Resources disponíveis

| Resource | Domínio | Endpoints IXC |
|---|---|---|
| `cliente()` | Clientes, contratos, login PPPoE | `/cliente`, `/cliente_contrato`, `/radusuarios` |
| `comercial()` | Contratos por período, vendedores, equipes, OS por tipo | `/cliente_contrato`, `/vd_contratos`, `/vendedor`, `/usuarios`, `/su_oss_chamado` |
| `contrato()` | Planos de venda, TV | `/vd_contratos`, `/tv_usuarios` |
| `estoque()` | Almoxarifado, saldo, produtos | `/almox_usuario`, `/view_prod_estoque_almox`, `/produtos` |
| `financeiro()` | Pagamentos, faturas, PIX, boletos, desbloqueio de confiança | `/fn_areceber`, `/desbloqueio_confianca`, `/get_pix`, `/get_boleto` |
| `funcionario()` | Funcionários ativos | `/funcionarios` |
| `os()` | Ordens de serviço, assuntos, diagnósticos, materiais | `/su_oss_chamado`, `/su_oss_assunto`, `/su_diagnostico`, `/movimento_produtos`, `/produtos`, `/radpop_radio` |
| `ticket()` | Tickets/protocolos de atendimento | `/su_ticket` |
| `veiculo()` | Frota e despesas | `/veiculos`, `/veiculos_despesas` |

Cada método é documentado com PHPDoc (campos relevantes de retorno, exemplo de uso) diretamente na classe do resource correspondente em `src/Resources/`.

## QueryBuilder

Todos os resources usam `RedRodrigo\IxcSdk\Query\QueryBuilder` internamente para montar o protocolo de listagem do IXC (`qtype`/`query`/`oper`/`page`/`rp`/`sortname`/`sortorder`/`grid_param`). Ele também está disponível publicamente para consultas ad-hoc:

```php
use RedRodrigo\IxcSdk\Query\QueryBuilder;

$query = QueryBuilder::for('cliente_contrato.id_vendedor')
    ->perPage(400)
    ->filter('cliente_contrato.status', '=', 'A')
    ->filter('cliente_contrato.data_ativacao', '>=', '2024-07-01')
    ->filter('cliente_contrato.data_ativacao', '<=', '2024-07-31')
    ->toArray();
```

## Cache

O núcleo não depende de nenhum cache específico. `RedRodrigo\IxcSdk\Http\CachingHttpClient` é um decorator PSR-16 que pode envolver qualquer `HttpClientInterface`:

```php
use RedRodrigo\IxcSdk\Http\CachingHttpClient;
use RedRodrigo\IxcSdk\Http\GuzzleHttpClient;
use RedRodrigo\IxcSdk\IxcClient;

$http = new CachingHttpClient(
    inner: new GuzzleHttpClient($baseUrl, $userId, $token),
    cache: $psr16Cache, // qualquer implementação de Psr\SimpleCache\CacheInterface
    ttl: 300,
);

$ixc = new IxcClient($http);
```

No Laravel, isso é habilitado automaticamente via `IXC_CACHE_ENABLED=true` (usa `Cache::store()`, que já implementa PSR-16).

## Respostas binárias (PDF de boleto)

Endpoints que devolvem arquivos (não JSON), como o boleto em PDF, usam `HttpClientInterface::getRaw()` em vez de `get()` — o corpo da resposta não é decodificado:

```php
$pdfBytes = $ixc->financeiro()->getBoletosByCliente(708492);

file_put_contents('boleto.pdf', $pdfBytes);
```

`CachingHttpClient::getRaw()` nunca cacheia — documentos financeiros costumam ser recalculados a cada emissão.

## Erros

Toda falha de transporte (timeout, DNS, HTTP de erro) lança `RedRodrigo\IxcSdk\Exceptions\IxcRequestException`. Uma resposta com corpo que não é JSON válido lança `IxcResponseException`. Ambas estendem `IxcException` — capture essa classe se quiser tratar qualquer erro da SDK de forma genérica.

```php
use RedRodrigo\IxcSdk\Exceptions\IxcException;

try {
    $ixc->cliente()->searchCliente('123');
} catch (IxcException $e) {
    // logar, notificar, etc.
}
```

## Decisões de design

- **Sem swallow de erros**: falhas de transporte e respostas inválidas lançam exceptions tipadas (`IxcRequestException`, `IxcResponseException`) em vez de retornar `[]`/`null` silenciosamente. Quem chama decide como tratar.
- **Cache centralizado**: em vez de cada método decidir se e como cachear (TTLs diferentes, chaves manuais), o cache é um único decorator plugável (`CachingHttpClient`) que pode envolver qualquer implementação de `HttpClientInterface`.
- **JSON-decode centralizado**: a decodificação da resposta HTTP acontece em um único lugar (`GuzzleHttpClient::get()`), não repetida em cada método de resource.
- **Retornos pragmáticos por padrão**: cada método de resource retorna o formato mais natural para aquele endpoint (`array`, lista, só o primeiro registro, ou a estrutura bruta `{total, registros}` do IXC) em vez de forçar um único formato genérico. Para um formato normalizado e tipado, use `RedRodrigo\IxcSdk\Support\ListResponse` (usado internamente, também exposto publicamente).

## Testes

```bash
composer install
vendor/bin/phpunit
```
