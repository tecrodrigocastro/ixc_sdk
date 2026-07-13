<?php

namespace RedRodrigo\IxcSdk\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;
use RedRodrigo\IxcSdk\Resources\FinanceiroResource;
use RedRodrigo\IxcSdk\Tests\Support\FakeHttpClient;

final class FinanceiroResourceTest extends TestCase
{
    public function test_get_invoices_open_by_id_returns_first_record_or_empty_array(): void
    {
        $http = new FakeHttpClient(['registros' => [['id' => 1, 'valor' => '99.90']]]);
        $resource = new FinanceiroResource($http);

        $this->assertSame(['id' => 1, 'valor' => '99.90'], $resource->getInvoicesOpenById(12345));

        $http->response = ['registros' => []];
        $this->assertSame([], $resource->getInvoicesOpenById(999));
    }

    public function test_get_all_invoices_open_filters_by_current_month(): void
    {
        $http = new FakeHttpClient(['registros' => [['id' => 1]]]);
        $resource = new FinanceiroResource($http);

        $result = $resource->getAllInvoicesOpen();

        $this->assertSame([['id' => 1]], $result);
        $filters = $http->lastGridParam();
        $this->assertSame('fn_areceber.data_vencimento', $filters[3]['TB']);
        $this->assertSame(date('Y-m-01'), $filters[3]['P']);
        $this->assertSame(date('Y-m-t'), $filters[4]['P']);
    }

    public function test_get_clientes_com_fatura_vencida_uses_days_late(): void
    {
        $http = new FakeHttpClient(['registros' => []]);
        $resource = new FinanceiroResource($http);

        $resource->getClientesComFaturaVencida(5);

        $filters = $http->lastGridParam();
        $this->assertSame(date('Y-m-d', strtotime('-5 days')), $filters[3]['P']);
    }

    public function test_get_clientes_com_fatura_a_vencer_uses_days_ahead(): void
    {
        $http = new FakeHttpClient(['registros' => []]);
        $resource = new FinanceiroResource($http);

        $resource->getClientesComFaturaAVencer(7);

        $filters = $http->lastGridParam();
        $this->assertSame(date('Y-m-d'), $filters[3]['P']);
        $this->assertSame(date('Y-m-d', strtotime('+7 days')), $filters[4]['P']);
    }

    public function test_get_pix_copia_cola_extracts_nested_field(): void
    {
        $http = new FakeHttpClient([
            'pix' => ['dadosPix' => ['pixCopiaECola' => '00020126...']],
        ]);
        $resource = new FinanceiroResource($http);

        $this->assertSame('00020126...', $resource->getPixCopiaCola(708492));
        $this->assertSame('/get_pix', $http->lastEndpoint);
        $this->assertSame(708492, $http->lastParams['id_areceber']);
    }

    public function test_get_pix_copia_cola_returns_null_when_missing(): void
    {
        $http = new FakeHttpClient(['pix' => null]);
        $resource = new FinanceiroResource($http);

        $this->assertNull($resource->getPixCopiaCola(1));
    }

    public function test_get_boletos_by_cliente_returns_raw_bytes(): void
    {
        $http = new FakeHttpClient();
        $http->rawResponse = '%PDF-1.4 boleto bytes';
        $resource = new FinanceiroResource($http);

        $result = $resource->getBoletosByCliente(708492);

        $this->assertSame('%PDF-1.4 boleto bytes', $result);
        $this->assertSame('/get_boleto', $http->lastEndpoint);
        $this->assertSame(708492, $http->lastParams['boletos']);
        $this->assertSame('arquivo', $http->lastParams['tipo_boleto']);
    }
}
