<?php

class KSF_Portal_Auth
{
    public static function require_login()
    {
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public static function get_user_customer_id($user_id = null)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return get_user_meta($user_id, 'ksf_fa_debtor_no', true);
    }

    public static function link_account($wp_user_id, $debtor_no)
    {
        update_user_meta($wp_user_id, 'ksf_fa_debtor_no', $debtor_no);
    }

    public static function unlink_account($wp_user_id)
    {
        delete_user_meta($wp_user_id, 'ksf_fa_debtor_no');
    }

    public static function has_customer_access($wp_user_id = null)
    {
        if (!$wp_user_id) {
            $wp_user_id = get_current_user_id();
        }
        
        $debtor_no = self::get_user_customer_id($wp_user_id);
        return !empty($debtor_no);
    }
}