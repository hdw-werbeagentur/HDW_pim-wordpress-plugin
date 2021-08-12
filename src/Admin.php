<?php

namespace HDW\ProjectDmsImporter;

/**
 * Service example
 */
class Admin
{
    /**
     * Register hooks
     *
     * @return void
     **/
    public function register(): void
    {
        \add_action('admin_menu', [$this, 'addAdminMenu']);
        \add_action('admin_print_styles-tools_page_logisoft-importer', [$this, 'enqueueAssets']);
    }

    /**
     * Add event columns
     **/
    public function addAdminMenu(): void
    {
        \add_management_page(__('DMS Importer', 'hdw-dms-importer'), __('DMS Importer', 'hdw-dms-importer'), 'edit_posts', 'logisoft-importer', [$this, 'page']);
    }

    /**
     * Enqueue assets
     *
     * @return void
     **/
    public function enqueueAssets(): void
    {
        \wp_enqueue_style('hdw-dms-importer', plugins_url('../assets/css/admin.css', __FILE__));
        \wp_enqueue_script('hdw-dms-importer', plugins_url('../assets/js/admin.js', __FILE__));
    }


    /**
     * Admin page
     *
     * @return void
     **/
    public function page(): void
    {
?>
        <div class="wrap">
            <h1><?php _e('DMS Importer') ?></h1>

            <?php
            $tab = $_GET['tab'] ?? 'dashboard';
            $this->tabNavigation($tab);

            switch ($tab) {
                case 'dashboard':
                    $this->tabDashboard();
                    break;
                case 'importer':
                    $this->tabImport();
                    break;
                case 'settings':
                    $this->tabSettings();
                    break;
            } ?>
        </div>
    <?php
    }


    /**
     * Display tabs
     *
     * @return void
     **/
    public function tabNavigation(): void
    {
        $tab = $_GET['tab'] ?? 'dashboard'; ?>
        <h2 class="nav-tab-wrapper">
            <a href="tools.php?page=logisoft-importer" class="nav-tab <?php echo $tab == 'dashboard' ? 'nav-tab-active' : ''; ?>"><?php _e('Dashboard') ?></a>
            <a href="tools.php?page=logisoft-importer&tab=importer" class="nav-tab <?php echo $tab == 'importer' ? 'nav-tab-active' : ''; ?>"><?php _e('Importer') ?></a>
            <a href="tools.php?page=logisoft-importer&tab=settings" class="nav-tab <?php echo $tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Settings') ?></a>
        </h2>
    <?php
    }


    /**
     * Tab: Dashboard
     *
     * @return void
     **/
    protected function tabDashboard(): void
    {
    ?>
        <h2><?php _e('Dashboard', 'hdw-dms-importer') ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _e('Anzahl Kollektions Produkte', 'hdw-dms-importer') ?></th>
                <td>
                    <?php
                    echo count(\get_posts([
                        'post_type' => 'product',
                        'tax_query' => [
                            [
                                'taxonomy' => 'product_cat',
                                'terms' => 'kollektion',
                                'field' => 'slug',
                            ]
                        ],
                        'fields' => 'ids',
                        'showposts' => -1,
                    ])); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Anzahl Shop Produkte', 'hdw-dms-importer') ?></th>
                <td>
                    <?php
                    echo count(\get_posts([
                        'post_type' => 'product',
                        'tax_query' => [
                            [
                                'taxonomy' => 'product_cat',
                                'terms' => 'shop',
                                'field' => 'slug',
                            ],
                        ],
                        'fields' => 'ids',
                        'showposts' => -1,
                    ])); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Anzahl Händler Produkte', 'hdw-dms-importer') ?></th>
                <td>
                    <?php
                    echo count(\get_posts([
                        'post_type' => 'product',
                        'tax_query' => [
                            [
                                'taxonomy' => 'product_cat',
                                'terms' => 'haendlerbereich',
                                'field' => 'slug',
                            ]
                        ],
                        'fields' => 'ids',
                        'showposts' => -1,
                    ])); ?>
                </td>
            </tr>
        </table>
    <?php
    }

