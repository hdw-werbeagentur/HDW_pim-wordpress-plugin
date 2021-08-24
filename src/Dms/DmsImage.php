<?php

namespace HDW\ProjectDmsImporter\Dms;

use HDW\ProjectDmsImporter\Dms\DmsApi;
use HDW\ProjectDmsImporter\Contracts\ImageContract;

class DmsImage implements ImageContract
{
    /**
     * Constructor
     *
     * @param string $id Image
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
     * @return string Image name
     **/
    public function getName(): string
    {
        return $this->data->name ?? '';
    }

    /**
     * Get Size
     *
     * @return string Image Size
     **/
    public function getSize(): string
    {
        if ($this->getWidth() > 0 && $this->getHeight() > 0) {
            return $this->getWidth() . ' x ' . $this->getHeight();
        }

        if ($this->getWidth() == 0 && $this->getHeight() > 0) {
            return $this->getHeight() . ' ' . __('px', 'hdw-dms-importer') . ' ' . __('height scale', 'hdw-dms-importer');
        }

        if ($this->getWidth() > 0 && $this->getHeight() == 0) {
            return $this->getWidth() . ' ' . __('px', 'hdw-dms-importer') . ' ' . __('width scale', 'hdw-dms-importer');
        }
    }

    /**
     * Get Width
     *
     * @return string Image width
     **/
    public function getWidth(): string
    {
        return $this->data->width ?? '';
    }

    /**
     * Get Height
     *
     * @return string Image height
     **/
    public function getHeight(): string
    {
        return $this->data->height ?? '';
    }

    /**
     * Load Image from ERP
     *
     * @throws Exception
     * @return DmsProduct
     **/
    public function load(): ImageContract
    {
        if (!$this->id) {
            throw new Exception("Missing ERP Image id");
        }
        $Image = $this->api->getImage($this->id);
        $this->set($Image);

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
