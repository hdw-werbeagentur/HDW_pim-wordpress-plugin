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

        $product = \getDmsProduct($id);

        $postIds = $this->importProduct($product);

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

        if ($_POST['progressIteration'] == $_POST['progressLimit']) {
            // \Anni\Info('Produkt Import', 'Import beendet');
        }

        \wp_send_json_success([
            'IDs' => $postIds,
            // 'data' => $product->getData(),
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
        
        if ($skipUpdate && 1 == 2) {
            // \Anni\Info('Produkt Import', sprintf('Überspringe Produkt %s (%s)', $product->getName(), $product->getSku()), [
            //     'name' => $product->getName(),
            //     'sku' => $product->getSku(),
            // ]);
            wp_update_post([
                'ID' => $skipUpdate,
                'post_status' => 'publish'
            ]);
            return [$skipUpdate];
        }

        $postIds = [];

        // $importProduct = Import::create($product);
        $postId = Import::create($product);

        if ($postId) {
            \update_post_meta($postId, '_sku', $product->getSku());
            \update_post_meta($postId, '_import-hash', Import::createHash($product));
        }

        //     // product image 
        //     $importProduct->set_image_id($product->getImage()); 

        //     $importProduct->set_status('publish');
        //     $importProduct->set_description($product->getDescription());
        //     $importProduct->set_catalog_visibility('visible');
        //     $importProduct->set_sku($product->getSku()); // We can't use set_sku as SKU has to be unique
        //     $importProduct->set_weight($product->getWeight());

        //     // Sizes
        //     $attribute->set_id(\wc_attribute_taxonomy_id_by_name('pa_size')); //if passing the attribute name to get the ID
        //     $attribute->set_name('pa_size'); //attribute name
        //     $attribute->set_options(array_map(function ($value) {
        //         return str_replace('½', ',5', $value);
        //     }, $product->getSizes())); // attribute value

        //     $attribute->set_position(1); //attribute display order
        //     $attribute->set_visible(1); //attribute visiblity
        //     $attribute->set_variation(1); //to use this attribute as variation or not
        //     $attributes[] = $attribute;
        //     $importProduct->set_attributes($attributes);

        //     $defaultSize = Import::findDefaultSize($product);
        //     if ($defaultSize) {
        //         $importProduct->set_default_attributes([
        //             'pa_size' => $defaultSize
        //         ]);
        //     }

        //     $postId = $importProduct->save();
        //     $postIds[] = $postId;

        //     if ($postId) {
        //         if (function_exists('update_field')) {
        //             \update_field(1219, implode(', ', $product->getAttribute('Material')), $postId);
        //             foreach ($product->getColorNames() as $productId => $color) {
        //                 \update_field(1220, $color, $postId);
        //             }
        //             \update_field(1221, implode(', ', $product->getAttribute('Schuhtyp')), $postId);
        //             \update_field(1222, implode(', ', $product->getAttribute('Sohle')), $postId);
        //             $sizes = count($product->getSizes()) ? reset($product->getSizes()) . ' - ' . end($product->getSizes()) : reset($product->getSizes());
        //             $sizes = str_replace('½', ',5', $sizes);
        //             \update_field(1223, $sizes, $postId);
        //             \update_field(539565, $product->getMaterialColorName(), $postId);
        //             \update_post_meta($postId, '_import-hash', Import::createHash($product));
        //         }
        //         \wp_set_object_terms($postId, $product->getCategories(), 'product_cat');
        //     }

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
            'post_type' => 'product',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'terms' => ['shop'],
                    'field' => 'slug',
                ],
            ],
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
            \Anni\Warning('Produkt Import', sprintf('Import Hash Duplikat %s (%s)', $product->getName(), $product->getSku()), [
                'name' => $product->getName(),
                'sku' => $product->getSku(),
                'hash' => $hash,
                'postIds' => $posts,
            ]);
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
    public static function cleanShop(bool $shouldSkipProducts = true): void
    {
        if ($shouldSkipProducts) {
            $skip = [];
            $skipProducts = \getDmsProducts();
        }

        // $posts = get_posts([
        //     'post_type' => 'product',
        //     'tax_query' => [
        //         [
        //             'taxonomy' => 'product_cat',
        //             'terms' => ['shop'],
        //             'field' => 'slug',
        //         ],
        //     ],
        //     'showposts' => -1,
        //     'fields' => 'ids',
        // ]);

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

    public static function findDefaultSize(ProductContract $product): string
    {
        $data = $product->getData();

        foreach ($data->colors as $color) {
            foreach ($color->sizes as $size) {
                if ($size->qty < 1) {
                    continue;
                }
                return str_replace('½', '5', $size->size);
            }
        }

        return '';
    }
}
