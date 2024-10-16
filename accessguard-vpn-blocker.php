<?php
/**
 * Plugin Name: AccessGuard VPN Blocker
 * Description: Blocks users using VPN or proxy and shows a warning notice using IPapi.co free API with enhanced IP detection.
 * Version: 1.6
 * Author: Your Name
 * Text Domain: accessguard-vpn-blocker
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AccessGuard_VPN_Blocker {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'load_plugin_options'));
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_action('wp', array($this, 'check_vpn_proxy'));
    }

    public function load_plugin_options() {
        $this->options = get_option('accessguard_vpn_blocker_options');
    }

    public function add_plugin_page() {
        add_options_page(
            'AccessGuard Settings', 
            'AccessGuard VPN Blocker', 
            'manage_options', 
            'accessguard-vpn-blocker', 
            array($this, 'create_admin_page')
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>AccessGuard VPN Blocker Settings</h1>
            <form method="post" action="options.php">
            <?php
                settings_fields('accessguard_vpn_blocker_option_group');
                do_settings_sections('accessguard-vpn-blocker-admin');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'accessguard_vpn_blocker_option_group',
            'accessguard_vpn_blocker_options',
            array($this, 'sanitize')
        );

        add_settings_section(
            'accessguard_vpn_blocker_setting_section',
            'General Settings',
            array($this, 'section_info'),
            'accessguard-vpn-blocker-admin'
        );

        add_settings_field(
            'enable_vpn_blocking', 
            'Enable VPN Blocking', 
            array($this, 'enable_vpn_blocking_callback'), 
            'accessguard-vpn-blocker-admin', 
            'accessguard_vpn_blocker_setting_section'
        );
    }

    public function sanitize($input) {
        $sanitary_values = array();
        if (isset($input['enable_vpn_blocking'])) {
            $sanitary_values['enable_vpn_blocking'] = $input['enable_vpn_blocking'];
        }
        return $sanitary_values;
    }

    public function section_info() {
        echo 'Configure the settings for AccessGuard VPN Blocker below:';
    }

    public function enable_vpn_blocking_callback() {
        printf(
            '<input type="checkbox" name="accessguard_vpn_blocker_options[enable_vpn_blocking]" id="enable_vpn_blocking" value="1" %s>',
            (isset($this->options['enable_vpn_blocking']) && $this->options['enable_vpn_blocking'] === '1') ? 'checked' : ''
        );
        echo '<label for="enable_vpn_blocking">Enable VPN and Proxy Blocking</label>';
    }

    public function check_vpn_proxy() {
        if (!isset($this->options['enable_vpn_blocking']) || $this->options['enable_vpn_blocking'] !== '1') {
            return; // VPN blocking is disabled
        }

        if ($this->is_vpn_or_proxy()) {
            wp_die($this->get_warning_message(), 'Access Denied', array('response' => 403));
        }
    }

    private function get_client_ip() {
        $ip_headers = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip_list = explode(',', $_SERVER[$header]);
                $client_ip = trim($ip_list[0]);
                if (filter_var($client_ip, FILTER_VALIDATE_IP)) {
                    return $client_ip;
                }
            }
        }

        return '';
    }

    private function is_vpn_or_proxy() {
        $ip_address = $this->get_client_ip();
        
        if (empty($ip_address)) {
            error_log('AccessGuard VPN Blocker: Unable to determine client IP address.');
            return false; // If we can't determine the IP, don't block the user
        }

        $api_url = "http://ip-api.com/json/{$ip_address}?fields=proxy,hosting";

        $response = wp_remote_get($api_url);

        if (is_wp_error($response)) {
            error_log('AccessGuard VPN Blocker API Error: ' . $response->get_error_message());
            return false; // If there's an error, don't block the user
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Check if the IP is identified as a proxy or hosting service
        if (isset($data['proxy']) && $data['proxy'] === true) {
            return true;
        }

        if (isset($data['hosting']) && $data['hosting'] === true) {
            return true;
        }

        return false;
    }

    private function get_warning_message() {
        return '<h1>' . __('Access Denied', 'accessguard-vpn-blocker') . '</h1>
                <p>' . __('We've detected that you're using a VPN or proxy. 
                For security reasons, access to this website is not allowed through VPNs or proxies. 
                Please disable your VPN or proxy and try again.', 'accessguard-vpn-blocker') . '</p>';
    }
}

new AccessGuard_VPN_Blocker();
