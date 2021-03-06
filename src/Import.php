<?php

namespace HDW\ProjectDmsImporter;

use HDW\ProjectDmsImporter\Contracts\ProductContract;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Service example
 */
class Import
{
    /**
     * Register hooks
     *
     * @return void
     **/
    public function register(): void
    {
        \add_action('wp_ajax_importProduct', [$this, 'ajaxImportProduct']);
    }

    /**
     * Add event columns
     **/
    public function ajaxImportProduct(): void
    {
        if ($_POST['progressIteration'] == 1) {
            // \Anni\Info('Produkt Import', 'Import gestartet (Manuell)');
        }

        $this->cleanShop();

        $id = \sanitize_text_field($_REQUEST['id']);

        $language = \getDmsSelectedLanguage();

        $product = \getDmsProduct($id, $language);

        $postIds = $this->importProduct($product);

        // echo $postIds[0];

        // $test = get_post([
        //     'ID' => $postIds[0]
        // ]);

        if (empty($postIds)) {
            // \Anni\Error(
            //     'Produkt Import',
            //     sprintf('Produkt import fehlgeschlagen %s (%s) importiert', $product->getName(), $product->getSku()),
            //     [
            //         'product' => $product->getName(),
            //         'sku' => $product->getSku(),
            //     ]
            // );
            \wp_send_json_error();
        }

        \wp_send_json_success([
            'IDs' => $postIds,
            'data' => $product->getData(),
        ]);
    }

    /**
     * Find product by sku
     **/
    public static function findBySku(string $sku, array $filter = []): int
    {
        $products = \getProductsBySKU($sku, $filter);
        $count = count($products);

        if ($count > 1) {
            // \Anni\Debug('Produkt Import', sprintf('%d mögliche Produkte für %s', $count, $sku), [
            //     'sku' => $sku,
            //     'products' => $products,
            //     'count' => $count,
            // ]);
        }

        if (count($products) == 0) {
            return 0;
        }

        return $products[0]->ID;
    }

    /**
     * Create import product
     **/
    public static function create(ProductContract $product)
    {
        // TODO
        $searchResult = Import::findBySku($product->getSku(), []);

        if ($searchResult) {
            // \Anni\Info('Produkt Import', sprintf('Aktualisiere Produkt %s (%s)', $product->getName(), $product->getSku()), [
            //     'id' => $searchResult,
            //     'name' => $product->getName(),
            //     'sku' => $product->getSku(),
            // ]);

            return $searchResult;

        } else {
            // \Anni\Info('Produkt Import', sprintf('Erstelle neues Produkt für %s (%s)', $product->getName(), $product->getSku()), [
            //     'name' => $product->getName(),
            //     'sku' => $product->getSku(),
            // ]);
        }

        $importProduct = wp_insert_post([
            'post_title' => $product->getName(),
            'post_type' => 'cpt_products'
        ]);

        return $importProduct;
    }

