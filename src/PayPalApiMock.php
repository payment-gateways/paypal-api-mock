<?php

namespace PaymentGateway\PayPalSdkMock;

use GuzzleHttp\Promise\PromiseInterface;
use PaymentGateway\PayPalApiMock\Concerns\HasBasicAuthentication;
use PaymentGateway\PayPalApiMock\Concerns\HasBearerAuthentication;
use PaymentGateway\PayPalApiMock\Concerns\HasFixedResponse;
use PaymentGateway\PayPalSdkMock\Constants\PlanStatus;
use PaymentGateway\PayPalSdkMock\Constants\ProductType;
use PaymentGateway\PayPalSdkMock\Responses\PayPalApiResponse;
use Psr\Http\Message\RequestInterface;

class PayPalApiMock extends BaseMock
{
    use HasFixedResponse;
    use HasBasicAuthentication;
    use HasBearerAuthentication;

    private const PRODUCT_PATTERN = '/v1\/catalogs\/products\/(PROD-[0-9a-zA-Z]+)/';
    private const PLAN_PATTERN = '/v1\/billing\/plans\/(P-[0-9a-zA-Z]+)/';

    protected string $hostname = 'api.sandbox.paypal.com';

    protected $user = 'AeA1QIZXiflr1_-r0U2UbWTziOWX1GRQer5jkUq4ZfWT5qwb6qQRPq7jDtv57TL4POEEezGLdutcxnkJ';
    protected $pass = 'ECYYrrSHdKfk_Q0EdvzdGkzj58a66kKaUQ5dZAEv4HvvtDId2_DpSuYDB088BZxGuMji7G4OFUnPog6p';

    protected array $products = [];
    protected array $plans = [];

    protected ?PromiseInterface $response = null;

    public function getProduct(string $id): ?array
    {
        return $this->showProduct($id);
    }

    public function getPlan(string $id): array
    {
        return $this->showPlan($id);
    }

