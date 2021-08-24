<?php

namespace HDW\ProjectDmsImporter\Dms;

use HDW\ProjectDmsImporter\Dms\DmsApi;
use HDW\ProjectDmsImporter\Dms\DmsImage;

class DmsImagesCollection
{

    /** @var int $data Products count */
    protected $count = 0;

    /** @var array $images Array of DmsImage objects */
    protected $images = [];

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
        return $this->images;
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
    public function load(): DmsImagesCollection
    {
        $images = $this->api->getApiImages();
        $this->set($images);

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
        $this->images = [];

        foreach ($data as $d) {
            $image = new DmsImage();
            $image->set($d);
            $this->images[$image->getName()] = $image; 
        }
        $this->count = count($this->images);
    }
}
