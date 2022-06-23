<?php
namespace HDW\ProjectDmsImporter;

use HDW\ProjectDmsImporter\Import;

/**
 * Service example
 */
class Cron
{
    /**
     * Register hooks
     *
     * @return void
     **/
    public function register(): void
    {
        \add_action('wp', [$this, 'scheduleEvent']);
        \add_action('update_products', [$this, 'updateProducts']);
    }

    /**
     * Schedule
     **/
    public function scheduleEvent(): void
    {
        if (!wp_next_scheduled('update_products')) {
            wp_schedule_event(strtotime('00:00:00'), 'daily', 'update_products');
        }

        if (!wp_next_scheduled('update_products_repeat')) {
            wp_schedule_event(strtotime('01:00:00'), 'daily', 'update_products_repeat');
        }
    }

    public function updateProducts()
    {
        \updateProducts();
    }
}
