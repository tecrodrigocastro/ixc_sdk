# ixc-sdk

SDK PHP não-oficial para a API REST do [IXC Soft](https://wikiixcsoft.ixcsoft.com.br/) (ERP de provedores de internet).

Núcleo sem dependência de framework (`guzzlehttp/guzzle` + `psr/simple-cache` apenas), com um adapter opcional para Laravel.

Extraído do projeto `gerencia-orbe`, onde a integração com o IXC vinha crescendo repetida em vários repositórios/controllers específicos daquela aplicação.

## Instalação

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
| `financeiro()` | Pagamentos, faturas, desbloqueio de confiança | `/fn_areceber`, `/desbloqueio_confianca` |
| `os()` | Ordens de serviço, assuntos, diagnósticos, materiais | `/su_oss_chamado`, `/su_oss_assunto`, `/su_diagnostico`, `/movimento_produtos`, `/produtos`, `/radpop_radio` |
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

## Decisões de design (vindas da extração do gerencia-orbe)

- **Sem swallow de erros**: os repositórios originais às vezes engoliam exceptions em `try/catch` retornando `[]`/`null` silenciosamente. A SDK propaga exceptions tipadas — quem chama decide como tratar.
- **Cache centralizado**: os `Cache::remember()` que antes viviam espalhados e duplicados em métodos individuais (com TTLs diferentes e chaves manuais) viraram um único decorator plugável (`CachingHttpClient`), com TTL único configurável.
- **JSON-decode centralizado**: o `json_decode($response->getBody()->getContents(), true)` repetido em todo método de repositório agora vive em um único lugar (`GuzzleHttpClient::get()`).
- **Retornos preservados**: cada método de resource mantém a mesma assinatura e formato de retorno dos repositórios originais (`array`, às vezes lista, às vezes só o primeiro registro, às vezes a estrutura bruta `{total, registros}`) — isso é intencional para manter compatibilidade com o código já existente no `gerencia-orbe` ao trocar a implementação depois. Para um formato normalizado e tipado, use `RedRodrigo\IxcSdk\Support\ListResponse` (usado internamente, também exposto publicamente).
- **Não portado**: `IxcClientService::postRequestDraWhats()` (integração com WhatsApp da DRA Telecom, acoplada a um Eloquent Model local) e o repositório legado `App\Repositories\IxcRepositoryInterface`/`Impl` (usado em Commands/Jobs/Livewire do gerencia-orbe) ficaram de fora desta extração — não são específicos do IXC ou têm acoplamento amplo demais para uma primeira versão da SDK.

## Testes

```bash
composer install
vendor/bin/phpunit
```

## Contexto (gerencia-orbe)

Este pacote nasceu de `app/Repositories/Ixc/*` + `app/Services/IxcClientService.php` no projeto `gerencia-orbe`. Para migrar aquele projeto para usar esta SDK: trocar os bindings de `IxcClienteRepositoryInterface` (e demais) no `AppServiceProvider` pelos resources equivalentes aqui (`ClienteResource`, `ComercialResource`, etc.), ou manter as interfaces locais como fachada fina delegando para a SDK.
