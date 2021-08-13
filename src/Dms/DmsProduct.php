<?php

namespace HDW\ProjectDmsImporter\Dms;

use HDW\ProjectDmsImporter\Dms\DmsApi;
use HDW\ProjectDmsImporter\Contracts\ErpApiContract;
use HDW\ProjectDmsImporter\Contracts\ProductContract;

class DmsProduct implements ProductContract
{

    /** @var string $id ERP product id */
    protected $id = null;

    /** @var object $data Product data */
    protected $data;

    protected $colorIndex = 0;

    /**
     * Constructor
     *
     * @param string $id Product
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
     * @return string Product name
     **/
    public function getName(): string
    {
        return $this->data->name ?? '';
    }

    /**
     * Get ID
     *
     * @return string Product ID
     **/
    public function getId(): string
    {
        return $this->data->id ?? '';
    }

    /**
     * Get Product ID slug
     *
     * @return string Product ID slug
     **/
    public function getIdSlug(): string
    {
        return str_replace('_', '-', $this->getId());
    }

    /**
     * Load product from ERP
     *
     * @throws Exception
     * @return DmsProduct
     **/
    public function load(): ProductContract
    {
        if (!$this->id) {
            throw new Exception("Missing ERP product id");
        }
        $product = $this->api->getProduct($this->id);
        $this->set($product);
        // $stock = $this->api->getProductStock($this->id);
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

    /**
     * Load product from ERP
     *
     * @param array $stock Stock
     * @return void
     **/
    public function setStock(array $stock): void
    {
        $this->data->stock = $stock;
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

    /**
     * Article number
     *
     * @return string
     **/
    public function getArticle(): string
    {
        return $this->data->article ?? '';
    }

    /**
     * Get product categories
     *
     * @return array Categories
     **/
    public function getCategories(): array
    {
        $categories[] = $this->data->modelGroup;
        $categories[] = $this->data->salesGroupDesc;
        $categories[] = $this->getMaterialColorName();
        foreach (['Einlage', 'Schuhtyp', 'Verschluss', 'Funktion', 'Geschlecht'] as $attributeKey) {
            $attributes = $this->getAttribute($attributeKey);

            if (is_array($attributes)) {
                $categories = array_merge($categories, $attributes);
            } else {
                $categories[] = $attributes;
            }

            $attributesGroup = $this->getAttributeGroup($attributeKey);

            if($attributesGroup[0] == 'Einlage') {
                $attributesGroup[0] = 'Einlegesohlen';
            }

            if (is_array($attributesGroup)) {
                $categories = array_merge($categories, $attributesGroup);
            } else {
                $categories[] = $attributesGroup;
            }
        }
        // don't add Shop category if category Einlegesohlen exists
        if(!in_array('Einlegesohlen', $categories)) {
            $categories[] = 'Shop';
        }

        $cats = array_map(function ($value) {
            return str_replace([
                'Hirsch'
            ], [
                'MyDeer'
            ], $value);
        }, $categories);

        return $cats;
    }

    /**
     * Weight
     *
     * @return string
     **/
    public function getWeight(): string
    {
        return $this->data->weight / 1000 ?? '';
    }

    /**
     * Description
     *
     * @return string
     **/
    public function getDescription(): string
    {
        return $this->data->colors[$this->colorIndex]->modelText ?? $this->data->modelText;
    }

    /**
     * Post Thumbnail Id
     *
     * @return int
     **/
    public function getPrice(string $size): int
    {
        return 0;
    }

    /**
     * Post Thumbnail Id
     *
     * @return int
     **/
    public function getPrices(): array
    {
        return [];
    }

    /**
     * Post Thumbnail Id
     *
     * @return int
     **/
    public function getPostThumbnailId(): int
    {
        return 0;
    }

    /**
     * Get product attribute
     *
     * @return string
     **/
    public function getAttribute(string $key): array
    {
        $attributes = [];
        foreach ($this->data->modelSelectcrit as $attribute) {
            if ($attribute->name != $key) {
                continue;
            }

            foreach ($attribute->modelSelectcritPos as $criteria) {
                $attributes[] = $criteria->pos;
            }
        }

        return $attributes;
    }

    /**
     * Get product attribute group
     *
     * @return string
     **/
    public function getAttributeGroup(string $key): array
    {
        $attributeGroup = [];
        foreach ($this->data->group as $attribute) {
            if ($attribute != $key) {
                continue;
            }
            $attributeGroup[] = $attribute;
        }

        return $attributeGroup;
    }

    /**
     * Get SKU
     *
     * @return string
     **/
    public function getSku(): string
    {
        return $this->data->colors[$this->colorIndex]->id ? transformSKU($this->data->colors[$this->colorIndex]->id) : '';
    }

    /**
     * Get colors
     *
     * @return array
     **/
    public function getColors(): array
    {
        return $this->data->colors;
    }

    /**
     * Get colors
     *
     * @return array
     **/
    public function getColorNames(): array
    {
        $colors = [];

        foreach ($this->data->colors as $color) {
            $colors[$color->id] = $color->color;
        }

        return $colors;
    }

    /**
     * Get colors
     *
     * @return array
     **/
    public function getColorName(): string
    {
        return $this->data->colors[$this->colorIndex]->color;
    }

    /**
     * Get material color
     *
     * @return array
     **/
    public function getMaterialColorName(): string
    {
        return $this->data->colors[$this->colorIndex]->mainMaterialColorDesc;
    }

    /**
     * Get product image
     *
     * @return array
     **/
    public function getImage(): ?int
    {
        global $wpdb;
        $sku = $this->getSku();
        // firstly check if image -2 exist
        $sql = "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid LIKE '%$sku-2%' ORDER BY ID DESC LIMIT 1";
        $attachmentId = $wpdb->get_var($sql);
        
        if($attachmentId) {
            return $attachmentId;    
        } else {
            // if image -2 doesn't exist, get fallback image
            $sql = "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid LIKE '%$sku%' ORDER BY ID DESC LIMIT 1";
            $attachmentId = $wpdb->get_var($sql);

            // get woocommerce fallback image if no image was found
            if(!$attachmentId && get_option('woocommerce_placeholder_image')) {
                $attachmentId = get_option('woocommerce_placeholder_image');
            }

            return $attachmentId ?? null;
        }
    }

    /**
     * Get product gallery images
     *
     * @return array
     **/
    public function getImages(): array
    {
        global $wpdb;
        $sku = str_replace('_', '-', $this->getSku());
        $sql = "SELECT ID, guid FROM $wpdb->posts WHERE post_type = 'attachment' AND guid LIKE '%$sku-%' ORDER BY guid ASC";
        $result = $wpdb->get_results($sql);

        $gallery = [];
        $duplicates = [];

        if(!empty($result)) {
            $numberOfGalleryImages = 5;

            foreach ($result as $value) {
                // allow only -1 to -5
                for ($i=1; $i <= $numberOfGalleryImages; $i++) {
                    if (FALSE !== strpos($value->guid, strtolower($sku) . '-' . $i . '.jpg')) {
                        $duplicates[$value->ID] = strtolower($sku) . '-' . $i . '.jpg';
                        continue;
                    }
                }
            }

            // order array by key DESC to get the newest images
            krsort($duplicates);

            // remove duplicates
            $duplicates = array_unique($duplicates);

            // sort by values ASC (name)
            asort($duplicates);

            foreach ($duplicates as $key => $d) {
                $gallery[] = $key;
            }

            // swap position from -1 and -2 if at least 2 element exists
            if(count($gallery) > 1) {
                $temporary = $gallery[0];
                $gallery[0] = $gallery[1];
                $gallery[1] = $temporary;
            }
        }
        
        return $gallery ?? null;
    }
        
    /**
     * Get sizes
     *
     * @return array
     **/
    public function getSizes(): array
    {
        $sizes = [];
        if (!isset($this->data->colors[$this->colorIndex])) {
            return $sizes;
        }

        foreach ($this->data->colors[$this->colorIndex]->sizes as $size) {
            if (!isset($size->size)) {
                continue;
            }
            $sizes[$size->id] = $size->size;
        }

        return \array_unique($sizes);
    }

    /**
     * Get order number
     *
     * @return string
     **/
    public function getOrderNumber(): string
    {
        return $this->data->order_number ?? '';
    }

    /**
     * Get order status
     *
     * @return string
     **/
    public function getStatus(): string
    {
        return $this->data->status ?? '';
    }

    /**
     * Get sizes
     *
     * @return array
     **/
    public function getSizeDetails(): array
    {
        return $this->data->colors[$this->colorIndex]->sizes ?? [];
    }

    /**
     * Get stock
     *
     * @return int
     **/
    public function getStock(): int
    {
        return $this->data->stock ?? [];
    }

    /**
     * Get stock
     *
     * @return int
     **/
    public function getStockSum(): int
    {
        $sum = 0;

        foreach ($this->getSizeDetails() as $size) {
            $sum += $size->qty;
        }

        return $sum;
    }

    /**
     * Get stock
     *
     * @return int
     **/
    public function getStockStatus(): string
    {
        $status = false;
        return $this->getStockSum() > 0 ? 'instock' : 'outofstock';
    }

    /**
     *  Checks if product has variations
     *
     * @return bool
     **/
    public function hasVariations(): bool
    {
        return true;
    }

    public function getHash(): string
    {
        $data = $this->getData();
        return md5(json_encode($data));
    }

    public function setColor(int $index): bool
    {
        $colors = $this->getColors();

        if (!isset($colors[$index])) {
            return false;
        }

        $this->colorIndex = $index;

        return true;
    }
}
