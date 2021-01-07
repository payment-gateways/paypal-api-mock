<?php

namespace PaymentGateway\PayPalSdkMock\Concerns;

use GuzzleHttp\Promise\PromiseInterface;

trait HasFixedResponse
{
    protected ?PromiseInterface $fixedResponse = null;

    public function withResponse(int $code, string $body): void
    {
        $this->fixedResponse = $this->jsonResponse($code, $body);
    }
}
