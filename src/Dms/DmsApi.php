<?php

namespace HDW\ProjectDmsImporter\Dms;

use HDW\ProjectDmsImporter\Dms\DmsProduct;
use HDW\ProjectDmsImporter\Contracts\ErpApiContract;
use HDW\ProjectDmsImporter\Contracts\ProductContract;

class DmsApi implements ErpApiContract
{

    /**
     * @var Singleton
     */
    private static $instance;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    public function __wakeup() // private
    {
    }

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Get products
     *
     * @return array
     **/
    public function getProducts(): array
    {
        $client = new \GuzzleHttp\Client();
        $collection = [];
        $headers = [
            'Authorization' => 'Bearer ' . getDMSApiToken(),
            'Accept'        => 'application/json',
        ];

        $res = $client->request(
            'GET',
            getDmsProductsEndpoint(), 
            [
                'headers' => $headers
            ]
        );

        if (200 != $res->getStatusCode()) {
            return $collection;
        }

        $collection = json_decode($res->getBody()->getContents());

        return $collection;
    }

    /**
     * Get products
     *
     * @param string $id ERP product id
     *
     * @return DmsProduct
     **/
    public function getProduct(string $id): ?\stdClass
    {
        $client = new \GuzzleHttp\Client();
        $res = $client->request(
            'GET',
            getDmsProductEndpoint($id),
            [
                'auth' => [getDmsRestUser(), getDmsRestPassword()]
            ]
        );

        if (200 != $res->getStatusCode()) {
            return null;
        }

        return json_decode($res->getBody()->getContents())->response[0];
    }

    /**
     * Get products
     *
     * @param string $id ERP product id
     *
     * @return DmsProduct
     **/
    public function getProductStock(string $id): array
    {
        $client = new \GuzzleHttp\Client();
        $res = $client->request(
            'GET',
            getDmsProductStockEndpoint($id),
            [
                'auth' => [getDmsRestUser(), getDmsRestPassword()]
            ]
        );

        if (200 != $res->getStatusCode()) {
            return [];
        }

        return json_decode($res->getBody()->getContents())->response;
    }

    /**
     * Update stock
     **/
    public function correctStock(array $items): bool
    {
        $response = wp_remote_post(
            getDmsProductStockCorrectionEndpoint(),
            [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(getDmsRestUser() . ':' . getDmsRestPassword()),
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode(
                    [
                        'items' => $items
                    ]
                ),
            ]
        );

        if (200 != \wp_remote_retrieve_response_code($response)) {
            return false;
        }

        $body = json_decode(\wp_remote_retrieve_body($response));

        return true;
    }
}
