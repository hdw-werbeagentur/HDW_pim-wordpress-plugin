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
        $sslSettings = (pathinfo($_SERVER['SERVER_NAME'], PATHINFO_EXTENSION) == 'test') ? ['verify' => false] : [];
        $client = new \GuzzleHttp\Client($sslSettings);
        $collection = [];
        $headers = [
            'Authorization' => 'Bearer ' . getDMSApiToken(),
            'Accept'        => 'application/json',
        ];

        $res = $client->request(
            'GET',
            getDmsProductsEndpoint($language),
            [
                'http_errors'   => false,
                'headers' => $headers
            ]
        );

        if ($res->getStatusCode() == 401) {
            echo '<span style="color: #F00;">' . __('Authorization missing. Please use a valid API Token', 'hdw-dms-importer') . '</span>';
        }

        if ($res->getStatusCode() != 200) {
            return $collection;
        }

        $collection = json_decode($res->getBody()->getContents());

        ############# brand filter start ##################
        $brand = \getDmsSelectedBrand();

        if ($brand) {
            if ($brand != 'select') {
                if ($collection) {
                    foreach ($collection as $key => $product) {
                        // check if attributes are set
                        if (!isset($product->attributes)) {
                            unset($collection[$key]);
                            continue;
                        }

                        // check if brand is set
                        if (!isset($product->attributes->brand)) {
                            unset($collection[$key]);
                            continue;
                        }

                        // check if brand value is set
                        if (!isset($product->attributes->brand->value)) {
                            unset($collection[$key]);
                            continue;
                        }

                        $brandValue = json_decode($product->attributes->brand->value);

                        // remove products that are not matching with the brand
                        if ((is_array($brandValue->t)) && $brandValue->t[0] != $brand) {
                            unset($collection[$key]);
                        }

                        // remove products that are not matching with the brand
                        if ((is_string($brandValue->t)) && $brandValue->t != $brand) {
                            unset($collection[$key]);
                        }
                    }
                }
            }
        }
        ############# brand filter end ###################

        return $collection;
    }

    /**
     * Get products
     *
     * @param string $id ERP product id
     * @param string $language ERP product language
     *
     * @return DmsProduct
     **/
    public function getProduct(string $id, string $language): ?\stdClass
    {
        $sslSettings = (pathinfo($_SERVER['SERVER_NAME'], PATHINFO_EXTENSION) == 'test') ? ['verify' => false] : [];
        $client = new \GuzzleHttp\Client($sslSettings);
        $headers = [
            'Authorization' => 'Bearer ' . getDMSApiToken(),
            'Accept'        => 'application/json',
        ];

        $res = $client->request(
            'GET',
            getDmsProductEndpoint($id, $language),
            [
                'http_errors'   => false,
                'headers' => $headers
            ]
        );

        if ($res->getStatusCode() == 401) {
            echo '<span style="color: #F00;">' . __('Authorization missing. Please use a valid API Token', 'hdw-dms-importer') . '</span>';
        }

        if ($res->getStatusCode() != 200) {
            return null;
        }

        return json_decode($res->getBody()->getContents());
    }

    /**
     * Get languages
     *
     * @return array
     **/
    public function getLanguages(): array
    {
        $sslSettings = (pathinfo($_SERVER['SERVER_NAME'], PATHINFO_EXTENSION) == 'test') ? ['verify' => false] : [];
        $client = new \GuzzleHttp\Client($sslSettings);
        $collection = [];
        $headers = [
            'Authorization' => 'Bearer ' . getDMSApiToken(),
            'Accept'        => 'application/json'
        ];

        $res = $client->request(
            'GET',
            getDmsLanguagesEndpoint(),
            [
                'http_errors'   => false,
                'headers'       => $headers
            ]
        );

        if ($res->getStatusCode() == 401) {
            echo '<span style="color: #F00;">' . __('Authorization missing. Please use a valid API Token', 'hdw-dms-importer') . '</span>';
        }

        if ($res->getStatusCode() != 200) {
            return $collection;
        }

        $collection = json_decode($res->getBody()->getContents());

        return $collection;
    }

    /**
     * Get languages
     *
     * @return array
     **/
    public function getApiImages(): array
    {
        $sslSettings = (pathinfo($_SERVER['SERVER_NAME'], PATHINFO_EXTENSION) == 'test') ? ['verify' => false] : [];
        $client = new \GuzzleHttp\Client($sslSettings);
        $collection = [];
        $headers = [
            'Authorization' => 'Bearer ' . getDMSApiToken(),
            'Accept'        => 'application/json',
        ];

        $res = $client->request(
            'GET',
            getDmsImagesEndpoint(),
            [
                'http_errors'   => false,
                'headers' => $headers
            ]
        );

        if ($res->getStatusCode() == 401) {
            echo '<span style="color: #F00;">' .__('Authorization missing. Please use a valid API Token', 'hdw-dms-importer') . '</span>';
        }

        if ($res->getStatusCode() != 200) {
            return $collection;
        }

        $collection = json_decode($res->getBody()->getContents());

        return $collection;
    }
}
