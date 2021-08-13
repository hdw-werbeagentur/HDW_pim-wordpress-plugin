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
        } 
        ?>

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
            // delete transients if language have changed
            if(\getDmsSelectedLanguage() != sanitize_text_field($_POST['rest-products-language'])) {
                delete_transient('logisoft-products-collection');
            }
            
            $options = [
                'rest-username' => $_POST['rest-username'],
                'rest-password' => $_POST['rest-password'] && \str_repeat('*', 8) != $_POST['rest-password'] ? $_POST['rest-password'] : getDmsRestPassword(),
                'rest-base' => trailingslashit(esc_url_raw($_POST['rest-base'])),
                'rest-api-token' => sanitize_text_field($_POST['rest-api-token']),
                'rest-products-endpoint' => sanitize_text_field($_POST['rest-products-endpoint']),
                'rest-product-endpoint' => sanitize_text_field($_POST['rest-product-endpoint']),
                'rest-products-language' => sanitize_text_field($_POST['rest-products-language']),
                'rest-languages-endpoint' => sanitize_text_field($_POST['rest-languages-endpoint']),
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
                <tr>
                    <th><?php _e('Languages Endpoint', 'hdw-dms-importer') ?></th>
                    <td>
                        <?= getDMSRestBase() ?><input type="text" name="rest-languages-endpoint" value="<?= esc_attr($options['rest-languages-endpoint']) ?>" /><br>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Product Language', 'hdw-dms-importer') . '<br>';

                        $contentLanguages = \getDmsLanguages();
                        $languagesCount = $contentLanguages->getCount(); ?>
                        (<?= $languagesCount . ' ' . __('languages', 'hdw-dms-importer'); ?>)
                    </th>
                    <td>
                        <?php
                        if($contentLanguages) { ?>
                            <select name="rest-products-language" id="rest-product-language">
                                <option name='select' <?php if(esc_attr($options['rest-products-language']) == 'select') echo 'selected'; ?>><?= __('Select language', 'hdw-dms-importer') ?></option>

                                <?php foreach ($contentLanguages->get() as $language) { ?>
                                    <option value="<?= $language->getIso() ; ?>"
                                        <?php if(esc_attr($options['rest-products-language']) == $language->getIso()) echo 'selected' ?>
                                    >
                                    <?=  $language->getName(); ?></option>';
                                <?php } ?>
                            </select>
                        <?php
                        }
                        ?>
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

                            // $postProducts = \getProductsBySKU($product->getSku(), $args);

                            // if (!empty($postProducts)) {
                            //     $classes[] = 'product--in-shop';
                            // } else {
                            //     $args['post_status'] = 'draft';
                            //     $postDraftProduct = \getProductsBySKU($product->getSku(), $args);
                            //     $classes[] = (!empty($postDraftProduct)) ? 'product--is-draft' : '';
                            // }

                            $class = 'product--is-out-of-sync';
                            // $hash = $product->getHash();
                            // foreach ($postProducts as $postProduct) {
                            //     if ($hash == get_post_meta($postProduct->ID, '_import-hash', true)) {
                            //         $class = 'product--is-in-sync';
                            //     }
                            // }
                            $classes[] = $class;
                        ?>
                            <div class="<?= implode(' ', $classes) ?>">
                                <input checked type="checkbox" class="product-input" name="product" id="product-<?= $product->getId() ?>" value="<?= $product->getId() ?>" checked />
                                <label class="product-label" for="product-<?= $product->getId() ?>">
                                    <?= $product->getName() ?><br>
                                    <small class="product-sku"><?= $product->getOrderNumber() ?> (<?= $product->getStatus() ?>)</small><br>
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
