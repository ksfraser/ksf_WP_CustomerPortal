<?php
/**
 * Plugin Name: KSF Customer Portal
 * Plugin URI: https://github.com/ksfraser/ksf_WP_CustomerPortal
 * Description: Customer portal connecting WordPress users to FrontAccounting CRM, Support Tickets, and WooCommerce
 * Version: 1.0.0
 * Author: KSF Development Team
 * License: GPL-3.0
 * Text Domain: ksf-customer-portal
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KSF_PORTAL_VERSION', '1.0.0');
define('KSF_PORTAL_PATH', plugin_dir_path(__FILE__));
define('KSF_PORTAL_URL', plugin_dir_url(__FILE__));

require_once KSF_PORTAL_PATH . 'includes/class-ksf-portal-autoloader.php';
require_once KSF_PORTAL_PATH . 'includes/class-ksf-portal-api.php';
require_once KSF_PORTAL_PATH . 'includes/class-ksf-portal-auth.php';
require_once KSF_PORTAL_PATH . 'includes/class-ksf-portal Shortcodes.php';

class KSF_Customer_Portal
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_login', [$this, 'on_user_login'], 10, 2);
        add_filter('auth_cookie_expiration', [$this, 'extend_auth_cookie'], 10, 2);
        
        add_shortcode('ksf_portal_tickets', [KSF_Portal_Shortcodes::class, 'render_tickets']);
        add_shortcode('ksf_portal_ticket_detail', [KSF_Portal_Shortcodes::class, 'render_ticket_detail']);
        add_shortcode('ksf_portal_invoices', [KSF_Portal_Shortcodes::class, 'render_invoices']);
        add_shortcode('ksf_portal_orders', [KSF_Portal_Shortcodes::class, 'render_orders']);
        add_shortcode('ksf_portal_ticket_form', [KSF_Portal_Shortcodes::class, 'render_ticket_form']);
    }

    public function register_rest_routes()
    {
        register_rest_route('ksf-portal/v1', '/auth', [
            'methods' => 'POST',
            'callback' => [KSF_Portal_API::class, 'authenticate'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('ksf-portal/v1', '/tickets', [
            'methods' => 'GET',
            'callback' => [KSF_Portal_API::class, 'get_tickets'],
            'permission_callback' => [KSF_Portal_API::class, 'check_auth'],
        ]);

        register_rest_route('ksf-portal/v1', '/tickets/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [KSF_Portal_API::class, 'get_ticket'],
            'permission_callback' => [KSF_Portal_API::class, 'check_auth'],
        ]);

        register_rest_route('ksf-portal/v1', '/tickets', [
            'methods' => 'POST',
            'callback' => [KSF_Portal_API::class, 'create_ticket'],
            'permission_callback' => [KSF_Portal_API::class, 'check_auth'],
        ]);

        register_rest_route('ksf-portal/v1', '/tickets/(?P<id>\d+)/notes', [
            'methods' => 'POST',
            'callback' => [KSF_Portal_API::class, 'add_ticket_note'],
            'permission_callback' => [KSF_Portal_API::class, 'check_auth'],
        ]);

        register_rest_route('ksf-portal/v1', '/invoices', [
            'methods' => 'GET',
            'callback' => [KSF_Portal_API::class, 'get_invoices'],
            'permission_callback' => [KSF_Portal_API::class, 'check_auth'],
        ]);

        register_rest_route('ksf-portal/v1', '/orders', [
            'methods' => 'GET',
            'callback' => [KSF_Portal_API::class, 'get_orders'],
            'permission_callback' => [KSF_Portal_API::class, 'check_auth'],
        ]);

        register_rest_route('ksf-portal/v1', '/profile', [
            'methods' => 'GET',
            'callback' => [KSF_Portal_API::class, 'get_profile'],
            'permission_callback' => [KSF_Portal_API::class, 'check_auth'],
        ]);
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style('ksf-portal', KSF_PORTAL_URL . 'assets/portal.css', [], KSF_PORTAL_VERSION);
        wp_enqueue_script('ksf-portal', KSF_PORTAL_URL . 'assets/portal.js', ['jquery'], KSF_PORTAL_VERSION, true);
        
        wp_localize_script('ksf-portal', 'ksfPortal', [
            'apiUrl' => get_rest_url(null, 'ksf-portal/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'isLoggedIn' => is_user_logged_in() ? 1 : 0,
        ]);
    }

    public function on_user_login($user_login, $user)
    {
        $this->sync_fa_user($user->ID);
    }

    public function extend_auth_cookie($expiration, $user_id)
    {
        return max($expiration, 86400 * 7);
    }

    private function sync_fa_user($wp_user_id)
    {
        $user = get_userdata($wp_user_id);
        if (!$user) return;

        $fa_debtor = get_user_meta($wp_user_id, 'ksf_fa_debtor_no', true);
        
        if (!$fa_debtor && $user->user_email) {
            $debtor_no = $this->find_fa_debtor_by_email($user->user_email);
            if ($debtor_no) {
                update_user_meta($wp_user_id, 'ksf_fa_debtor_no', $debtor_no);
            }
        }
    }

    private function find_fa_debtor_by_email($email)
    {
        $fa_path = defined('KSF_FA_PATH') ? KSF_FA_PATH : '/var/www/html';

        if (!file_exists($fa_path . '/includes/db.inc')) {
            return null;
        }

        global $db;
        include_once $fa_path . '/includes/db.inc';

        $sql = "SELECT debtor_no FROM " . TB_PREF . "debtors_master WHERE email = " . db_escape($email) . " LIMIT 1";
        $result = db_query($sql, "Could not find debtor");
        $row = db_fetch_assoc($result);
        
        return $row ? $row['debtor_no'] : null;
    }

    public static function activate()
    {
        flush_rewrite_rules();
        wp_schedule_event(time(), 'daily', 'ksf_portal_sync_cron');
    }

    public static function deactivate()
    {
        wp_clear_scheduled_event('ksf_portal_sync_cron');
        flush_rewrite_rules();
    }
}

function ksf_portal()
{
    return KSF_Customer_Portal::get_instance();
}

add_action('plugins_loaded', 'ksf_portal');

register_activation_hook(__FILE__, ['KSF_Customer_Portal', 'activate']);
register_deactivation_hook(__FILE__, ['KSF_Customer_Portal', 'deactivate']);