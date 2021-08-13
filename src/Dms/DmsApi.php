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
     * @param string $language ERP language slug
     *
     * @return array
     **/
    public function getProducts(string $language): array
    {
        $client = new \GuzzleHttp\Client();
        $collection = [];
        $headers = [
            'Authorization' => 'Bearer ' . getDMSApiToken(),
            'Accept'        => 'application/json',
        ];

        $res = $client->request(
            'GET',
            getDmsProductsEndpoint($language), 
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
     * Get languages
     *
     * @return array
     **/
    public function getLanguages(): array
    {
        $client = new \GuzzleHttp\Client();
        $collection = [];
        $headers = [
            'Authorization' => 'Bearer ' . getDMSApiToken(),
            'Accept'        => 'application/json',
        ];

        $res = $client->request(
            'GET',
            getDmsLanguagesEndpoint(),
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
}
