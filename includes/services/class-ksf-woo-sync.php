<?php

namespace Ksfraser\WooCommerce;

use WP_User;

class WooCommerceSyncService
{
    private static $instance = null;
    private $fa_path = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->fa_path = defined('KSF_FA_PATH') ? KSF_FA_PATH : get_option('ksf_fa_path', '/var/www/html');
    }

    public function sync_customer_to_woo($debtor_no)
    {
        $this->init_fa();

        $sql = "SELECT * FROM " . TB_PREF . "debtors_master WHERE debtor_no = " . db_escape($debtor_no);
        $result = db_query($sql, "Could not get debtor");
        $debtor = db_fetch_assoc($result);

        if (!$debtor) {
            return new \WP_Error('not_found', 'Debtor not found');
        }

        $user = $this->find_wp_user_by_debtor($debtor_no);
        if (!$user) {
            $user_id = $this->create_wp_user($debtor);
            if (is_wp_error($user_id)) {
                return $user_id;
            }
        } else {
            $user_id = $user->ID;
            $this->update_wp_user($user_id, $debtor);
        }

        $woo_customer_id = get_user_meta($user_id, 'ksf_woo_customer_id', true);
        if (!$woo_customer_id) {
            $woo_customer_id = $this->create_woo_customer($debtor);
            if ($woo_customer_id) {
                update_user_meta($user_id, 'ksf_woo_customer_id', $woo_customer_id);
            }
        }

        return [
            'user_id' => $user_id,
            'woo_customer_id' => $woo_customer_id,
            'debtor_no' => $debtor_no,
        ];
    }

    public function sync_woo_customer_to_fa($woo_customer_id)
    {
        if (!function_exists('wc_get_customer')) {
            return new \WP_Error('woo_not_found', 'WooCommerce not active');
        }

        $woo_customer = new \WC_Customer($woo_customer_id);
        if (!$woo_customer->get_id()) {
            return new \WP_Error('not_found', 'WooCommerce customer not found');
        }

        $email = $woo_customer->get_email();
        $this->init_fa();

        $debtor_no = $this->find_fa_debtor_by_email($email);
        if ($debtor_no) {
            $this->update_fa_debtor($debtor_no, [
                'name' => $woo_customer->get_first_name() . ' ' . $woo_customer->get_last_name(),
                'address' => $woo_customer->get_billing_address_1(),
                'city' => $woo_customer->get_billing_city(),
                'state' => $woo_customer->get_billing_state(),
                'postal_code' => $woo_customer->get_billing_postcode(),
                'country' => $woo_customer->get_billing_country(),
                'phone' => $woo_customer->get_billing_phone(),
            ]);
        } else {
            $debtor_no = $this->create_fa_debtor([
                'name' => $woo_customer->get_first_name() . ' ' . $woo_customer->get_last_name(),
                'email' => $email,
                'address' => $woo_customer->get_billing_address_1(),
                'city' => $woo_customer->get_billing_city(),
                'state' => $woo_customer->get_billing_state(),
                'postal_code' => $woo_customer->get_billing_postcode(),
                'country' => $woo_customer->get_billing_country(),
                'phone' => $woo_customer->get_billing_phone(),
            ]);
        }

        if ($user = $this->find_wp_user_by_email($email)) {
            update_user_meta($user->ID, 'ksf_fa_debtor_no', $debtor_no);
        }

        return ['debtor_no' => $debtor_no];
    }

    public function sync_orders_to_woo($debtor_no)
    {
        $this->init_fa();

        $sql = "SELECT * FROM " . TB_PREF . "sales_orders 
            WHERE debtor_no = " . db_escape($debtor_no) . "
            ORDER BY ord_date DESC";

        $result = db_query($sql, "Could not get orders");
        $orders = [];

        while ($row = db_fetch_assoc($result)) {
            $woo_order_id = get_post_meta($row['order_no'], 'ksf_woo_order_id', true);
            if (!$woo_order_id) {
                $woo_order_id = $this->create_woo_order_from_fa($row, $debtor_no);
            }
            $orders[] = [
                'fa_order_no' => $row['order_no'],
                'woo_order_id' => $woo_order_id,
                'reference' => $row['reference'],
                'total' => $row['total'],
                'status' => $row['status'],
            ];
        }

        return $orders;
    }

    public function sync_invoices_to_woo($debtor_no)
    {
        $this->init_fa();

        $sql = "SELECT * FROM " . TB_PREF . "debtor_trans 
            WHERE debtor_no = " . db_escape($debtor_no) . "
            AND type = " . db_escape(10) . "
            ORDER BY trans_date DESC";

        $result = db_query($sql, "Could not get invoices");
        $invoices = [];

        while ($row = db_fetch_assoc($result)) {
            $invoice = [
                'fa_invoice_no' => $row['trans_no'],
                'reference' => $row['reference'],
                'trans_date' => $row['trans_date'],
                'due_date' => $row['due_date'],
                'total' => $row['total'],
                'due_value' => $row['due_value'],
                'paid' => (floatval($row['due_value']) == 0),
            ];

            if (function_exists('wc_create_invoice')) {
                $invoice['woo_invoice_id'] = $this->get_or_create_woo_invoice($row, $debtor_no);
            }

            $invoices[] = $invoice;
        }

        return $invoices;
    }

    public function create_payment_link($invoice_no, $WooCommerce_order_id = null)
    {
        $this->init_fa();

        $sql = "SELECT dt.*, dm.name as customer_name, dm.email as customer_email
            FROM " . TB_PREF . "debtor_trans dt
            JOIN " . TB_PREF . "debtors_master dm ON dt.debtor_no = dm.debtor_no
            WHERE dt.trans_no = " . db_escape($invoice_no) . "
            AND dt.type = " . db_escape(10);

        $result = db_query($sql, "Could not get invoice");
        $invoice = db_fetch_assoc($result);

        if (!$invoice) {
            return new \WP_Error('not_found', 'Invoice not found');
        }

        if (floatval($invoice['due_value']) <= 0) {
            return new \WP_Error('paid', 'Invoice already paid');
        }

        $amount = floatval($invoice['due_value']);
        $description = sprintf("Invoice %s - %s", $invoice['reference'], $invoice['customer_name']);

        $checkout_session = WC()->checkout();
        $session_data = [
            'payment_method' => 'bacs',
            'payment_method_title' => 'Bank Transfer',
            'set_archive' => true,
            'billing_first_name' => $invoice['customer_name'],
            'billing_email' => $invoice['customer_email'],
            'billing_phone' => $invoice['phone'],
        ];

        if (function_exists('wc_create_invoice')) {
            $product_id = wp_insert_post([
                'post_title' => $description,
                'post_content' => '',
                'post_status' => 'publish',
                'post_type' => 'product',
            ]);

            update_post_meta($product_id, '_price', $amount);
            update_post_meta($product_id, '_regular_price', $amount);
            update_post_meta($product_id, '_virtual', 'yes');
            update_post_meta($product_id, '_sold_individually', 'yes');
            update_post_meta($product_id, '_ksf_fa_invoice_no', $invoice_no);

            $checkout_session->create_order( [
                'billing_email' => $invoice['customer_email'],
                'created_via' => 'ksf_portal',
            ]);

            $product = wc_get_product($product_id);
            $checkout_session->add_product($product, 1);
        }

        return [
            'invoice_no' => $invoice_no,
            'reference' => $invoice['reference'],
            'amount' => $amount,
            'description' => $description,
            'due_date' => $invoice['due_date'],
        ];
    }

    public function init_fa()
    {
        if (!file_exists($this->fa_path . '/includes/db.inc')) {
            return new \WP_Error('fa_not_found', 'FA not configured');
        }
        global $db;
        include_once $this->fa_path . '/includes/db.inc';
    }

    private function find_wp_user_by_debtor($debtor_no)
    {
        $users = get_users([
            'meta_key' => 'ksf_fa_debtor_no',
            'meta_value' => $debtor_no,
        ]);
        return $users ? $users[0] : null;
    }

    private function find_wp_user_by_email($email)
    {
        $users = get_users([
            'search' => $email,
        ]);
        return $users ? $users[0] : null;
    }

    private function find_fa_debtor_by_email($email)
    {
        $this->init_fa();
        $sql = "SELECT debtor_no FROM " . TB_PREF . "debtors_master WHERE email = " . db_escape($email) . " LIMIT 1";
        $result = db_query($sql, "Could not find debtor");
        $row = db_fetch_assoc($result);
        return $row ? $row['debtor_no'] : null;
    }

    private function create_wp_user($debtor)
    {
        $username = sanitize_user($debtor['debtor_no']);
        $email = $debtor['email'];
        $user_id = wp_create_user($username, wp_generate_password(), $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        wp_update_user([
            'ID' => $user_id,
            'display_name' => $debtor['name'],
        ]);

        update_user_meta($user_id, 'ksf_fa_debtor_no', $debtor['debtor_no']);

        return $user_id;
    }

    private function update_wp_user($user_id, $debtor)
    {
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $debtor['name'],
        ]);

        if (!empty($debtor['phone'])) {
            update_user_meta($user_id, 'billing_phone', $debtor['phone']);
        }
    }

    private function create_woo_customer($debtor)
    {
        if (!function_exists('wc_create_customer')) {
            return null;
        }

        $customer = new \WC_Customer();
        $customer->set_email($debtor['email']);
        $customer->set_first_name(explode(' ', $debtor['name'])[0]);
        $customer->set_last_name(explode(' ', $debtor['name'])[1] ?? '');
        $customer->set_billing_address_1($debtor['address']);
        $customer->set_billing_city($debtor['city']);
        $customer->set_billing_state($debtor['state']);
        $customer->set_billing_postcode($debtor['postal_code']);
        $customer->set_billing_country($debtor['country']);
        $customer->set_phone($debtor['phone']);
        $customer->save();

        return $customer->get_id();
    }

    private function update_fa_debtor($debtor_no, $data)
    {
        $updates = [];
        if (isset($data['name'])) {
            $updates[] = "name = " . db_escape($data['name']);
        }
        if (isset($data['address'])) {
            $updates[] = "address = " . db_escape($data['address']);
        }
        if (isset($data['city'])) {
            $updates[] = "city = " . db_escape($data['city']);
        }
        if (isset($data['state'])) {
            $updates[] = "state = " . db_escape($data['state']);
        }
        if (isset($data['postal_code'])) {
            $updates[] = "postal_code = " . db_escape($data['postal_code']);
        }
        if (isset($data['country'])) {
            $updates[] = "country = " . db_escape($data['country']);
        }
        if (isset($data['phone'])) {
            $updates[] = "phone = " . db_escape($data['phone']);
        }

        if (!empty($updates)) {
            $sql = "UPDATE " . TB_PREF . "debtors_master SET " . implode(', ', $updates) . "
                WHERE debtor_no = " . db_escape($debtor_no);
            db_query($sql, "Could not update debtor");
        }
    }

    private function create_fa_debtor($data)
    {
        $debtor_no = 'DEB-' . date('Ymd') . '-' . str_pad((time() % 10000), 4, '0', STR_PAD_LEFT);

        $sql = "INSERT INTO " . TB_PREF . "debtors_master
            (debtor_no, name, email, address, city, state, postal_code, country, phone)
            VALUES (
                " . db_escape($debtor_no) . ",
                " . db_escape($data['name']) . ",
                " . db_escape($data['email']) . ",
                " . db_escape($data['address'] ?? '') . ",
                " . db_escape($data['city'] ?? '') . ",
                " . db_escape($data['state'] ?? '') . ",
                " . db_escape($data['postal_code'] ?? '') . ",
                " . db_escape($data['country'] ?? '') . ",
                " . db_escape($data['phone'] ?? '') . "
            )";

        db_query($sql, "Could not create debtor");

        return $debtor_no;
    }

    private function create_woo_order_from_fa($order, $debtor_no)
    {
        $user = $this->find_wp_user_by_debtor($debtor_no);
        if (!$user) {
            return null;
        }

        $checkout = WC()->checkout();
        $order_id = $checkout->create_order([
            'status' => $order['status'],
            'created_via' => 'ksf_sync',
            'customer_id' => $user->ID,
        ]);

        update_post_meta($order_id, 'ksf_fa_order_no', $order['order_no']);

        return $order_id;
    }

    private function get_or_create_woo_invoice($invoice, $debtor_no)
    {
        return null;
    }
}