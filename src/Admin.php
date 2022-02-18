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
                <th><?php _e('Anzahl Produkte', 'hdw-dms-importer') ?></th>
                <td>
                    <?php
                    echo count(\get_posts([
                        'post_type' => 'cpt_products',
                        'fields' => 'ids',
                        'showposts' => -1,
                    ])); ?>
                </td>
            </tr>
            <tr>
                <th><?php _e('Anzahl Produkte auf Entwurf', 'hdw-dms-importer') ?></th>
                <td>
                    <?php
                    echo count(\get_posts([
                        'post_type' => 'cpt_products',
                        'post_status' => 'draft',
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
        <h4><?= __('Language', 'hdw-dms-importer') . ': ' . \getDmsSelectedLanguage(); ?></h4>

        <?php
        $brand = \getDmsSelectedBrand();
        if ($brand != 'select') {
            echo '<h4>' . __('Brand', 'hdw-dms-importer') . ': ' . \getDmsSelectedBrand() . '</h4>';
        }
        ?>

        <?php
        // if (wp_verify_nonce($_POST['preview-waldlaeufer-logisoft-import-nonce'], 'preview-waldlaeufer-logisoft-import')) {
        //     $this->previewImport();
        // } 
        if (isset($_POST['preview-waldlaeufer-logisoft-import-nonce'])) {
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
            if (\getDmsSelectedLanguage() != '' && (\getDmsSelectedLanguage() !== null && (\getDmsSelectedLanguage() != sanitize_text_field($_POST['rest-products-language'])))) {
                delete_transient('logisoft-products-collection');
                delete_transients_with_prefix('logisoft-product-');
            }

            // delete transients if brand filter have changed
            if (\getDmsSelectedBrand() != '' && (\getDmsSelectedBrand() != sanitize_text_field($_POST['rest-products-brand']))) {
                delete_transient('logisoft-products-collection');
                delete_transients_with_prefix('logisoft-product-');
            }

            $options = [
                'rest-username' => isset($_POST['rest-username']) ? sanitize_text_field($_POST['rest-username']) : '',
                'rest-password' => $_POST['rest-password'] && \str_repeat('*', 8) != $_POST['rest-password'] ? $_POST['rest-password'] : getDmsRestPassword(),
                'rest-base' => isset($_POST['rest-base']) ? trailingslashit(esc_url_raw($_POST['rest-base'])) : '',
                'rest-api-token' => isset($_POST['rest-api-token']) ? sanitize_text_field($_POST['rest-api-token']) : '',
                'rest-products-endpoint' => isset($_POST['rest-products-endpoint']) ? sanitize_text_field($_POST['rest-products-endpoint']) : '',
                'rest-product-endpoint' => isset($_POST['rest-product-endpoint']) ? sanitize_text_field($_POST['rest-product-endpoint']) : '',
                'rest-products-language' => isset($_POST['rest-products-language']) ? sanitize_text_field($_POST['rest-products-language']) : '',
                'rest-file-root-path' => isset($_POST['rest-file-root-path']) ? sanitize_text_field($_POST['rest-file-root-path']) : '',
                'rest-products-brand' => isset($_POST['rest-products-brand']) ? sanitize_text_field($_POST['rest-products-brand']) : '',
                'rest-languages-endpoint' => isset($_POST['rest-languages-endpoint']) ? sanitize_text_field($_POST['rest-languages-endpoint']) : '',
                'rest-images-endpoint' => isset($_POST['rest-images-endpoint']) ? sanitize_text_field($_POST['rest-images-endpoint']) : '',
                'rest-product-overview-image' => isset($_POST['rest-product-overview-image']) ? sanitize_text_field($_POST['rest-product-overview-image']) : '',
                'rest-product-detail-page-image' => isset($_POST['rest-product-detail-page-image']) ? sanitize_text_field($_POST['rest-product-detail-page-image']) : ''
            ];

            \update_option('hdw-dms-importer-settings', $options);
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
                        <?= getDMSRestBase() ?><input type="text" name="rest-products-endpoint" value="<?= esc_attr($options['rest-products-endpoint'] ?? '') ?>" />
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Product Endpoint', 'hdw-dms-importer') ?></th>
                    <td>
                        <?= getDMSRestBase() ?><input type="text" name="rest-product-endpoint" value="<?= esc_attr($options['rest-product-endpoint'] ?? '') ?>" /><br>
                        <small><?php _e('{id} is replaced with the product id from erp') ?></small><br>
                        <small><?php _e('{language} is replaced with the product language from erp') ?></small>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Languages Endpoint', 'hdw-dms-importer') ?></th>
                    <td>
                        <?= getDMSRestBase() ?><input type="text" name="rest-languages-endpoint" value="<?= esc_attr($options['rest-languages-endpoint'] ?? '') ?>" /><br>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Images Endpoint', 'hdw-dms-importer') ?></th>
                    <td>
                        <?= getDMSRestBase() ?><input type="text" name="rest-images-endpoint" value="<?= esc_attr($options['rest-images-endpoint'] ?? '') ?>" /><br>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Image Root Path', 'hdw-dms-importer') ?><br>
                        <?php _e('(AWS Server for an example)', 'hdw-dms-importer') ?>
                    </th>
                    <td>
                        <input type="url" class="regular-text" name="rest-file-root-path" value="<?= getFileRootPath() ?>" />
                    </td>
                </tr>
                <tr>
                    <th><?= __('Brand Filter', 'hdw-dms-importer') ?></th>
                    <td>
                        <select name="rest-products-brand" id="rest-products-brand">
                            <option value='select' <?php if (isset($options['rest-products-brand']) && esc_attr($options['rest-products-brand']) == 'select') echo 'selected'; ?>><?= __('Select Brand', 'hdw-dms-importer') ?></option>
                            <option value='green care PROFESSIONAL' <?php if (isset($options['rest-products-brand']) && esc_attr($options['rest-products-brand']) == 'green care PROFESSIONAL') echo 'selected'; ?>><?= __('green care PROFESSIONAL', 'hdw-dms-importer') ?></option>
                            <option value='tana PROFESSIONAL' <?php if (isset($options['rest-products-brand']) && esc_attr($options['rest-products-brand']) == 'tana PROFESSIONAL') echo 'selected'; ?>><?= __('tana PROFESSIONAL', 'hdw-dms-importer') ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Product Language', 'hdw-dms-importer') . '<br>';

                        $contentLanguages = '';

                        if (getDMSRestBase() != '' && getDMSRestBase() != '/') {
                            $contentLanguages = \getDmsLanguages(); 
                            $languagesCount = $contentLanguages->getCount(); ?>
                            (<?= $languagesCount . ' ' . __('languages', 'hdw-dms-importer'); ?>)
                        <?php
                        } ?>
                    </th>
                    <td>
                        <?php
                        if (isset($contentLanguages) && $contentLanguages != '') { ?>
                            <select name="rest-products-language" id="rest-product-language">
                                <option name='select' <?php if (esc_attr($options['rest-products-language']) == 'select') echo 'selected'; ?>><?= __('Select language', 'hdw-dms-importer') ?></option>

                                <?php foreach ($contentLanguages->get() as $language) { ?>
                                    <option value="<?= $language->getIso(); ?>" <?php if (esc_attr($options['rest-products-language']) == $language->getIso()) echo 'selected' ?>>
                                        <?= __($language->getName(), 'hdw-dms-importer'); ?></option>;
                                <?php } ?>
                            </select>
                        <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Image Product overview page', 'hdw-dms-importer') ?></th>
                    <td>
                        <?php
                        $imageSizes = '';

                        if (getDMSRestBase() != '' && getDMSRestBase() != '/') {
                            $imageSizes = \getDmsImageSizes();
                        }

                        if ($imageSizes != '') { ?>
                            <select name="rest-product-overview-image" id="rest-product-overview-image">
                                <option name="select" <?php if (esc_attr($options['rest-product-overview-image'] ?? '') == 'select') echo "selected"; ?>><?= __('Select thumbnail size', 'hdw-dms-importer') ?></option>

                                <?php foreach ($imageSizes->get() as $size) { ?>
                                    <option value="<?= $size->getName(); ?>" <?php if (esc_attr($options['rest-product-overview-image'] ?? '') == $size->getName()) echo 'selected' ?>>
                                        <?= ucfirst($size->getName()); ?> (<?= __($size->getSize(), 'hdw-dms-importer'); ?>)
                                    </option>;
                                <?php } ?>
                            </select>
                        <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?= __('Image Product detail page', 'hdw-dms-importer') ?></th>
                    <td>
                        <?php
                        if ($imageSizes != '') { ?>
                            <select name="rest-product-detail-page-image" id="rest-product-detail-page-image">
                                <option name="select" <?php if (esc_attr($options['rest-product-detail-page-image'] ?? '') == 'select') echo "selected"; ?>><?= __('Select thumbnail size', 'hdw-dms-importer') ?></option>

                                <?php foreach ($imageSizes->get() as $size) { ?>
                                    <option value="<?= $size->getName(); ?>" <?php if (esc_attr($options['rest-product-detail-page-image'] ?? '') == $size->getName()) echo 'selected' ?>>
                                        <?= ucfirst($size->getName()); ?> (<?= __($size->getSize(), 'hdw-dms-importer'); ?>)
                                    </option>;
                                <?php } ?>
                            </select>
                        <?php
                        }
                        ?>
                    </td>
                </tr>
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
                            $classes = ['product'];

                            $args = [
                                'post_status' => 'any'
                            ];

                            // $args = [
                            //     'post_status' => 'publish',
                            //     'tax_query' => [
                            //         'taxonomy' => 'product_cat',
                            //         'terms' => ['shop'],
                            //         'field' => 'slug',
                            //     ]
                            // ];

                            $postProducts = \getProductsBySKU($product->getSku(), $args);

                            if (!empty($postProducts)) {
                                $classes[] = 'product--on-site';
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
                            $classes[] = $class;
                        ?>
                            <div class="<?= implode(' ', $classes) ?>">
                                <input checked type="checkbox" class="product-input" name="product" id="product-<?= $product->getId() ?>" value="<?= $product->getId() ?>" checked />
                                <label class="product-label" for="product-<?= $product->getId() ?>">
                                    <?= $product->getName() ?> <?= $product->getOrderQuantity(); ?><br>
                                    <?php
                                    $type = $product->getProductType();
                                    echo $type;
                                    ?>
                                    <br><small class="product-sku"><?= $product->getSku() ?>
                                        <!-- (<?= $product->getStatus() ?>) -->
                                    </small>
                                    <?php
                                    if ($type == 'variant') {
                                        foreach ($product->getVariants() as $p) {
                                            echo '<br><small class="product-sku">' . $p->order_number . '</small>';
                                        }
                                    }
                                    ?>
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