    /**
     * Import base product
     *
     * @param
     * @return int Post Id
     **/
    public static function importProduct(ProductContract $product): array
    {
        $skipUpdate = Import::getProductByHash($product);

        if ($skipUpdate) {
            wp_update_post([
                'ID' => $skipUpdate,
                'post_status' => 'publish'
            ]);
        }

        // if ($skipUpdate && 1 == 2) {
        //     // \Anni\Info('Produkt Import', sprintf('Überspringe Produkt %s (%s)', $product->getName(), $product->getSku()), [
        //     //     'name' => $product->getName(),
        //     //     'sku' => $product->getSku(),
        //     // ]);
        //     wp_update_post([
        //         'ID' => $skipUpdate,
        //         'post_status' => 'publish'
        //     ]);
        //     return [$skipUpdate];
        // }

        $postIds = [];

        $postId = Import::create($product);

        $postIds[] = $postId;

        $skuType = \getDmsSelectedSkuType();

        if ($postId) {
            // set post
            \wp_update_post(['ID' => $postId, 'post_content' => $product->getDescription()]);

            // set taxonomies
            \wp_set_object_terms($postId, $product->getIndustries(), 'tax_products_industries');
            \wp_set_object_terms($postId, $product->getApplicationCategory(), 'tax_products_application_scope');
            \wp_set_object_terms($postId, $product->getProductCategory(), 'tax_products_category');
            \wp_set_object_terms($postId, $product->getApplicationPurposes(), 'tax_products_application_purpose');
            \wp_set_object_terms($postId, $product->getProductComposition(), 'tax_products_composition');
            \wp_set_object_terms($postId, $product->getSurfaceMaterial(), 'tax_products_surface_material');
            \wp_set_object_terms($postId, $product->getProductCertificates(), 'tax_products_certificates');
            \wp_set_object_terms($postId, $product->getBrand(), 'tax_products_brands');
            // \wp_set_object_terms($postId, , 'tax_products_awards');
            \wp_set_object_terms($postId, $product->getPhValue(), 'tax_products_ph_value');
            \wp_set_object_terms($postId, $product->getFormat(), 'tax_products_package_size');
            \wp_set_object_terms($postId, $product->getColourOdour(), 'tax_products_color_smell');
            \wp_set_object_terms($postId, $product->getWaterHardness(), 'tax_products_water_hardness');
            \wp_set_object_terms($postId, $product->getCLPLabelling(), 'tax_products_identification');
            \wp_set_object_terms($postId, $product->getDosingSystems(), 'tax_products_dosing_systems');
            // \wp_set_object_terms($postId, , 'tax_products_category');

            // set meta fields
            \update_post_meta($postId, 'product-dms-id', $product->getId());
            if ($skuType == 'Master number' && $product->getMasterNumber() && $product->getMasterNumber() != '') {
                \update_post_meta($postId, 'product-order-number', $product->getMasterNumber());
            } else {
                \update_post_meta($postId, 'product-order-number', $product->getSku());
            }
            \update_post_meta($postId, 'product-subtitle', $product->getShortDescription());
            \update_post_meta($postId, 'product-advantages-html', $product->getPropertiesUsp());
            \update_post_meta($postId, 'product-downloads-html', $product->getDownloadsHtml());


            \update_post_meta($postId, '_thumbnail', $product->getThumbnail());
            \update_post_meta($postId, '_thumbnails', $product->getThumbnails());
            \update_post_meta($postId, '_sku', $product->getSku());
            \update_post_meta($postId, 'product-order-amount', $product->getOrderQuantity());
            \update_post_meta($postId, '_brand', $product->getBrand());
            \update_post_meta($postId, '_master-number', $product->getMasterNumber());
            \update_post_meta($postId, '_sales-units', $product->getSalesUnits());
            \update_post_meta($postId, '_packaging-type', $product->getPackagingType());
            \update_post_meta($postId, '_properties-usp', $product->getPropertiesUsp());
            \update_post_meta($postId, '_icons-usp', $product->getIconsUsp());
            \update_post_meta($postId, '_profile', $product->getProfile());
            \update_post_meta($postId, '_eco-flower-nr', $product->getEcoFlowerNr());
            \update_post_meta($postId, '_nordic-swan-nr', $product->getNordicSwanNr());
            \update_post_meta($postId, '_sds', $product->getSds());
            \update_post_meta($postId, '_si-ti', $product->getSiTi());
            \update_post_meta($postId, '_operating-instructions-de', $product->getOperatingInstructionsDe());
            \update_post_meta($postId, '_application-pictograms-picture', $product->getApplicationPictogramsPicture());
            \update_post_meta($postId, '_application-pictograms-text', $product->getApplicationPictogramsText());
            \update_post_meta($postId, '_application-category', $product->getApplicationCategory());
            \update_post_meta($postId, '_product-category', $product->getProductCategory());
            \update_post_meta($postId, '_application-range-si-ti', $product->getApplicationRangeSiTi());
            \update_post_meta($postId, '_scope-of-application-picture', $product->getScopeOfApplicationPicture());
            \update_post_meta($postId, '_application-purposes', $product->getApplicationPurposes());
            \update_post_meta($postId, '_dosage', $product->getDosage());
            \update_post_meta($postId, '_product-composition', $product->getProductComposition());
            \update_post_meta($postId, '_surface-material', $product->getSurfaceMaterial());
            \update_post_meta($postId, '_ph-value', $product->getPhValue());
            \update_post_meta($postId, '_colour-odour', $product->getColourOdour());
            \update_post_meta($postId, '_water-hardness', $product->getWaterHardness());
            \update_post_meta($postId, '_dosing-systems', $product->getDosingSystems());
            \update_post_meta($postId, '_ean-code', $product->getEanCode());
            \update_post_meta($postId, '_dosage-table', $product->getDosageTable());
            \update_post_meta($postId, '_disinfection-table', $product->getDisinfectionTable());
            \update_post_meta($postId, '_product-certificates', $product->getProductCertificates());
            ################## variants ######################
            // reset repeater fields
            \update_post_meta($postId, 'product-order-number-second', '');
            \update_post_meta($postId, 'product-order-amount-second', '');
            \update_post_meta($postId, 'product-order-number-third', '');
            \update_post_meta($postId, 'product-order-amount-third', '');
            \update_post_meta($postId, 'product-order-number-fourth', '');
            \update_post_meta($postId, 'product-order-amount-fourth', '');
            \update_post_meta($postId, 'product-order-number-fifth', '');
            \update_post_meta($postId, 'product-order-amount-fifth', '');
            \update_post_meta($postId, 'product-order-number-sixth', '');
            \update_post_meta($postId, 'product-order-amount-sixth', '');
            \update_post_meta($postId, 'product-order-number-seventh', '');
            \update_post_meta($postId, 'product-order-amount-seventh', '');

            $variants = $product->getVariants();
            if (count($variants) > 0) {

                if (isset($variants[0])) {
                    if ($skuType == 'Master number' && property_exists($variants[0], 'master_number') && $variants[0]->master_number != '') {
                        \update_post_meta($postId, 'product-order-number-second', $variants[0]->master_number);
                    } else {
                        \update_post_meta($postId, 'product-order-number-second', $variants[0]->order_number ?? '');
                    }

                    \update_post_meta($postId, 'product-order-amount-second', ($variants[0]->format ?? ''));
                }

                if (isset($variants[1])) {
                    if ($skuType == 'Master number' && property_exists($variants[1], 'master_number') && $variants[1]->master_number != '') {
                        \update_post_meta($postId, 'product-order-number-third', $variants[1]->master_number);
                    } else {
                        \update_post_meta($postId, 'product-order-number-third', $variants[1]->order_number ?? '');
                    }
                    \update_post_meta($postId, 'product-order-amount-third', ($variants[1]->format ?? ''));
                }

                if (isset($variants[2])) {
                    if ($skuType == 'Master number' && property_exists($variants[2], 'master_number') && $variants[2]->master_number != '') {
                        \update_post_meta($postId, 'product-order-number-fourth', $variants[2]->master_number);
                    } else {
                        \update_post_meta($postId, 'product-order-number-fourth', $variants[2]->order_number ?? '');
                    }
                    \update_post_meta($postId, 'product-order-amount-fourth', ($variants[2]->format ?? ''));
                }

                if (isset($variants[3])) {
                    if ($skuType == 'Master number' && property_exists($variants[3], 'master_number') && $variants[3]->master_number != '') {
                        \update_post_meta($postId, 'product-order-number-fifth', $variants[3]->master_number);
                    } else {
                        \update_post_meta($postId, 'product-order-number-fifth', $variants[3]->order_number ?? '');
                    }
                    \update_post_meta($postId, 'product-order-amount-fifth', ($variants[3]->format ?? ''));
                }

                if (isset($variants[4])) {
                    if ($skuType == 'Master number' && property_exists($variants[4], 'master_number') && $variants[4]->master_number != '') {
                        \update_post_meta($postId, 'product-order-number-sixth', $variants[4]->master_number);
                    } else {
                        \update_post_meta($postId, 'product-order-number-sixth', $variants[4]->order_number ?? '');
                    }
                    \update_post_meta($postId, 'product-order-amount-sixth', ($variants[4]->format ?? ''));
                }

                if (isset($variants[5])) {
                    if ($skuType == 'Master number' && property_exists($variants[5], 'master_number') && $variants[5]->master_number != '') {
                        \update_post_meta($postId, 'product-order-number-seventh', $variants[5]->master_number);
                    } else {
                        \update_post_meta($postId, 'product-order-number-seventh', $variants[5]->order_number ?? '');
                    }
                    \update_post_meta($postId, 'product-order-amount-seventh', ($variants[5]->format ?? ''));
                }
            }

            \update_post_meta($postId, '_import-hash', Import::createHash($product));
        }

        return $postIds;
    }