    /**
     * undocumented function summary
     *
     * Undocumented function long description
     *
     * @param Type $var Description
     * @return type
     * @throws conditon
     **/
    public function tabSKU()
    {
    ?>
        <h2><?php _e('Artikelnummern') ?></h2>

        <?php
        if (wp_verify_nonce($_POST['waldlaeufer-logisoft-sku-nonce'], 'waldlaeufer-logisoft-sku')) {
            $products = get_posts([
                'post_type' => ['product', 'product_variation'],
                'meta_key' => '_sku',
                'post_status' => 'any',
                'showposts' => -1,
            ]); ?>
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <th><?php _e('#') ?></th>
                    <th><?php _e('Produkt', 'hdw-dms-importer') ?></th>
                    <th><?php _e('Artikelnummer vorher', 'hdw-dms-importer') ?></th>
                    <th><?php _e('Artikelnummer nachher', 'hdw-dms-importer') ?></th>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    foreach ($products as $product) {
                        $sku = get_post_meta($product->ID, '_sku', true);
                        $skuUpdate = \transformSKU($sku);
                        if ($sku == $skuUpdate) {
                            continue;
                        } ?>
                        <tr>
                            <td><?= $i ?></td>
                            <th><?= $product->post_title ?></th>
                            <td><?= $sku ?></td>
                            <td><?= $skuUpdate ?></td>
                        </tr>
                    <?php
                        if (isset($_POST['update-sku'])) {
                            $sku = update_post_meta($product->ID, '_sku', $skuUpdate);
                        }
                        $i++;
                    } ?>
                </tbody>
            </table>
        <?php
        }

        if (empty($_POST)) {
        ?>
            <form method="post" action="">
                <p>
                    <label><input type="checkbox" name="update-sku"> <?php _e('Artikelnummern korrigieren', 'hdw-dms-importer') ?></label>
                </p>
                <p>
                    <button class="button button-primary" type="submit"><?php _e('Artikelnummern prüfen', 'hdw-dms-importer') ?></button>
                </p>
                <?php wp_nonce_field('waldlaeufer-logisoft-sku', 'waldlaeufer-logisoft-sku-nonce') ?>
            </form>
            <?php
        }
    }

    /**
     * undocumented function summary
     *
     * Undocumented function long description
     *
     * @param Type $var Description
     * @return type
     * @throws conditon
     **/
    public function tabVariations()
    {
        global $wpdb;

        if ($_REQUEST['action'] == 'delete-variation' && isset($_REQUEST['variation']) && wp_verify_nonce($_REQUEST['nonce'], 'delete-variation-' . $_REQUEST['variation'])) {
            $variation = wp_delete_post($_REQUEST['variation']);
            if (is_a($variation, \WP_POST::class)) {
            ?>
                <div class="notice notice-success">
                    <p><?php printf(__('Variation %s gelöscht'), $variation->post_title) ?></p>
                </div>
            <?php
            } else {
            ?>
                <div class="error">
                    <p><?php _e('Variation konnte nicht gelöscht') ?></p>
                </div>
                <?php
            }
        }

        if (!empty($_POST) && wp_verify_nonce($_POST['nonce'], 'bulk-action') && $_POST['action'] == 'delete-selected') {
            foreach ($_POST['post'] as $post) {
                $variation = wp_delete_post($post);
                if (is_a($variation, \WP_POST::class)) {
                ?>
                    <div class="notice notice-success">
                        <p><?php printf(__('Variation %s gelöscht'), $variation->post_title) ?></p>
                    </div>
                <?php
                } else {
                ?>
                    <div class="error">
                        <p><?php _e('Variation konnte nicht gelöscht') ?></p>
                    </div>
        <?php
                }
            }
        }


        $treshhold = 20;
        $i = 0;
        $posts = get_posts([
            'post_type' => 'product',
            'tax_query' => [
                [
                    'taxonomy' => 'product_cat',
                    'terms' => ['shop'],
                    'field' => 'slug'
                ]
            ],
            'showposts' => -1,
        ]); ?>
        <h2><?php _e('Variations', 'woocommerce') ?></h2>

        <form method="post" action="tools.php?page=logisoft-importer&tab=variations">
            <?php wp_nonce_field('bulk-action', 'nonce') ?>
            <p>
                <button name="action" value="<?php _e('delete-selected') ?>" type="submit" class="button"><?php _e('Ausgewählte Produkte löschen') ?></button>
            </p>
            <table class="wp-list-table widefat striped posts">
                <thead>
                    <tr>
                        <th><input type="checkbox"></th>
                        <td>#</td>
                        <th><?php _e('ID') ?></th>
                        <th><?php _e('Title') ?></th>
                        <th><?php _e('SKU', 'woocommerce') ?></th>
                        <th><?php _e('Orders', 'woocommerce') ?></th>
                    </tr>
                </thead>
                <?php
                foreach ($posts as $post) {
                    $variations = get_posts([
                        'post_type' => 'product_variation',
                        'post_parent' => $post->ID,
                        'showposts' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC',
                    ]);
                    $count = count($variations);
                    $variationTitles = wp_list_pluck($variations, 'post_title');
                    $unique = array_unique($variationTitles);
                    $duplicates = array_diff_assoc($variationTitles, $unique);

                    if (count($duplicates) == 0) {
                        continue;
                    }

                    $j = 0;
                    $i++ ?>
                    <tbody>
                        <tr>
                            <th></th>
                            <th><strong><?= $i ?></strong></th>
                            <th><strong><?= $post->ID ?></strong></th>
                            <th><strong><a href="<?= get_permalink($post->ID) ?>"><?= $post->post_title ?></a></strong></th>
                            <th colspan="3"><strong><?= get_post_meta($post->ID, '_sku', true) ?></strong></th>
                        </tr>
                        <?php
                        foreach ($variations as $variation) {
                            $sql = $wpdb->prepare("SELECT * FROM dbwl2_woocommerce_order_itemmeta WHERE meta_key='_variation_id' and meta_value = %d ", $variation->ID);
                            $orders = $wpdb->get_results($sql);
                            if (count($orders) == 0) {
                                // continue;
                            }
                            $j++; ?>
                            <tr>
                                <th><input value="<?= $variation->ID ?>" id="post-<?= $variation->ID ?>" name="post[]" type="checkbox"></th>
                                <td><?= $i . '.' . $j ?></td>
                                <td><?= $variation->ID ?></td>
                                <td>
                                    <label for="post-<?= $variation->ID ?>">
                                        <?= str_replace($post->post_title . ' - ', '', $variation->post_title) ?>
                                    </label>
                                </td>
                                <td>
                                    <label for="post-<?= $variation->ID ?>">
                                        <?= get_post_meta($variation->ID, '_sku', true) ?>
                                    </label>
                                </td>
                                <td><?= count($orders) > 0 ? count($orders) : '' ?></td>
                            </tr>
                        <?php
                        } ?>
                    </tbody>
                <?php
                } ?>
            </table>
        </form>
    <?php
    }


