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
        \add_action('update_stock', [$this, 'updateStock']);
    }

    /**
     * Schedule
     **/
    public function scheduleEvent(): void
    {
        if (! wp_next_scheduled('update_stock')) {
            wp_schedule_event(time(), 'hourly', 'update_stock');
        }

        if (! wp_next_scheduled('update_products')) {
            wp_schedule_event(strtotime('00:00:00'), 'daily', 'update_products');
        }
    }

    public function updateProducts()
    {
        \updateProducts();
    }

    public function updateStock()
    {
        \updateStocks();
    }
}
