<?php

namespace PaymentGateway\PayPalApiMock\Responses;

use PaymentGateway\PayPalApiMock\Constants\PlanStatus;

class PayPalApiResponse
{
    public static function token(): array
    {
        return [
            'scope' => 'https://uri.paypal.com/services/invoicing https://uri.paypal.com/services/disputes/read-buyer https://uri.paypal.com/services/payments/realtimepayment https://uri.paypal.com/services/disputes/update-seller https://uri.paypal.com/services/payments/payment/authcapture openid https://uri.paypal.com/services/disputes/read-seller https://uri.paypal.com/services/payments/refund https://api.paypal.com/v1/vault/credit-card https://api.paypal.com/v1/payments/.* https://uri.paypal.com/payments/payouts https://api.paypal.com/v1/vault/credit-card/.* https://uri.paypal.com/services/subscriptions https://uri.paypal.com/services/applications/webhooks', // phpcs:ignore
            'access_token' => 'A21AAK0bqGokMIxVEU2O-x9a04BG0xX6-geO6JmogaA0J3lCHqLKhKWvLWT2NtkP1VUOuWGBsfx3PwiHwBAhwb5UN80TmM65w', // phpcs:ignore
            'token_type' => 'Bearer',
            'app_id' => 'APP-80W284485P519543T',
            'expires_in' => 32358,
            'nonce' => '2020-12-01T00:49:57ZSvHY0k14KHSXBV-6Al4jAhQ-_e5wPsZdAfJuneW911U',
        ];
    }

    public static function failureAuthentication(): array
    {
        return [
            'name' => 'AUTHENTICATION_FAILURE',
            'message' => 'Authentication failed due to invalid authentication credentials or a missing Authorization header.', // phpcs:ignore
            'links' => [
                [
                    'href' => 'https://developer.paypal.com/docs/api/overview/#error',
                    'rel' => 'information_link'
                ]
            ]
        ];
    }

    public static function missingGrantType(): array
    {
        return [
            'error' => 'unsupported_grant_type',
            'error_description' => 'Grant Type is NULL',
        ];
    }

    public static function unsupportedGrantType(): array
    {
        return [
            'error' => 'unsupported_grant_type',
            'error_description' => 'unsupported grant_type',
        ];
    }

    public static function invalidToken(): array
    {
        return [
            'error' => 'invalid_token',
            'error_description' => 'Authorization header does not have valid access token',
        ];
    }

    /**
     * @param string $issue
     * @param string $description
     * @param string $field
     * @param array|string|null $value
     * @param array|null $links
     * @return array
     */
    private static function invalidRequest(
        string $issue,
        string $description,
        string $field,
        $value = null,
        array $links = null
    ): array {
        if (is_null($links)) {
            $links = [
                [
                    'href' => 'https://developer.paypal.com/docs/api/v1/billing/subscriptions#INVALID_REQUEST',
                    'rel' => 'information_link',
                    'method' => 'GET'
                ]
            ];
        }

        $response = [
            'name' => 'INVALID_REQUEST',
            'message' => 'Request is not well-formed, syntactically incorrect, or violates schema.',
            'debug_id' => uniqid(),
            'details' => [
                [
                    'field' => "/$field",
                    'location' => 'body',
                    'issue' => $issue,
                    'description' => $description
                ]
            ],
            'links' => $links
        ];

        if ($value) {
            $response['details']['value'] = $value;
        }

        return $response;
    }

    public static function missingRequiredParameter(string $name): array
    {
        return self::invalidRequest('MISSING_REQUIRED_PARAMETER', 'A required field is missing.', $name);
    }

    public static function malformedRequestJson(string $name): array
    {
        return self::invalidRequest('MALFORMED_REQUEST_JSON', 'The request JSON is not well formed.', $name, null, []);
    }

    /**
     * @param string|array $value
     * @return array
     */
    public static function invalidPatchPath($value): array
    {
        return self::invalidRequest('INVALID_PATCH_PATH', 'The specified field cannot be patched.', '0/path', $value);
    }

    public static function resourceNotFound($identifier): array
    {
        return [
            'name' => 'RESOURCE_NOT_FOUND',
            'message' => 'The specified resource does not exist.',
            'debug_id' => uniqid(),
            'details' => [
                [
                    'issue' => 'INVALID_RESOURCE_ID',
                    'description' => 'Invalid ' . $identifier
                ]
            ],
            'links' => [
                [
                    'href' => 'https://developer.paypal.com/docs/api/v1/billing/subscriptions#RESOURCE_NOT_FOUND',
                    'rel' => 'information_link',
                    'method' => 'GET'
                ]
            ]
        ];
    }

    private static function unprocessableEntity(
        string $issue,
        string $description,
        string $field = '',
        $value = null
    ): array {
        $response = [
            'name' => 'UNPROCESSABLE_ENTITY',
            'message' => 'The requested action could not be performed, semantically incorrect, or failed business validation.', // phpcs:ignore
            'debug_id' => uniqid(),
            'details' => [
                [
                    'field' => "/$field",
                    'location' => 'body',
                    'issue' => $issue,
                    'description' => $description
                ]
            ],
            'links' => [
                [
                    'href' => 'https://developer.paypal.com/docs/api/v1/billing/subscriptions#UNPROCESSABLE_ENTITY',
                    'rel' => 'information_link',
                    'method' => 'GET'
                ]
            ]
        ];

        if ($value) {
            $response['details']['value'] = $value;
        }

        return $response;
    }

    public static function unprocessableEntityForOperation(string $value): array
    {
        return self::unprocessableEntity(
            'UNSUPPORTED_PATCH_OPERATION',
            'The specified patch operation not supported for this field.',
            '0/op',
            $value
        );
    }

    public static function unprocessableEntityForCurrencyMismatch(): array
    {
        return self::unprocessableEntity(
            'CURRENCY_MISMATCH',
            'The currency code across plan mismatch'
        );
    }

    public static function productCreated(array $request): array
    {
        $id = 'PROD-XY' . substr(bin2hex(uniqid()), 0, 15);

        $product = [
            'id' => $id,
            'name' => $request['name'],
            'type' => $request['type'],
            'create_time' => '2020-01-10T21:20:49Z',
            'update_time' => '2020-01-10T21:20:49Z',
            'links' => [
                [
                    'href' => 'https://api.paypal.com/v1/catalogs/products/' . $id,
                    'rel' => 'self',
                    'method' => 'GET'
                ],
                [
                    'href' => 'https://api.paypal.com/v1/catalogs/products/' . $id,
                    'rel' => 'edit',
                    'method' => 'PATCH'
                ]
            ]
        ];

        if (isset($request['description'])) {
            $product['description'] = $request['description'];
        }

        if (isset($request['category'])) {
            $product['category'] = $request['category'];
        }

        if (isset($request['image_url'])) {
            $product['image_url'] = $request['image_url'];
        }

        if (isset($request['home_url'])) {
            $product['home_url'] = $request['home_url'];
        }

        return $product;
    }

    public static function planCreated(array $request): array
    {
        $plan = [
            'id' => $request['id'],
            'product_id' => $request['product_id'],
            'name' => $request['name'],
            'status' => $request['status'] ?? PlanStatus::ACTIVE,
            'usage_type' => $request['usage_type'],
            'create_time' => $request['create_time'],
            'links' => $request['links'],
        ];

        if (isset($request['description'])) {
            $plan['description'] = $request['description'];
        }

        return $plan;
    }
}
