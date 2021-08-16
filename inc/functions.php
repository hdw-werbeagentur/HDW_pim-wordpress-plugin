<?php

use GuzzleHttp\Client;
use HDW\ProjectDmsImporter\Dms\DmsProduct;
use HDW\ProjectDmsImporter\Dms\DmsProductsCollection;
use HDW\ProjectDmsImporter\Dms\DmsLanguage;
use HDW\ProjectDmsImporter\Dms\DmsLanguagesCollection;
use HDW\ProjectDmsImporter\Import;

function getDmsRestUser(): string
{
    $options = get_option('hdw-dms-importer-settings');
    return $options['rest-username'] ?? '';
}

function getDmsRestPassword(): string
{
    $options = get_option('hdw-dms-importer-settings');
    return $options['rest-password'] ?? '';
}

function getDMSRestBase(): string
{
    $options = get_option('hdw-dms-importer-settings');
    return trailingslashit(esc_url_raw($options['rest-base'])) ?? '';
}

function getDMSApiToken(): string
{
    $options = get_option('hdw-dms-importer-settings');
    return trim($options['rest-api-token']) ?? '';
}

function getDmsProductsEndpoint(string $language): string
{
    $options = get_option('hdw-dms-importer-settings');
    return esc_url_raw(getDMSRestBase()) . $options['rest-products-endpoint'] . $language;
}

function getDmsProductEndpoint(string $id): string
{
    $options = get_option('hdw-dms-importer-settings');
    $endpoint = getDMSRestBase() . $options['rest-product-endpoint'];
    $endpoint = str_replace('{id}', $id, $endpoint);
    return esc_url_raw($endpoint);
}

function getDmsLanguagesEndpoint(): string
{
    $options = get_option('hdw-dms-importer-settings');
    return esc_url_raw(getDMSRestBase()) . $options['rest-languages-endpoint'];
}

function getDmsSelectedLanguage(): string
{
    $options = get_option('hdw-dms-importer-settings');
    return trim($options['rest-products-language']);
}

function getDmsProductStockEndpoint(string $id): string
{
    $options = get_option('hdw-dms-importer-settings');
    $endpoint = getDMSRestBase() . $options['rest-product-stock-endpoint'];
    $endpoint = str_replace('{id}', $id, $endpoint);
    return esc_url_raw($endpoint);
}

function getDmsProductStockCorrectionEndpoint(): string
{
    $options = get_option('hdw-dms-importer-settings');
    $endpoint = getDMSRestBase() . $options['rest-product-stock-correction-endpoint'];
    return esc_url_raw($endpoint);
}

function getDmsProducts(): DmsProductsCollection
{
    if (false === $collection = get_transient('logisoft-products-collection')) {
        $collection = (new DmsProductsCollection())->load();
        set_transient('logisoft-products-collection', $collection, MINUTE_IN_SECONDS * 15);

        foreach ($collection->get() as $product) {
            set_transient('logisoft-product-' . $product->getId(), $product, MINUTE_IN_SECONDS * 15);
        }
    }

    return $collection;
}

function getDmsProduct($id): DmsProduct
{
    if (false === $product = get_transient('logisoft-product-' . $id)) {
        $product = (new DmsProduct($id))->load();
        set_transient('logisoft-product-' . $id, $product, MINUTE_IN_SECONDS * 15);
    }

    return $product;
}

function getDmsLanguages(): DmsLanguagesCollection
{
    $collection = (new DmsLanguagesCollection())->load();

    return $collection;
}

function getProductsBySKU(string $sku, array $filter = []): array
{
    $args = [
        'post_type' => ['cpt_products'],
        'post_status' => 'any',
        'meta_query' => [
            [
                'key' => '_sku',
                'value' => $sku,
            ] 
        ],
        'orderby' => 'date',
        'order' => 'DESC',
    ];

    if (!empty($filter)) {
        $args = array_merge($args, $filter);
    }

    $products = get_posts($args);

    return $products;
}

function updateProducts()
{
    $products = \getDmsProducts();
    $productsCount = $products->getCount();

    if (empty($products)) {
        return;
    }

    // \Anni\Info('Produkt Import', 'Import gestartet (Cron)');
    // \Anni\Info('Produkt Import', sprintf('Importiere %d Produkte', $productsCount), ['count' => $productsCount]);

    Import::cleanShop();

    foreach ($products->get() as $product) {
        Import::importProduct($product);
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
            'post_status' => 'publish',
        ]);

    $postCount = count($posts);

    // if ($postCount != $productsCount) {
    //     \Anni\Warning('Produkt Import', sprintf('Es wurden %d von %d Produkte importiert', $postCount, $productsCount), [
    //             'products' => $productsCount,
    //             'posts' => $postCount,
    //         ]);
    // } else {
    //     \Anni\Info('Produkt Import', sprintf('Es wurden %d Produkte importiert', $postCount), ['count' => $postCount]);
    // }

    // \Anni\Info('Produkt Import', 'Import beendet');
}