<?php

use GuzzleHttp\Client;
use HDW\ProjectDmsImporter\Dms\DmsProduct;
use HDW\ProjectDmsImporter\Dms\DmsProductsCollection;
use HDW\ProjectDmsImporter\Dms\DmsLanguage;
use HDW\ProjectDmsImporter\Dms\DmsLanguagesCollection;
use HDW\ProjectDmsImporter\Dms\DmsImage;
use HDW\ProjectDmsImporter\Dms\DmsImagesCollection;
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
    return trailingslashit(esc_url_raw($options['rest-base'] ?? ''));
}

function getDMSApiToken(): string
{
    $options = get_option('hdw-dms-importer-settings');
    return trim($options['rest-api-token']?? '');
}

function getDmsProductsEndpoint(string $language): string
{
    $options = get_option('hdw-dms-importer-settings');
    return esc_url_raw(getDMSRestBase()) . $options['rest-products-endpoint'] . $language;
}

function getDmsProductEndpoint(string $id, string $language): string
{
    $options = get_option('hdw-dms-importer-settings');
    $endpoint = getDMSRestBase() . $options['rest-product-endpoint'];
    $endpoint = str_replace('{id}', $id, $endpoint);
    $endpoint = str_replace('{language}', $language, $endpoint);
    return esc_url_raw($endpoint);
}

function getDmsLanguagesEndpoint(): string
{
    $options = get_option('hdw-dms-importer-settings');
    return esc_url_raw(getDMSRestBase()) . $options['rest-languages-endpoint'];
}

function getDmsImagesEndpoint(): string
{
    $options = get_option('hdw-dms-importer-settings');
    return esc_url_raw(getDMSRestBase()) . $options['rest-images-endpoint'];
}

function getDmsSelectedLanguage(): string
{
    $options = get_option('hdw-dms-importer-settings');

    return trim($options['rest-products-language'] ?? '');
}

function getFileRootPath(): string
{
    $options = get_option('hdw-dms-importer-settings');

    return trailingslashit(esc_url_raw($options['rest-file-root-path'] ?? '')) ?? '';
}

function getDmsSelectedBrand(): string
{
    $options = get_option('hdw-dms-importer-settings');

    return trim($options['rest-products-brand'] ?? '');
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

function getDmsProduct($id, $language): DmsProduct
{
    if (false === $product = get_transient('logisoft-product-' . $id . '-' . $language)) {
        $product = (new DmsProduct($id, $language))->load();
        set_transient('logisoft-product-' . $id . '-' . $language, $product, MINUTE_IN_SECONDS * 15);
    }

    return $product;
}

function getDmsLanguages(): DmsLanguagesCollection
{
    $collection = (new DmsLanguagesCollection())->load();

    return $collection;
}

function getDmsImageSizes(): DmsImagesCollection
{
    $collection = (new DmsImagesCollection())->load();

    return $collection;
}

function getProductsBySKU(string $sku, array $filter = []): array
{
    $args = [
        'post_type' => 'cpt_products',
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

    // $posts = get_posts([
    //         'post_type' => 'cpt_products',
    //         'showposts' => -1,
    //         'fields' => 'ids',
    //         'post_status' => 'publish',
    //     ]);

    // $postCount = count($posts);

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

add_action('single_product_download_after', 'add_aws_image_to_backend_product');

function add_aws_image_to_backend_product($postId)
{
    echo '<label for="image-thumbnail">' . __('Thumbnail', 'hdw-dms-importer') . '</label>';

    $thumbnail = get_post_meta($postId, '_thumbnail', true);

    if ($thumbnail) {
        echo '<img src="' . $thumbnail . '" alt="">';
    }

    $dmsRestBase = \getDMSRestBase();

    if ($dmsRestBase) {
        echo '<br><a href="' . str_replace('/api/', '', $dmsRestBase) . '" target="_blank">' . __('Edit Product in the DMS', 'hdw-dms-importer') . '</a>';
    }
}



add_action('archive_product_page_overview_image', 'add_aws_image_to_product_overview_page');

function add_aws_image_to_product_overview_page($postId)
{
    // get config value
    $options = \get_option('hdw-dms-importer-settings');

    if ($overviewImage = $options['rest-product-overview-image']) {
        if ($sizes = get_post_meta($postId, '_thumbnails', true)) {
?>
            <img src="<?= $sizes[$overviewImage]; ?>" alt="<?= get_the_title($postId); ?>">
        <?php
        }
    } elseif (get_post_meta($postId, '_thumbnail', true)) { ?>
        <img src="<?= get_post_meta($postId, '_thumbnail', true); ?>" alt="<?= get_the_title($postId); ?>">
        <?php
    }
}

add_action('single_product_page_detail_image', 'add_aws_image_to_product_single_page');

function add_aws_image_to_product_single_page($postId)
{
    // get config value
    $options = \get_option('hdw-dms-importer-settings');

    if ($overviewImage = $options['rest-product-detail-page-image']) {
        if ($sizes = get_post_meta($postId, '_thumbnails', true)) {
        ?>
            <img src="<?= $sizes[$overviewImage]; ?>" alt="<?= get_the_title($postId); ?>">
        <?php
        }
    } elseif (get_post_meta($postId, '_thumbnail', true)) { ?>
        <img src="<?= get_post_meta($postId, '_thumbnail', true); ?>" alt="<?= get_the_title($postId); ?>">
<?php
    }
}

/**
 * Delete all transients from the database whose keys have a specific prefix.
 *
 * @param string $prefix The prefix. Example: 'my_cool_transient_'.
 */
function delete_transients_with_prefix($prefix)
{
    foreach (get_transient_keys_with_prefix($prefix) as $key) {
        delete_transient($key);
    }
}

/**
 * Gets all transient keys in the database with a specific prefix.
 *
 * Note that this doesn't work for sites that use a persistent object
 * cache, since in that case, transients are stored in memory.
 *
 * @param  string $prefix Prefix to search for.
 * @return array          Transient keys with prefix, or empty array on error.
 */
function get_transient_keys_with_prefix($prefix)
{
    global $wpdb;

    $prefix = $wpdb->esc_like('_transient_' . $prefix);
    $sql    = "SELECT 'gcp_options' FROM $wpdb->options WHERE 'gcp_options' LIKE '%s'";
    $keys   = $wpdb->get_results($wpdb->prepare($sql, $prefix . '%'), ARRAY_A);

    if (is_wp_error($keys)) {
        return [];
    }

    return array_map(function ($key) {
        // Remove '_transient_' from the option name.
        return ltrim($key['gcp_options'], '_transient_');
    }, $keys);
}