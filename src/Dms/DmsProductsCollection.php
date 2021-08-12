<?php

namespace HDW\ProjectDmsImporter\Dms;

use HDW\ProjectDmsImporter\Dms\DmsApi;
use HDW\ProjectDmsImporter\Dms\DmsProduct;

class DmsProductsCollection
{

    /** @var int $data Products count */
    protected $count = 0;

    /** @var array $products Array of DmsProduct objects */
    protected $products = [];

    /**
     * Construct
     *
     * @return self;
     **/
    public function __construct()
    {
        $this->api = DmsApi::getInstance();
    }

    /**
     * Set collection data
     *
     * @return array
     **/
    public function get(): array
    {
        return $this->products;
    }

    /**
     * Set collection data
     *
     * @param array $data Data
     * @return void
     **/
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Load products collection from ERP
     *
     * @return void
     **/
    public function load(): DmsProductsCollection
    {
        $products = $this->api->getProducts();
        $this->set($products);

        return $this;
    }

    /**
     * Set collection data
     *
     * @param array $data Data
     * @return void
     **/
    public function set(array $data): void
    {
        $this->products = [];
        foreach ($data as $d) {
            $product = new DmsProduct();
            $product->set($d);
            $this->products[$product->getId()] = $product;
        }
        $this->count = count($this->products);
    }
}
