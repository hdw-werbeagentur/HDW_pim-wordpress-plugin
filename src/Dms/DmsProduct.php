<?php

namespace HDW\ProjectDmsImporter\Dms;

use HDW\ProjectDmsImporter\Dms\DmsApi;
use HDW\ProjectDmsImporter\Contracts\ErpApiContract;
use HDW\ProjectDmsImporter\Contracts\ProductContract;

use function GuzzleHttp\json_decode;

class DmsProduct implements ProductContract
{

    /** @var string $id ERP product id */
    protected $id = null;

    /** @var object $data Product data */
    protected $data;

    protected $colorIndex = 0;

    protected $variants = [];

    /** @var string $languageDmsSlug String of the selected language where the import should use  */
    protected $languageDmsSlug;

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
        $this->languageDmsSlug = \getDmsSelectedLanguage() ?? '';
    }

    /**
     * Get name
     *
     * @return string Product name
     **/
    public function getName(): string
    {
        // check if property exist
        if (!property_exists($this->data, 'name')) {
            return '';
        }

        return $this->data->name ?? '';
    }

    /**
     * Get brand
     *
     * @return string Product brand
     **/
    public function getBrand(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'brand')) {
            return '';
        }

        return $this->getSelectValues('brand');
    }

    /**
     * Get master number
     *
     * @return string Product master number
     **/
    public function getMasterNumber(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'master-number')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'master-number'}->value);
        return $value->t ?? '';
    }

    /**
     * Get format
     *
     * @return string Product format
     **/
    public function getFormat()
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'format')) {
            return '';
        }

        $value = json_decode($this->data->attributes->format->value);


        // check if it is a variant product HDW
        $variants = [];

        if ($this->getVariants()) {
            foreach ($this->getVariants() as $variant) {
                $variants[] = $variant->format ?? '';
            }

            if ($value->t) {
                $variants[] = $value->t;
            }

            $results = array_unique($variants); // remove duplicates
            $results = array_filter($results); // remove empty values

            return $results;
        }

        return $value->t ?? '';
    }

    /**
     * Get order quantity
     *
     * @return string Product order quantity
     **/
    public function getOrderQuantity(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'format')) {
            return '';
        }

        $value = json_decode($this->data->attributes->format->value);
        return $value->t ?? '';
    }

    /**
     * Get sales units
     *
     * @return string Sales units
     **/
    public function getSalesUnits(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'sales-units')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'sales-units'}->value);
        return $value->t ?? '';
    }

    /**
     * Get industries
     *
     * @return array Product industries
     **/
    public function getIndustries(): array
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'industries')) {
            return [];
        }

        return $this->getSelectValues('industries', 'multiselect');
    }

    /**
     * Get packaging type
     *
     * @return string Product packaging type
     **/
    public function getPackagingType(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'packaging-type')) {
            return '';
        }

        return $this->getSelectValues('packaging-type');
    }

    /**
     * Get product properties usp
     *
     * @return string Product properties usp
     **/
    public function getPropertiesUsp(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'product-properties-usp')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'product-properties-usp'}->value);
        return $value->t ?? '';
    }

    /**
     * Get product icons usp usp
     *
     * @return string Product properties usp
     **/
    public function getIconsUsp(): array
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'product-icons-usp')) {
            return [];
        }

        return $this->getSelectValues('product-icons-usp', 'multiselect');
    }

    /**
     * Get product profile
     *
     * @return string Product profile
     **/
    public function getProfile(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'short-product-profile')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'short-product-profile'}->value);
        return $value->t ?? '';
    }

    /**
     * Get product eco flower nr
     *
     * @return string Product eco flower nr
     **/
    public function getEcoFlowerNr(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'ecoflowernr')) {
            return '';
        }

        $value = json_decode($this->data->attributes->ecoflowernr->value);
        return $value->t ?? '';
    }

    /**
     * Get product nordic swan nr
     *
     * @return string Product nordic swan nr
     **/
    public function getNordicSwanNr(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'nordicswannr')) {
            return '';
        }

        $value = json_decode($this->data->attributes->nordicswannr->value);
        return $value->t ?? '';
    }

    /**
     * Get product sds
     *
     * @return string Product sds
     **/
    public function getSds(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'sds')) {
            return '';
        }

        $value = json_decode($this->data->attributes->sds->value);
        return $value->t ?? '';
    }

    /**
     * Get product si ti
     *
     * @return string Product si ti
     **/
    public function getSiTi(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'si-ti')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'si-ti'}->value);

        if ($value->t == '') {
            return '';
        }

        $aws = \getFileRootPath();
        // check if aws path is already in give path
        if (str_contains($value->t, $aws)) {
            $aws = '';
        }

        return $aws . $value->t;
    }

    /**
     * Get downloads html
     *
     * @return string Product downloads html (Wordpress only)
     **/
    public function getDownloadsHtml(): string
    {
        $downloadsHtml = '';

        if ($this->getSiTi() != '' || $this->getSds() != '' || $this->getOperatingInstructionsDe() != '') {
            $downloadsHtml .= '<ul>';
        }

        if ($this->getSiTi() != '') {
            $downloadsHtml .= '<li>';
            $downloadsHtml .= '<a href="' . $this->getSiTi() . '" target="_blank">' . __('Sustainability Information', 'hdw-dms-importer') . '</a>';
            $downloadsHtml .= '</li>';
        }

        if ($this->getSds() != '') {
            $downloadsHtml .= '<li>';
            $downloadsHtml .= '<a href="' . $this->getSds() . '" target="_blank">' . __('Safety Data Sheet', 'hdw-dms-importer') . ' ' . $this->getOrderQuantity() . '</a>';
            $downloadsHtml .= '</li>';
        }

        if ($this->getOperatingInstructionsDe() != '') {
            $downloadsHtml .= '<li>';
            $downloadsHtml .= '<a href="' . $this->getOperatingInstructionsDe() . '" target="_blank">' . __('Operating instructions', 'hdw-dms-importer') . ' ' . $this->getOrderQuantity() . '</a>';
            $downloadsHtml .= '</li>';
        }

        // check if product have variants
        $variants = $this->getVariants();
        if (count($variants) > 0) {
            foreach ($variants as $variant) {
                if ($variant->sds != '') {
                    $downloadsHtml .= '<li>';
                    $downloadsHtml .= '<a href="' . $variant->sds . '" target="_blank">' . __('Safety Data Sheet', 'hdw-dms-importer') . ' ' . ($variant->format ?? '') . '</a>';
                    $downloadsHtml .= '</li>';
                }

                if ($variant->operating_instructions_de != '') {
                    $aws = \getFileRootPath();

                    $downloadsHtml .= '<li>';
                    $downloadsHtml .= '<a href="' . $aws . $variant->operating_instructions_de . '" target="_blank">' . __('Operating instructions', 'hdw-dms-importer') . ' ' . ($variant->format ?? '') . '</a>';
                    $downloadsHtml .= '</li>';
                }
            }
        }

        if ($this->getSiTi() != '' || $this->getSds() != '' || $this->getOperatingInstructionsDe() != '') {
            $downloadsHtml .= '</ul>';
        }

        return $downloadsHtml;
    }

    /**
     * Get product operating instructions de
     *
     * @return string Product operating instructions de
     **/
    public function getOperatingInstructionsDe(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'operating-instructions-de')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'operating-instructions-de'}->value);

        if ($value->t == '') {
            return '';
        }

        $aws = \getFileRootPath();
        // check if aws path is already in give path
        if (str_contains($value->t, $aws)) {
            $aws = '';
        }

        return $aws . $value->t;
    }

    /**
     * Get product application pictograms picture
     *
     * @return string Product application pictograms picture
     **/
    public function getApplicationPictogramsPicture(): array
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'application-pictograms-picture')) {
            return [];
        }

        return $this->getSelectValues('application-pictograms-picture', 'multiselect');
    }

    /**
     * Get product application pictograms text
     *
     * @return string Product application pictograms text
     **/
    public function getApplicationPictogramsText(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'application-pictograms-text')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'application-pictograms-text'}->value);
        return $value->t ?? '';
    }

    /**
     * Get product application category
     *
     * @return string Product application category
     **/
    public function getApplicationCategory(): array
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'application-category')) {
            return [];
        }

        return $this->getSelectValues('application-category', 'multiselect');
    }

    /**
     * Get product application range si ti
     *
     * @return string Product application range si ti
     **/
    public function getApplicationRangeSiTi(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'application-range-si-ti')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'application-range-si-ti'}->value);
        return $value->t ?? '';
    }

    /**
     * Get product scope of application picture
     *
     * @return string Product scope of application picture
     **/
    public function getScopeOfApplicationPicture(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'scope-of-application-picture')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'scope-of-application-picture'}->value);
        return $value->t ?? '';
    }

    /**
     * Get product application purposes
     *
     * @return string Product application purposes
     **/
    public function getApplicationPurposes(): array
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'application-purposes')) {
            return [];
        }

        return $this->getSelectValues('application-purposes', 'multiselect');
    }

    /**
     * Get product dosage
     *
     * @return string Product dosage
     **/
    public function getDosage(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'dosage')) {
            return '';
        }

        $value = json_decode($this->data->attributes->dosage->value);
        return $value->t ?? '';
    }

    /**
     * Get product composition
     *
     * @return string Product composition
     **/
    public function getProductComposition(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'product-composition')) {
            return '';
        }

        return $this->getSelectValues('product-composition');
    }

    /**
     * Get product surface material
     *
     * @return string Product surface material
     **/
    public function getSurfaceMaterial(): array
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'surface-material')) {
            return [];
        }

        return $this->getSelectValues('surface-material', 'multiselect');
    }

    /**
     * Get product ph value
     *
     * @return string Product ph value
     **/
    public function getPhValue(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'ph-value')) {
            return '';
        }

        return $this->getSelectValues('ph-value');
    }

    /**
     * Get product colour odour
     *
     * @return string Product colour odour
     **/
    public function getColourOdour(): array
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'colour-odour')) {
            return [];
        }

        return $this->getSelectValues('colour-odour', 'multiselect');
    }

    /**
     * Get product water hardness
     *
     * @return string Product water hardness
     **/
    public function getWaterHardness(): array
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'water-hardness')) {
            return [];
        }

        return $this->getSelectValues('water-hardness', 'multiselect');
    }

    /**
     * Get product dosing systems
     *
     * @return string Product dosing systems
     **/
    public function getDosingSystems(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'dosing-systems')) {
            return '';
        }

        return $this->getSelectValues('dosing-systems');
    }

    /**
     * Get product ean code
     *
     * @return string Product ean code
     **/
    public function getEanCode(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'ean-code')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'ean-code'}->value);
        return $value->t ?? '';
    }

    /**
     * Get product dosage table
     *
     * @return string Product dosage table
     **/
    public function getDosageTable(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'dosage-table')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'dosage-table'}->value);
        return $value->t ?? '';
    }

    /**
     * Get product disinfection table
     *
     * @return string Product disinfection table
     **/
    public function getDisinfectionTable(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'disinfection-table')) {
            return '';
        }

        $value = json_decode($this->data->attributes->{'disinfection-table'}->value);
        return $value->t ?? '';
    }

    /**
     * Get product certificates
     *
     * @return string Product certificates
     **/
    public function getProductCertificates(): array
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'product-certificates')) {
            return [];
        }

        $certificates = $this->getSelectValues('product-certificates', 'multiselect');

        if (is_array($certificates)) {
            foreach ($certificates as $key => $certificate) {
                // cradle to cradle (gold, silver, bronze) adjustments
                if (str_contains($certificate, 'Cradle to Cradle')) {
                    $details = explode('Cradle to Cradle', $certificate);
                    $certificates[$key] = trim(str_replace($details[1], '', $certificate)); // replace original value
                    $certificates[] = trim($details[1]);
                }
                // cradle to cradle (gold, silver, bronze) adjustments for russian language
                if (str_contains($certificate, 'От колыбель к колыбели')) {
                    $details = explode('От колыбель к колыбели', $certificate);
                    $certificates[$key] = trim(str_replace($details[1], '', $certificate)); // replace original value
                    $certificates[] = trim($details[1]);
                }
            }
        }

        return $certificates;
    }

    /**
     * Get product CLP labelling
     *
     * @return string Product CLP labelling
     **/
    public function getCLPLabelling(): string
    {
        // check if property exist
        if (!property_exists($this->data->attributes, 'clp-labelling')) {
            return '';
        }

        return $this->getSelectValues('clp-labelling');
    }

    // /**
    //  * Get product Main Features
    //  *
    //  * @return string Product CLP labelling
    //  **/
    // public function getMainFeatures(): string
    // {
    //     if (!isset($this->data->attributes->{'main-features'})) {
    //         return '';
    //     }

    //     $value = json_decode($this->data->attributes->{'main-features'}->value);
    //     return $value->t ?? '';
    // }

    /**
     * Get ID
     *
     * @return string Product ID
     **/
    public function getId(): string
    {
        // check if property exist
        if (!property_exists($this->data, 'id')) {
            return '';
        }

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

        if (!$this->languageDmsSlug) {
            throw new Exception("Missing ERP language slug");
        }

        $product = $this->api->getProduct($this->id, $this->languageDmsSlug);
        $this->set($product);

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

    /**
     * Article number
     *
     * @return string
     **/
    // public function getArticle(): string
    // {
    //     return $this->data->article ?? '';
    // }

    /**
     * Get product categories
     *
     * @return array Categories
     **/
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
    //  * Weight
    //  *
    //  * @return string
    //  **/
    // public function getWeight(): string
    // {
    //     return $this->data->weight / 1000 ?? '';
    // }

    /**
     * Description
     *
     * @return string
     **/
    public function getDescription(): string
    {
        // check if property exist
        if (!property_exists($this->data, 'description')) {
            return '';
        }

        return $this->data->description ?? '';
    }

    /**
     * Short Description
     *
     * @return string
     **/
    public function getShortDescription(): string
    {
        // check if property exist
        if (!property_exists($this->data, 'short_description')) {
            return '';
        }

        return $this->data->short_description ?? '';
    }

    /**
     * Get product image
     *
     * @return string
     **/
    public function getImage(): string
    {
        $aws = \getFileRootPath();

        // check if property exist
        if (!property_exists($this->data, 'image')) {
            return '';
        }

        if ($this->data->image === '') {
            return '';
        }

        return $this->data->image ? $aws . $this->data->image : '';
    }

    /**
     * Get product image thumbnail
     *
     * @return string
     **/
    public function getThumbnail(): string
    {
        // check if property exist
        if (!property_exists($this->data, 'image')) {
            return '';
        }

        $image = $this->data->image ?? '';

        if($image == '') {
            return '';
        }

        $aws = \getFileRootPath();

        $basename = basename($image);

        $thumbnail =  str_replace($basename, '' , $image) . 'thumbs/' . basename($image);

        return $aws . $thumbnail;
    }

    /**
     * Get product image thumbnails
     *
     * @return string
     **/
    public function getThumbnails(): array
    {
        // check if property exist
        if (!property_exists($this->data, 'image')) {
            return [];
        }

        $image = $this->data->image ?? '';

        if($image == '') {
            return [];
        }

        $aws = \getFileRootPath();

        $imageSizes = \getDmsImageSizes();

        $sizes = [];

        $basename = basename($image);

        if ($imageSizes) {
            foreach ($imageSizes->get() as $size) {

                $thumbnail = str_replace($basename, '', $image) . 'thumbs/' . basename($image);

                $subdirectory = '';

                if ($size->getName() != 'thumbnail') {

                    if ($size->getWidth() > 0 && $size->getHeight() > 0) {
                        $subdirectory = $size->getWidth() . '_' . $size->getHeight() . '/';
                    }

                    if ($size->getWidth() == 0 && $size->getHeight() > 0) {
                        $subdirectory = 'height_' . $size->getHeight() . '/';
                    }

                    if ($size->getWidth() > 0 && $size->getHeight() == 0) {
                        $subdirectory = 'width_' . $size->getWidth() . '/';
                    }

                    $thumbnail = str_replace($basename, '', $image) . 'thumbs/' . $subdirectory . basename($image);
                }

                $sizes[$size->getName()] = $aws . $thumbnail;
            }
        }

        return $sizes;
    }

    /**
     * Get sku
     *
     * @return string
     **/
    public function getSku(): string
    {
        // check if property exist
        if (!property_exists($this->data, 'order_number')) {
            return '';
        }

        return $this->data->order_number ?? '';
    }

    /**
     * Get product status
     *
     * @return string
     **/
    public function getStatus(): string
    {
        // check if property exist
        if (!property_exists($this->data, 'status')) {
            return '';
        }

        return $this->data->status ?? '';
    }

    /**
     * Get product type
     *
     * @return string
     **/
    public function getProductType(): string
    {
        // check if property exist
        if (!property_exists($this->data, 'type')) {
            return '';
        }

        return $this->data->type ?? '';
    }

    /**
     * Get product category
     *
     * @return string Product category
     **/
    public function getProductCategory(): array
    {
        // check if property exist
        if (!property_exists($this->data, 'product-category')) {
            return [];
        }

        return $this->getSelectValues('product-category', 'multiselect');
    }

    /**
     * Get product variants
     *
     * @return string
     **/
    public function getVariants(): array
    {
        // check if property exist
        if (!property_exists($this->data, 'variants')) {
            return [];
        }

        if(!isset($this->data->variants)) {
            return [];
        }

        return (array) $this->data->variants ?? [];
    }

    /**
     *  Checks if product has variations
     *
     * @return bool
     **/
    // public function hasVariations(): bool
    // {
    //     return true;
    // }

    public function getHash(): string
    {
        $data = $this->getData();
        return md5(json_encode($data));
    }

    private function getSelectValues($attribute, $type = null)
    {
        // check if variable is set
        if (!isset($this->data->attributes->{$attribute})) {
            if ($type === 'multiselect') {
                return [];
            }
            return '';
        }

        // check if variable value is set
        if (!isset($this->data->attributes->{$attribute}->value)) {
            if ($type === 'multiselect') {
                return [];
            }
            return '';
        }

        $detail = json_decode($this->data->attributes->{$attribute}->value);

        if (is_null($detail)) {
            if ($type === 'multiselect') {
                return [];
            }
            return '';
        }

        // if array is not empty
        if ($type === 'multiselect') {
            if (is_null($detail->t)) {
                return [];
            }

            if (count($detail->t) == 0) {
                return [];
            }
            return $detail->t;
        }

        if (is_null($detail->t)) {
            return '';
        }

        if (is_array($detail->t)) {
            return $detail->t[0] ?? '';
        }

        return $detail->t ?? '';
    }
}