    /**
     * Tab: Import
     *
     * @return void
     **/
    protected function tabStock(): void
    {
    ?>
        <h2><?php _e('Lagerbestand') ?></h2>

        <?php
        if (wp_verify_nonce($_POST['waldlaeufer-logisoft-stock-nonce'], 'waldlaeufer-logisoft-stock')) {
        ?>
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <th><?php _e('#') ?></th>
                    <th><?php _e('Title') ?></th>
                    <th><?php _e('ID') ?></th>
                    <th><?php _e('Count') ?></th>
                    <th><?php _e('Status') ?></th>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    $originProducts = getDmsProducts();
                    foreach ($originProducts->get() as $originProduct) {
                        $products = \getProductsBySKU($originProduct->getSku());
                        updateProductStockSKU($originProduct->getSku(), $originProduct->getStockSum());
                        foreach ($products as $product) {
                    ?>
                            <tr>
                                <th><?= $i ?></th>
                                <th><?= edit_post_link($product->post_title, '', '', $product) ?></th>
                                <td><?= $originProduct->getId() ?></td>
                                <td><?= $originProduct->getStockSum() ?></td>
                                <td><?= $originProduct->getStockStatus() ?></td>
                            </tr>
                            <?php
                            foreach ($originProduct->getSizeDetails() as $size) {
                                $productSizes = \getProductsBySKU($size->id);
                                foreach ($productSizes as $product) {
                                    updateProductStockSKU($size->id, $size->qty); ?>
                                    <tr>
                                        <th><?= $i ?></th>
                                        <th><?= $product->post_title ?></th>
                                        <td><?= $size->id ?></td>
                                        <td><?= $size->qty ?></td>
                                        <td><?= $size->qty > 0 ? 'instock' : 'outofstock' ?></td>
                                    </tr>
                    <?php
                                    $i++;
                                }
                            }
                            $i++;
                        }
                    } ?>
                </tbody>
            </table>
        <?php
        }

        if (empty($_POST)) {
        ?>
            <form method="post" action="">
                <p>
                    <button class="button button-primary" type="submit"><?php _e('Lagerbestand aktualisieren', 'hdw-dms-importer') ?></button>
                </p>
                <?php wp_nonce_field('waldlaeufer-logisoft-stock', 'waldlaeufer-logisoft-stock-nonce') ?>
            </form>
        <?php
        }
    }


