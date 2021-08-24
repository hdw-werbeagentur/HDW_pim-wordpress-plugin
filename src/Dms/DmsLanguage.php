<?php

namespace HDW\ProjectDmsImporter\Dms;

use HDW\ProjectDmsImporter\Dms\DmsApi;
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
        return $this->data->iso_name ?? '';
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
     * @return string Language iso
     **/
    public function getIso(): string
    {
        return $this->data->iso_639_1 ?? '';
    }

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

    /**
     * Get data
     *
     * @return array Data
     **/
    public function getData()
    {
        return $this->data;
    }
}