    public static function createHash(ProductContract $product): string
    {
        $data = $product->getData();
        return md5(json_encode($data));
    }

    public static function getProductByHash(ProductContract $product): int
    {
        $hash = $product->getHash();
        $posts = get_posts([
            'post_type' => 'cpt_products',
            'meta_query' => [
                [
                    'key' => '_import-hash',
                    'value' => $hash,
                ]
            ],
            'showposts' => -1,
            'fields' => 'ids',
            'post_status' => 'any'
        ]);

        if (count($posts) > 1) {
            // \Anni\Warning('Produkt Import', sprintf('Import Hash Duplikat %s (%s)', $product->getName(), $product->getSku()), [
            //     'name' => $product->getName(),
            //     'sku' => $product->getSku(),
            //     'hash' => $hash,
            //     'postIds' => $posts,
            // ]);
        }

        if (count($posts) != 1) {
            return 0;
        }

        return $posts[0];
    }

    /**
     * Clear shop
     *
     * @return void
     **/
    public static function cleanShop(bool $shouldSkipProducts = true)
    {
        if ($shouldSkipProducts) {
            $skip = [];
            $skipProducts = \getDmsProducts();

            if (!empty($skipProducts)) {
                foreach ($skipProducts->get() as $skipProduct) {
                    $skip[] = $skipProduct->getSku();
                }
            }
        }

        $posts = get_posts([
            'post_type' => 'cpt_products',
            'showposts' => -1,
            'fields' => 'ids',
        ]);

        foreach ($posts as $postId) {
            $sku = get_post_meta($postId, '_sku', true);
            if (in_array($sku, $skip)) {
                continue;
            }

            // \Anni\Info('Produkt Import', sprintf('Schalte %s auf Entwurf', get_the_title($postId)));

            wp_update_post([
                'ID' => $postId,
                'post_status' => 'draft'
            ]);
        }
    }
}
