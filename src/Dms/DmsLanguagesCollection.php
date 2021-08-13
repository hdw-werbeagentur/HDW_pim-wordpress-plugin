<?php

namespace HDW\ProjectDmsImporter\Dms;

use HDW\ProjectDmsImporter\Dms\DmsApi;
use HDW\ProjectDmsImporter\Dms\DmsLanguage;

class DmsLanguagesCollection
{

    /** @var int $data Products count */
    protected $count = 0;

    /** @var array $languages Array of DmsLanguage objects */
    protected $languages = [];

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
        return $this->languages;
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
     * Load languages collection from ERP
     *
     * @return void
     **/
    public function load(): DmsLanguagesCollection
    {
        $languages = $this->api->getLanguages();
        $this->set($languages);

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
        $this->languages = [];
        foreach ($data as $d) {
            $language = new DmsLanguage();
            $language->set($d);
            $this->languages[$language->getId()] = $language;
        }
        $this->count = count($this->languages);
    }
}
