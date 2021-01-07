<?php

namespace PaymentGateway\PayPalSdkMock\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use PaymentGateway\PayPalSdkMock\PayPalApiMock;
use PHPUnit\Framework\TestCase;

class CatalogProductApiTest extends TestCase
{
    /**
     * @test
     */
    public function itWorks()
    {
        $handler  = HandlerStack::create(new PayPalApiMock());
        $client = new Client(['handler' => $handler]);

        $response = $client->request('POST', 'http://api.sandbox.paypal.com/v1/catalogs/products', [
            'headers' => [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Authorization' => 'Bearer A21AAK0bqGokMIxVEU2O-x9a04BG0xX6-geO6JmogaA0J3lCHqLKhKWvLWT2NtkP1VUOuWGBsfx3PwiHwBAhwb5UN80TmM65w'
            ],
            'json' => [
                "name" => "New product Name",
                "description" => "New product description",
                "type" => "SERVICE",
                "category" => "SOFTWARE",
                "image_url" => "https://example.com/image.jpg",
                "home_url" => "https://example.com/home"
            ],
        ]);

        $json = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('id', $json);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('description', $json);
        $this->assertArrayHasKey('create_time', $json);
        $this->assertArrayHasKey('links', $json);
    }
}