    /**
     * Tab: Import
     *
     * @return void
     **/
    protected function tabImport(): void
    {
        ?>
        <h2><?php _e('Import') ?></h2>

        <?php
        if (wp_verify_nonce($_POST['preview-waldlaeufer-logisoft-import-nonce'], 'preview-waldlaeufer-logisoft-import')) {
            $this->previewImport();
        } ?>

        <?php if (empty($_POST)) {
        ?>
            <form method="post" action="">
                <p>
                    <button class="button button-primary" type="submit"><?php _e('Update Preview', 'preview-waldlaeufer-logisoft-import') ?></button>
                </p>
                <?php wp_nonce_field('preview-waldlaeufer-logisoft-import', 'preview-waldlaeufer-logisoft-import-nonce') ?>
            </form>
        <?php
        }
    }


    /**
     * Tab: Settings
     *
     * @return void
     **/
    protected function tabSettings(): void
    {
        if (!empty($_POST) && \wp_verify_nonce($_POST['hdw-dms-importer-settings-nonce'], 'save-hdw-dms-importer-settings')) {
            $options = [
                'rest-username' => $_POST['rest-username'],
                'rest-password' => $_POST['rest-password'] && \str_repeat('*', 8) != $_POST['rest-password'] ? $_POST['rest-password'] : getDmsRestPassword(),
                'rest-base' => trailingslashit(esc_url_raw($_POST['rest-base'])),
                'rest-api-token' => sanitize_text_field($_POST['rest-api-token']),
                'rest-products-endpoint' => sanitize_text_field($_POST['rest-products-endpoint']),
                'rest-product-endpoint' => sanitize_text_field($_POST['rest-product-endpoint']),
                // 'rest-product-stock-endpoint' => sanitize_text_field($_POST['rest-product-stock-endpoint']),
                // 'rest-product-stock-correction-endpoint' => sanitize_text_field($_POST['rest-product-stock-correction-endpoint']),
            ];
            \update_option('hdw-dms-importer-settings', $options)
        ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Saved.') ?></p>
            </div>
        <?php
        }
        $options = \get_option('hdw-dms-importer-settings'); ?>

        <h2><?php _e('REST Settings', 'hdw-dms-importer') ?></h2>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><?php _e('Username') ?></th>
                    <td>
                        <input type="text" class="regular-text" name="rest-username" value="<?= getDmsRestUser() ?>" />
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Password') ?></th>
                    <td>
                        <input type="password" class="regular-text" name="rest-password" value="<?php if (getDmsRestPassword()) {
                                                                                                    echo \str_repeat('*', 8);
                                                                                                } ?>" />
                    </td>
                </tr>
                <tr>
                    <th><?php _e('REST API Base URL', 'hdw-dms-importer') ?></th>
                    <td>
                        <input type="url" class="regular-text" name="rest-base" value="<?= getDMSRestBase() ?>" />
                    </td>
                </tr>
                <tr>
                    <th><?php _e('REST API Token', 'hdw-dms-importer') ?></th>
                    <td>
                        <input type="text" class="regular-text" name="rest-api-token" value="<?= getDMSApiToken() ?>" />
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Products Endpoint', 'hdw-dms-importer') ?></th>
                    <td>
                        <?= getDMSRestBase() ?><input type="text" name="rest-products-endpoint" value="<?= esc_attr($options['rest-products-endpoint']) ?>" />
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Product Endpoint', 'hdw-dms-importer') ?></th>
                    <td>
                        <?= getDMSRestBase() ?><input type="text" name="rest-product-endpoint" value="<?= esc_attr($options['rest-product-endpoint']) ?>" /><br>
                        <small><?php _e('{id} is replaced with the product id from erp') ?></small>
                    </td>
                </tr>
                <!-- <tr>
                    <th><?php _e('Product Stock Endpoint', 'hdw-dms-importer') ?></th>
                    <td>
                        <?= getDMSRestBase() ?><input type="text" name="rest-product-stock-endpoint" value="<?= esc_attr($options['rest-product-stock-endpoint']) ?>" /><br>
                        <small><?php _e('{id} is replaced with the product id from erp') ?></small>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Stock Correction Endpoint', 'hdw-dms-importer') ?></th>
                    <td>
                        <?= getDMSRestBase() ?><input type="text" name="rest-product-stock-correction-endpoint" value="<?= esc_attr($options['rest-product-stock-correction-endpoint']) ?>" /><br>
                    </td>
                </tr> -->
            </table>
            <p>
                <button class="button button-primary" type="submit"><?php _e('Save') ?></button>
            </p>
            <?php wp_nonce_field('save-hdw-dms-importer-settings', 'hdw-dms-importer-settings-nonce') ?>
        </form>
    <?php
    }

    /**
     * Preview import
     *
     * @return void
     **/
    public function previewImport(): void
    {
        $products = \getDmsProducts();
        // \Anni\Info('Produkt Import', 'Erstelle Vorschau von Logisoft Produkten', [
        //     'count' => $products->getCount(),
        // ]); 
        ?>
        <div class="import-timer" id="import-timer">00:00</div>
        <progress id="product-progress" class="product-progress" value="0" max="<?= $products->getCount() ?>">0</progress>
        <table class="form-table">
            <tr>
                <th><?php _e('Filter', 'hdw-dms-importer-settings-nonce') ?></th>
                <td>
                    <button class="button button-filter" id="select-all"><?php _e('Alle', 'hdw-dms-importer-settings-nonce') ?></button>
                    <button class="button button-filter" id="out-of-sync-selection"><?php _e('Out-of-Sync', 'hdw-dms-importer-settings-nonce') ?></button>
                    <button class="button button-filter" id="invert-selection"><?php _e('Invertieren', 'hdw-dms-importer-settings-nonce') ?></button>
                    <button class="button button-filter" id="reset-selection" type="reset"><?php _e('Reset', 'hdw-dms-importer-settings-nonce') ?></button>
                </td>
            </tr>
            <tr>
                <th><?php _e('Products count', 'hdw-dms-importer-settings-nonce') ?></th>
                <td>
                    <?= $products->getCount() ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Products', 'hdw-dms-importer-settings-nonce') ?></th>
                <td>
                    <div class="products">
                        <?php
                        foreach ($products->get() as $product) {
                            $colorIndex = 0;
                            $classes = ['product'];

                            $args = [
                                'post_status' => 'publish',
                                'tax_query' => [
                                    'taxonomy' => 'product_cat',
                                    'terms' => ['shop'],
                                    'field' => 'slug',
                                ]
                            ];

                            $postProducts = \getProductsBySKU($product->getSku(), $args);

                            if (!empty($postProducts)) {
                                $classes[] = 'product--in-shop';
                            } else {
                                $args['post_status'] = 'draft';
                                $postDraftProduct = \getProductsBySKU($product->getSku(), $args);
                                $classes[] = (!empty($postDraftProduct)) ? 'product--is-draft' : '';
                            }

                            $class = 'product--is-out-of-sync';
                            $hash = $product->getHash();
                            foreach ($postProducts as $postProduct) {
                                if ($hash == get_post_meta($postProduct->ID, '_import-hash', true)) {
                                    $class = 'product--is-in-sync';
                                }
                            }
                            $classes[] = $class; ?>
                            <div class="<?= implode(' ', $classes) ?>">
                                <input checked type="checkbox" class="product-input" name="product" id="product-<?= $product->getId() ?>" value="<?= $product->getId() ?>" checked />
                                <label class="product-label" for="product-<?= $product->getId() ?>">
                                    <?= $product->getModel() ?><br>
                                    <?php
                                    $colorIndex = 0;
                                    foreach ($product->getColors() as $color) {
                                        $product->setColor($colorIndex); ?>
                                        <small class="product-sku"><?= $product->getSku() ?> - <?= $product->getMaterialColorName() ?></small><br>
                                    <?php
                                        $colorIndex++;
                                    } ?>

                                </label>
                            </div>
                        <?php
                        } ?>
                    </div>
                </td>
            </tr>
        </table>
        <p>
            <button id="product-import" class="button button-primary" type="submit"><?php _e('Import') ?></button>
        </p>
<?php wp_nonce_field('import-waldlaeufer-logisoft-import', 'import-waldlaeufer-logisoft-import-nonce');
    }
}