    public function __invoke(RequestInterface $request)
    {
        if ($this->fixedResponse) {
            return $this->fixedResponse;
        }

        if ($request->getUri()->getHost() != $this->hostname) {
            return $this->jsonResponse(400, 'Not found');
        }

        $this->response = $this->jsonResponse(400, 'Not found');

        if ($request->getUri()->getPath() === '/v1/oauth2/token') {
            if ($request->getMethod() === 'GET') {
                $this->invalidToken();
            } elseif (!$this->validateBasicAuth($request)) {
                $this->failureAuthentication();
            } elseif (empty($request->getUri()->getQuery())) {
                $this->unsupportedGrantType();
            } else {
                $this->token();
            }
        } elseif ($request->getUri()->getPath() === '/v1/catalogs/products') {
            if ($request->getMethod() === 'GET') {
                if (!$this->validateAuthToken($request)) {
                    $this->failureAuthentication();
                } else {
                    $this->productListResponse();
                }
            } elseif ($request->getMethod() === 'POST') {
                if (!$this->validateAuthToken($request)) {
                    $this->failureAuthentication();
                } else {
                    $json = $this->parseArray(json_decode($request->getBody()->getContents()));
                    $this->createProduct($json);
                }
            } else {
                $this->response = $this->response(404, '', [], 'Not Found');
            }
        } elseif (preg_match(self::PRODUCT_PATTERN, $request->getUri()->getPath(), $matches)) {
            if ($request->getMethod() === 'GET') {
                $id = $matches[1];

                if (!in_array($id, array_column($this->products, 'id'))) {
                    $this->response = $this->response(
                        404,
                        PayPalApiResponse::resourceNotFound('product id'),
                        [],
                        'Not Found'
                    );
                } else {
                    $this->showProductResponse($id);
                }
            } elseif ($request->getMethod() === 'PATCH') {
                $id = $matches[1];

                if (!in_array($id, array_column($this->products, 'id'))) {
                    $this->response = $this->response(404, '', [], 'Not Found');
                } else {
                    $json = $this->parseArray(json_decode($request->getBody()->getContents()));

                    $ids = array_column($this->products, 'id');
                    $position = $this->arrayPos($ids, $id);

                    if ($json) {
                        foreach ($json as $change) {
                            $field = str_replace('/', '', $change['path']);

                            if ($change['op'] === 'add') {
                                $this->products[$position][$field] = $this->products[$id][$field] . $change['value'];
                            } elseif ($change['op'] === 'replace') {
                                $this->products[$position][$field] = $change['value'];
                            } elseif ($change['op'] === 'remove') {
                                unset($this->products[$position][$field]);
                            }
                        }
                    }

                    $this->response = $this->response(204, '', [], 'No Content');
                }
            } else {
                $this->response = $this->response(405, '', [], 'Method Not Allowed');
            }
        } elseif ($request->getUri()->getPath() === '/v1/billing/plans') {
            if ($request->getMethod() === 'GET') {
                if (!$this->validateAuthToken($request)) {
                    $this->failureAuthentication();
                } else {
                    $this->planListResponse();
                }
            } elseif ($request->getMethod() === 'POST') {
                if (!$this->validateAuthToken($request)) {
                    $this->failureAuthentication();
                } else {
                    $json = $this->parseArray(json_decode($request->getBody()->getContents()));
                    $this->createPlan($json);
                }
            } else {
                $this->response = $this->response(405, '', [], 'Method Not Allowed');
            }
        } elseif (preg_match(self::PLAN_PATTERN, $request->getUri()->getPath(), $matches)) {
            if ($request->getMethod() === 'GET') {
                $id = $matches[1];

                if (!in_array($id, array_column($this->plans, 'id'))) {
                    $this->response = $this->response(
                        404,
                        PayPalApiResponse::resourceNotFound('planId'),
                        [],
                        'Not Found'
                    );
                } else {
                    $this->showPlanResponse($id);
                }
            } elseif ($request->getMethod() === 'PATCH') {
                $id = $matches[1];

                if (!in_array($id, array_column($this->plans, 'id'))) {
                    $this->response = $this->response(
                        404,
                        PayPalApiResponse::resourceNotFound('planId'),
                        [],
                        'Not Found'
                    );
                } else {
                    $json = $this->parseArray(json_decode($request->getBody()->getContents()));

                    $ids = array_column($this->plans, 'id');
                    $position = $this->arrayPos($ids, $id);

                    foreach ($json as $change) {
                        $field = $change['path'];

                        if ($change['op'] === 'replace') {
                            $plan = &$this->plans[$position];

                            if ($field === '/description') {
                                $this->plans[$position][$field] = $change['value'];
                            } elseif ($field === '/payment_preferences/auto_bill_outstanding') {
                                $plan['payment_preferences']['auto_bill_outstanding'] = $change['value'];
                            } elseif ($field === '/payment_preferences/payment_failure_threshold') {
                                $plan['payment_preferences']['payment_failure_threshold'] = $change['value'];
                            } elseif ($field === '/payment_preferences/setup_fee') {
                                $originalCurrency = $plan['payment_preferences']['setup_fee']['currency_code'];
                                $newCurrency = $change['value']['currency_code'];
                                if ($originalCurrency !== $newCurrency) {
                                    return $this->jsonResponse(
                                        422,
                                        PayPalApiResponse::unprocessableEntityForCurrencyMismatch(),
                                        [],
                                        'Unprocessable Entity'
                                    );
                                } else {
                                    $plan['payment_preferences']['setup_fee'] = $change['value'];
                                }
                            } elseif ($field === '/payment_preferences/setup_fee_failure_action') {
                                $plan['payment_preferences']['setup_fee_failure_action'] = $change['value'];
                            } else {
                                return $this->jsonResponse(
                                    400,
                                    PayPalApiResponse::invalidPatchPath($change['value']),
                                    [],
                                    'Bad Request'
                                );
                            }
                        } elseif (in_array($change['op'], ['add', 'remove', 'copy', 'move', 'test'])) {
                            return $this->response(
                                422,
                                PayPalApiResponse::unprocessableEntityForOperation($change['op']),
                                [],
                                'Unprocessable Entity'
                            );
                        } else {
                            return $this->jsonResponse(
                                400,
                                PayPalApiResponse::malformedRequestJson('0/op'),
                                [],
                                'Bad Request'
                            );
                        }
                    }

                    $this->response = $this->response(204, '', [], 'No Content');
                }
            } elseif (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'])) {
                $this->response = $this->response(404, '', [], 'Not Found');
            } else {
                $this->response = $this->response(405, '', [], 'Method Not Allowed');
            }
        }

        return $this->response;
    }

    /**
     * @param $obj
     * @return array|object
     */
    private function parseArray($obj)
    {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }

        if (is_array($obj)) {
            $new = array();

            foreach ($obj as $key => $val) {
                $new[$key] = $this->parseArray($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }

    private function arrayPos(array $array, $element)
    {
        foreach ($array as $key => $value) {
            if ($value === $element) {
                return $key;
            }
        }

        return false;
    }

    private function token(): void
    {
        $this->response = $this->jsonResponse(200, PayPalApiResponse::token(), [], 'OK');
    }

    private function invalidToken(): void
    {
        $this->response = $this->jsonResponse(401, PayPalApiResponse::invalidToken(), [], 'OK');
    }

    private function failureAuthentication(): void
    {
        $this->response = $this->jsonResponse(401, PayPalApiResponse::failureAuthentication(), [], 'OK');
    }

    private function unsupportedGrantType(): void
    {
        $this->response = $this->jsonResponse(401, PayPalApiResponse::missingGrantType(), [], 'OK');
    }

    private function createProduct(array $request): void
    {
        if (!isset($request['name'])) {
            $this->response = $this->jsonResponse(400, PayPalApiResponse::missingRequiredParameter('name'));
            return;
        }

        if (!isset($request['type'])) {
            $request['type'] = ProductType::PHYSICAL;
        }

        $product = PayPalApiResponse::productCreated($request);
        $this->products[] = $product;

        $this->response = $this->jsonResponse(201, $product, [], 'OK');
    }

    private function productList(): array
    {
        $list = ['products' => []];

        foreach ($this->products as $product) {
            unset($product['type']);
            $list['products'][] = $product;
        }

        return $list;
    }

    private function productListResponse(): void
    {
        $this->response = $this->jsonResponse(200, $this->productList(), [], 'OK');
    }

    private function showProduct(string $id): array
    {
        $ids = array_column($this->products, 'id');
        $position = $this->arrayPos($ids, $id);

        return $this->products[$position];
    }

    private function showProductResponse(string $id): void
    {
        $this->response = $this->jsonResponse(200, $this->showProduct($id), [], 'OK');
    }

    private function createPlan(array $request): void
    {
        if (!isset($request['name'])) {
            $this->response = $this->jsonResponse(
                400,
                PayPalApiResponse::missingRequiredParameter('name'),
                [],
                'Bad Request'
            );
            return;
        }

        if (!isset($request['payment_preferences'])) {
            $this->response = $this->jsonResponse(
                400,
                PayPalApiResponse::missingRequiredParameter('payment_preferences'),
                [],
                'Bad Request'
            );
            return;
        }

        if (!isset($request['status'])) {
            $request['status'] = PlanStatus::ACTIVE;
        }

        $request['id'] = 'P-' . substr(bin2hex(uniqid()), 0, 24);
        $request['usage_type'] = 'LICENSED';
        $request['create_time'] = '2020-12-17T03:44:39Z';

        $request['links'] = [
            [
                'href' =>  'https://api.sandbox.paypal.com/v1/billing/plans/' . $request['id'],
                'rel' =>  'self',
                'method' =>  'GET',
                'encType' =>  'application/json'
            ],
            [
                'href' =>  'https://api.sandbox.paypal.com/v1/billing/plans/' . $request['id'],
                'rel' =>  'edit',
                'method' =>  'PATCH',
                'encType' =>  'application/json'
            ],
            [
                'href' =>  'https://api.sandbox.paypal.com/v1/billing/plans/' . $request['id'],
                'rel' =>  'self',
                'method' =>  'POST',
                'encType' =>  'application/json'
            ]
        ];

        $this->plans[] = $request;
        $plan = PayPalApiResponse::planCreated($request);

        $this->response = $this->jsonResponse(201, $plan, [], 'Created');
    }

    private function planList(): array
    {
        $list = ['plans' => []];

        foreach ($this->plans as $plan) {
            unset($plan['product_id']);
            $plan['links'] = $plan['links'][0];
            $list['plans'][] = $plan;
        }

        return $list;
    }

    private function planListResponse(): void
    {
        $this->response = $this->jsonResponse(200, $this->planList(), [], 'OK');
    }

    private function showPlan(string $id): array
    {
        $ids = array_column($this->plans, 'id');
        $position = $this->arrayPos($ids, $id);

        return $this->plans[$position];
    }

    private function showPlanResponse(string $id): void
    {
        $this->response = $this->jsonResponse(200, $this->showPlan($id), [], 'OK');
    }
}
