<?php

namespace HDW\ProjectDmsImporter\Dms;

use HDW\ProjectDmsImporter\Dms\DmsLanguage;
use HDW\ProjectDmsImporter\Contracts\LanguageContract;

class DmsLanguage implements LanguageContract
{
    /**
     * Constructor
     *
     * @param string $id Language
     **/
    public function __construct(string $id = '')
    {
        $this->api = DmsApi::getInstance();
        $this->id = $id;
        $this->data = new \stdClass();
    }

    /**
     * Get name
     *
     * @return string Language name
     **/
    public function getName(): string
    {
        return $this->data->native_name ?? '';
    }

    /**
     * Get ID
     *
     * @return string Language ID
     **/
    public function getId(): string
    {
        return $this->data->id ?? '';
    }

    /**
     * Get Iso slug
     *
     * @return string Language ID
     **/
    public function getIso(): string
    {
        return $this->data->iso_639_1 ?? '';
    }

    // /**
    //  * Get Product ID slug
    //  *
    //  * @return string Product ID slug
    //  **/
    // public function getIdSlug(): string
    // {
    //     return str_replace('_', '-', $this->getId());
    // }

    /**
     * Load language from ERP
     *
     * @throws Exception
     * @return DmsProduct
     **/
    public function load(): LanguageContract
    {
        if (!$this->id) {
            throw new Exception("Missing ERP language id");
        }
        $language = $this->api->getLanguage($this->id);
        $this->set($language);
        // $this->setStock($stock);

        return $this;
    }

    /**
     * Load product from ERP
     *
     * @param \stdClass $data Data
     * @return void
     **/
    public function set(\stdClass $data): void
    {
        $this->data = $data;
    }

    // /**
    //  * Load product from ERP
    //  *
    //  * @param array $stock Stock
    //  * @return void
    //  **/
    // public function setStock(array $stock): void
    // {
    //     $this->data->stock = $stock;
    // }

    /**
     * Get data
     *
     * @return array Data
     **/
    public function getData()
    {
        return $this->data;
    }

    // /**
    //  * Article number
    //  *
    //  * @return string
    //  **/
    // public function getArticle(): string
    // {
    //     return $this->data->article ?? '';
    // }

    // /**
    //  * Get product categories
    //  *
    //  * @return array Categories
    //  **/
    // public function getCategories(): array
    // {
    //     $categories[] = $this->data->modelGroup;
    //     $categories[] = $this->data->salesGroupDesc;
    //     $categories[] = $this->getMaterialColorName();
    //     foreach (['Einlage', 'Schuhtyp', 'Verschluss', 'Funktion', 'Geschlecht'] as $attributeKey) {
    //         $attributes = $this->getAttribute($attributeKey);

    //         if (is_array($attributes)) {
    //             $categories = array_merge($categories, $attributes);
    //         } else {
    //             $categories[] = $attributes;
    //         }

    //         $attributesGroup = $this->getAttributeGroup($attributeKey);

    //         if($attributesGroup[0] == 'Einlage') {
    //             $attributesGroup[0] = 'Einlegesohlen';
    //         }

    //         if (is_array($attributesGroup)) {
    //             $categories = array_merge($categories, $attributesGroup);
    //         } else {
    //             $categories[] = $attributesGroup;
    //         }
    //     }
    //     // don't add Shop category if category Einlegesohlen exists
    //     if(!in_array('Einlegesohlen', $categories)) {
    //         $categories[] = 'Shop';
    //     }

    //     $cats = array_map(function ($value) {
    //         return str_replace([
    //             'Hirsch'
    //         ], [
    //             'MyDeer'
    //         ], $value);
    //     }, $categories);

    //     return $cats;
    // }

    // /**
    //  * Get order number
    //  *
    //  * @return string
    //  **/
    // public function getOrderNumber(): string
    // {
    //     return $this->data->order_number ?? '';
    // }
}
