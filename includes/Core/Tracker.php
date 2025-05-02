<?php
/**
 * Appsero Tracker initialization
 *
 * @package AccessDefender\Core
 */

namespace AccessDefender\Core;

/**
 * Class Tracker
 * Handles Appsero tracking initialization
 */
class Tracker {

    /**
     * Initialize Appsero tracker
     *
     * @return void
     */
    public static function init() {
        if ( ! class_exists( 'Appsero\Client' ) ) {
            require_once ACCESS_DEFENDER_PATH . '/appsero/src/Client.php';
        }

        $client = new \Appsero\Client( 
            '170369a5-3507-4de6-8d7c-740e158124ea',
            'Access Defender',
            ACCESS_DEFENDER_FILE
        );

        // Active insights
        $client->insights()->init();
    }
} 