<?php

namespace PaymentGateway\PayPalSdkMock\Concerns;

use Psr\Http\Message\RequestInterface;

trait HasBasicAuthentication
{
    protected $user = 'AeA1QIZXiflr1_-r0U2UbWTziOWX1GRQer5jkUq4ZfWT5qwb6qQRPq7jDtv57TL4POEEezGLdutcxnkJ';
    protected $pass = 'ECYYrrSHdKfk_Q0EdvzdGkzj58a66kKaUQ5dZAEv4HvvtDId2_DpSuYDB088BZxGuMji7G4OFUnPog6p';

    protected function validateBasicAuth(RequestInterface $request): bool
    {
        $authorization = $request->getHeader('Authorization');
        $authorization = array_shift($authorization);

        return $this->validateCredentials(explode(' ', $authorization));
    }

    protected function validateCredentials(array $token): bool
    {
        $type = array_shift($token);

        if (! $token || $type !== 'Basic') {
            return false;
        }

        $value = array_shift($token);

        return $value === $this->buildAuthValue();
    }

    private function buildAuthValue(): string
    {
        return base64_encode($this->user . ':' . $this->pass);
    }
}
