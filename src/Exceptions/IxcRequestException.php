<?php

namespace RedRodrigo\IxcSdk\Exceptions;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Throwable;

/**
 * Falha de transporte HTTP (timeout, DNS, conexão recusada, status de erro, etc.)
 * ao chamar a API do IXC Soft.
 */
class IxcRequestException extends IxcException
{
    public static function fromGuzzleException(GuzzleException $exception): self
    {
        $statusCode = null;

        if ($exception instanceof RequestException && $exception->hasResponse()) {
            $statusCode = $exception->getResponse()?->getStatusCode();
        }

        $message = $statusCode !== null
            ? "Falha ao chamar a API do IXC Soft (HTTP {$statusCode}): {$exception->getMessage()}"
            : "Falha ao chamar a API do IXC Soft: {$exception->getMessage()}";

        return new self($message, $statusCode ?? 0, $exception);
    }

    public function __construct(string $message, private readonly int $statusCode = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode !== 0 ? $this->statusCode : null;
    }
}
