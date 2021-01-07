<?php

namespace PaymentGateway\PayPalSdkMock\Concerns;

use Psr\Http\Message\RequestInterface;

trait HasBearerAuthentication
{
    protected array $tokens = [
        'A21AAK0bqGokMIxVEU2O-x9a04BG0xX6-geO6JmogaA0J3lCHqLKhKWvLWT2NtkP1VUOuWGBsfx3PwiHwBAhwb5UN80TmM65w'
    ];

    protected function isAuthHeaderEmpty(RequestInterface $request): bool
    {
        return empty($request->getHeader('Authorization')) || is_null($request->getHeader('Authorization'));
    }

    protected function validateAuthToken(RequestInterface $request): bool
    {
        if ($this->isAuthHeaderEmpty($request)) {
            return false;
        }

        $authorization = $request->getHeader('Authorization');
        $authorization = array_shift($authorization);

        $token = trim(strstr($authorization, ' '));

        return $this->validateToken($token);
    }

    protected function validateToken(string $token): bool
    {
        return in_array($token, $this->tokens);
    }
}
