<?php

namespace HDW\ProjectDmsImporter;

use HDW\ProjectDmsImporter\Contracts\ProductContract;
use \WC_Product_Variable;
use \WC_Product_Variation;
use \WC_Product;
use \WC_Product_Attribute; 
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
            //     sprintf('Produkt import fehlgeschlagen %s (%s) importiert', $product->getModel(), $product->getSku()),
            //     [
            //         'product' => $product->getModel(),
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
            \Anni\Debug('Produkt Import', sprintf('%d mögliche Produkte für %s', $count, $sku), [
                'sku' => $sku,
                'products' => $products,
                'count' => $count,
            ]);
        }

        if (count($products) == 0) {
            return 0;
        }

        return $products[0]->ID;
    }

    /**
     * Create import product
     **/
    public static function create(ProductContract $product): WC_Product
    {
        $searchResult = Import::findBySku($product->getSku(), [
            'tax_query' => [
                'taxonomy' => 'product_cat',
                'terms' => ['shop'],
                'field' => 'slug',
            ],
        ]);

        if ($searchResult) {
            // \Anni\Info('Produkt Import', sprintf('Aktualisiere Produkt %s (%s)', $product->getModel(), $product->getSku()), [
            //     'id' => $searchResult,
            //     'name' => $product->getModel(),
            //     'sku' => $product->getSku(),
            // ]);
        } else {
            // \Anni\Info('Produkt Import', sprintf('Erstelle neues Produkt für %s (%s)', $product->getModel(), $product->getSku()), [
            //     'name' => $product->getModel(),
            //     'sku' => $product->getSku(),
            // ]);
        }

        $currentProduct = $searchResult ?? null;

        if ($product->hasVariations()) {
            $importProduct = new WC_Product_Variable($currentProduct);
        } else {
            $importProduct = new WC_Product($currentProduct);
        }

        return $importProduct;
    }

    /**
     * Create import project
     **/
    public static function createVariation($parentId, array $variation): WC_Product_Variation
    {
        $products = \getProductsBySKU($variation['sku'], ['post_parent' => $parentId]);
        $variationId = !empty($products) ? $products[0]->ID : 0;

        $productVariation = new WC_Product_Variation($variationId);

        return $productVariation;
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
            // \Anni\Info('Produkt Import', sprintf('Überspringe Produkt %s (%s)', $product->getModel(), $product->getSku()), [
            //     'name' => $product->getModel(),
            //     'sku' => $product->getSku(),
            // ]);
            wp_update_post([
                'ID' => $skipUpdate,
                'post_status' => 'publish'
            ]);
            return [$skipUpdate];
        }

        $postIds = [];
        $colorIndex = 0;
        foreach ($product->getColors() as $color) {
            $postId = null;
            $product->setColor($colorIndex);

            $importProduct = Import::create($product);

            $importProduct->set_name($product->getModel());

            // product image 
            $importProduct->set_image_id($product->getImage()); 

            // product gallery
            $importProduct->set_gallery_image_ids($product->getImages());

            $importProduct->set_status('publish');
            $importProduct->set_description($product->getDescription());
            $importProduct->set_catalog_visibility('visible');
            $importProduct->set_stock_quantity($product->getStockSum());
            $importProduct->set_stock_status($product->getStockSum() > 0 ? 'instock' : 'outofstock');
            $importProduct->set_sku($product->getSku()); // We can't use set_sku as SKU has to be unique
            $importProduct->set_weight($product->getWeight());

            // Sizes
            $attribute = new WC_Product_Attribute();
            $attribute->set_id(\wc_attribute_taxonomy_id_by_name('pa_size')); //if passing the attribute name to get the ID
            $attribute->set_name('pa_size'); //attribute name
            $attribute->set_options(array_map(function ($value) {
                return str_replace('½', ',5', $value);
            }, $product->getSizes())); // attribute value

            $attribute->set_position(1); //attribute display order
            $attribute->set_visible(1); //attribute visiblity
            $attribute->set_variation(1); //to use this attribute as variation or not
            $attributes[] = $attribute;
            $importProduct->set_attributes($attributes);

            $defaultSize = Import::findDefaultSize($product);
            if ($defaultSize) {
                $importProduct->set_default_attributes([
                    'pa_size' => $defaultSize
                ]);
            }

            $postId = $importProduct->save();
            $postIds[] = $postId;

            if ($postId) {
                if (function_exists('update_field')) {
                    \update_field(1219, implode(', ', $product->getAttribute('Material')), $postId);
                    foreach ($product->getColorNames() as $productId => $color) {
                        \update_field(1220, $color, $postId);
                    }
                    \update_field(1221, implode(', ', $product->getAttribute('Schuhtyp')), $postId);
                    \update_field(1222, implode(', ', $product->getAttribute('Sohle')), $postId);
                    $sizes = count($product->getSizes()) ? reset($product->getSizes()) . ' - ' . end($product->getSizes()) : reset($product->getSizes());
                    $sizes = str_replace('½', ',5', $sizes);
                    \update_field(1223, $sizes, $postId);
                    \update_field(539565, $product->getMaterialColorName(), $postId);
                    \update_field(546674, $product->getSchuheDeAvailable($product), $postId); // schuhe de checkbox
                    \update_post_meta($postId, '_import-hash', Import::createHash($product));
                }
                \wp_set_object_terms($postId, $product->getCategories(), 'product_cat');

                if ($product->hasVariations()) {
                    Import::importVariations($postId, $importProduct, $product);
                }
            }

            $colorIndex++;
        }

        return $postIds;
    }

    /**
     * Import a single product
     *
     * @param int $importProductId Import product ID
     * @param WC_Product $importProduct Import product object
     * @param ProductContract $product Product to import
     * @return int
     **/
    public static function importVariations(int $importProductId, WC_Product $importProduct, ProductContract $product): int
    {
        // Add variations
        $variations = [];
        foreach ($product->getSizeDetails() as $size) {
            $variations[] = [
                "regular_price" => $size->price,
                "price"         => $size->price,
                "sku"           => \transformSKU($size->id),
                "attributes"    => [
                    [
                        "name"   => "size",
                        "option" => str_replace('½', ',5', $size->size)
                    ]
                ],
                "manage_stock"   => 1,
                "stock_quantity" => $size->qty,
                "stock_location" => $size->sizeStockLocation,
                "ean"           => [
                    "gtin"      => $size->ean
                ]
            ];
        }

        foreach ($variations as $variation) {
            $productVariation = Import::createVariation($importProductId, $variation);
            $productVariation->set_parent_id($importProductId);
            $productVariation->set_price($variation["price"]);
            $productVariation->set_regular_price($variation["regular_price"]);
            // $productVariation->set_sku($variation["sku"]); // We can't use set_sku as SKU has to be unique
            $productVariation->set_manage_stock($variation["manage_stock"]);
            $productVariation->set_stock_quantity($variation["stock_quantity"]);
            $productVariation->set_stock_status($variation["stock_quantity"] ? 'instock' : 'outofstock'); // in stock or out of stock value
            $var_attributes = [];
            foreach ($variation["attributes"] as $vattribute) {
                $taxonomy = "pa_" . \wc_sanitize_taxonomy_name(stripslashes($vattribute["name"])); // name of variant attribute should be same as the name used for creating product attributes
                $attr_val_slug =  \wc_sanitize_taxonomy_name(stripslashes($vattribute["option"]));
                $var_attributes[$taxonomy] = $attr_val_slug;
            }
            $productVariation->set_attributes($var_attributes);
            $variationId = $productVariation->save();

            if ($variationId) {
                update_post_meta($variationId, '_sku', $variation["sku"]);
                update_post_meta($variationId, '_stock_location', $variation["stock_location"]);
                update_post_meta($variationId, '_woocommerce_gpf_data', $variation["ean"]);
            }
        }

        // Swatches
        update_post_meta($importProductId, '_swatch_type', 'default');
        $name = 'pa_size';
        $key = md5(sanitize_title($name));
        $key_attr = md5(str_replace('-', '_', sanitize_title($name)));
        foreach ($product->getSizes() as $size) {
            $attributes[md5($size)] = [
                'type' => 'color',
                'color' => '#FFFFFF',
                'image' => '0',
            ];
        }
        $swatches = [
            $key => [
                'type' => 'radio',
                'layout' => 'default',
                'size' => 'swatches_image_size',
                'attributes' => $attributes,
            ],
        ];
        update_post_meta($importProductId, '_swatch_type_options', $swatches);

        return $importProductId;
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
            \Anni\Warning('Produkt Import', sprintf('Import Hash Duplikat %s (%s)', $product->getModel(), $product->getSku()), [
                'name' => $product->getModel(),
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

            if (!empty($skipProducts)) {
                foreach ($skipProducts->get() as $skipProduct) {
                    $colorIndex = 0;
                    foreach ($skipProduct->getColors() as $color) {
                        $skipProduct->setColor($colorIndex);
                        $skip[] = $skipProduct->getSku();
                        $colorIndex++;
                    }
                }
            }
        }

        $posts = get_posts([
            'post_type' => 'product',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'terms' => ['shop'],
                    'field' => 'slug',
                ],
            ],
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
