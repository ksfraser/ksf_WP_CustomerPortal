<?php

class KSF_Portal_API
{
    private static $fa_path = null;

    public static function get_fa_path()
    {
        if (self::$fa_path === null) {
            self::$fa_path = defined('KSF_FA_PATH') ? KSF_FA_PATH : get_option('ksf_fa_path', '/var/www/html');
        }
        return self::$fa_path;
    }

    public static function authenticate($request)
    {
        $username = $request->get_param('username');
        $password = $request->get_param('password');

        $creds = [
            'user_login' => $username,
            'user_password' => $password,
            'remember' => true,
        ];

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            return new WP_Error('auth_failed', $user->get_error_message(), ['status' => 401]);
        }

        $debtor_no = get_user_meta($user->ID, 'ksf_fa_debtor_no', true);
        
        if (!$debtor_no && $user->user_email) {
            $debtor_no = self::find_fa_debtor($user->user_email);
            if ($debtor_no) {
                update_user_meta($user->ID, 'ksf_fa_debtor_no', $debtor_no);
            }
        }

        return [
            'token' => wp_hash($user->ID . time()),
            'user_id' => $user->ID,
            'debtor_no' => $debtor_no,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
        ];
    }

    public static function check_auth($request)
    {
        $auth_header = $request->get_header('Authorization');
        
        if (!$auth_header) {
            $token = $request->get_param('token');
            if (!$token) {
                return is_user_logged_in();
            }
        }

        return is_user_logged_in();
    }

    public static function get_current_user_id()
    {
        $user = wp_get_current_user();
        return $user->ID;
    }

    public static function get_debtor_no($user_id = null)
    {
        if (!$user_id) {
            $user_id = self::get_current_user_id();
        }
        
        $debtor_no = get_user_meta($user_id, 'ksf_fa_debtor_no', true);
        
        if (!$debtor_no) {
            $user = get_userdata($user_id);
            $debtor_no = self::find_fa_debtor($user->user_email);
            if ($debtor_no) {
                update_user_meta($user_id, 'ksf_fa_debtor_no', $debtor_no);
            }
        }
        
        return $debtor_no;
    }

    public static function get_tickets($request)
    {
        $debtor_no = self::get_debtor_no();
        
        if (!$debtor_no) {
            return new WP_Error('no_customer', 'No linked customer found', ['status' => 404]);
        }

        $fa_path = self::get_fa_path();
        if (!file_exists($fa_path . '/includes/db.inc')) {
            return new WP_Error('fa_not_found', 'FA not configured', ['status' => 500]);
        }

        include_once $fa_path . '/includes/db.inc';

        $sql = "SELECT * FROM " . TB_PREF . "ksf_supporttickets_tickets 
            WHERE debtor_no = " . db_escape($debtor_no) . "
            ORDER BY created_at DESC LIMIT 50";

        $result = db_query($sql, "Could not get tickets");
        $tickets = [];

        while ($row = db_fetch_assoc($result)) {
            $tickets[] = [
                'id' => (int) $row['id'],
                'ticket_number' => $row['ticket_number'],
                'subject' => $row['subject'],
                'status' => $row['status'],
                'priority' => $row['priority'],
                'type' => $row['type'],
                'created_at' => $row['created_at'],
            ];
        }

        return $tickets;
    }

    public static function get_ticket($request)
    {
        $debtor_no = self::get_debtor_no();
        $id = (int) $request->get_param('id');

        $fa_path = self::get_fa_path();
        if (!file_exists($fa_path . '/includes/db.inc')) {
            return new WP_Error('fa_not_found', 'FA not configured', ['status' => 500]);
        }

        include_once $fa_path . '/includes/db.inc';

        $sql = "SELECT * FROM " . TB_PREF . "ksf_supporttickets_tickets 
            WHERE id = " . db_escape($id) . " AND debtor_no = " . db_escape($debtor_no);

        $result = db_query($sql, "Could not get ticket");
        $row = db_fetch_assoc($result);

        if (!$row) {
            return new WP_Error('not_found', 'Ticket not found', ['status' => 404]);
        }

        $notes_sql = "SELECT * FROM " . TB_PREF . "ksf_supporttickets_tickets_notes 
            WHERE ticket_id = " . db_escape($id) . " ORDER BY created_at DESC";
        
        $notes_result = db_query($notes_sql, "Could not get notes");
        $notes = [];
        
        while ($note = db_fetch_assoc($notes_result)) {
            $notes[] = $note;
        }

        return [
            'id' => (int) $row['id'],
            'ticket_number' => $row['ticket_number'],
            'subject' => $row['subject'],
            'description' => $row['description'],
            'status' => $row['status'],
            'priority' => $row['priority'],
            'type' => $row['type'],
            'created_at' => $row['created_at'],
            'notes' => $notes,
        ];
    }

    public static function create_ticket($request)
    {
        $debtor_no = self::get_debtor_no();
        
        if (!$debtor_no) {
            return new WP_Error('no_customer', 'No linked customer found', ['status' => 404]);
        }

        $subject = $request->get_param('subject');
        $description = $request->get_param('description');
        $type = $request->get_param('type', 'Question');
        $priority = $request->get_param('priority', 'Medium');

        if (!$subject) {
            return new WP_Error('missing_field', 'Subject required', ['status' => 400]);
        }

        $fa_path = self::get_fa_path();
        if (!file_exists($fa_path . '/includes/db.inc')) {
            return new WP_Error('fa_not_found', 'FA not configured', ['status' => 500]);
        }

        include_once $fa_path . '/includes/db.inc';

        $ticket_number = 'TKT-' . date('Ymd') . '-' . str_pad((time() % 10000), 4, '0', STR_PAD_LEFT);
        $user = wp_get_current_user();

        $sql = "INSERT INTO " . TB_PREF . "ksf_supporttickets_tickets
            (ticket_number, subject, description, type, priority, status, debtor_no, created_by)
            VALUES (" . db_escape($ticket_number) . ",
                " . db_escape($subject) . ",
                " . db_escape($description) . ",
                " . db_escape($type) . ",
                " . db_escape($priority) . ",
                'New',
                " . db_escape($debtor_no) . ",
                " . db_escape($user->user_login) . ")";

        db_query($sql, "Could not create ticket");
        $ticket_id = db_insert_id();

        return [
            'id' => (int) $ticket_id,
            'ticket_number' => $ticket_number,
            'message' => 'Ticket created successfully',
        ];
    }

    public static function add_ticket_note($request)
    {
        $debtor_no = self::get_debtor_no();
        $id = (int) $request->get_param('id');
        $note = $request->get_param('note');
        $note_type = $request->get_param('note_type', 'Public');

        if (!$note) {
            return new WP_Error('missing_field', 'Note required', ['status' => 400]);
        }

        $fa_path = self::get_fa_path();
        if (!file_exists($fa_path . '/includes/db.inc')) {
            return new WP_Error('fa_not_found', 'FA not configured', ['status' => 500]);
        }

        include_once $fa_path . '/includes/db.inc';

        $user = wp_get_current_user();

        $sql = "INSERT INTO " . TB_PREF . "ksf_supporttickets_tickets_notes
            (ticket_id, note, note_type, created_by)
            VALUES (" . db_escape($id) . ",
                " . db_escape($note) . ",
                " . db_escape($note_type) . ",
                " . db_escape($user->user_login) . ")";

        db_query($sql, "Could not add note");

        return ['message' => 'Note added'];
    }

    public static function get_invoices($request)
    {
        $debtor_no = self::get_debtor_no();
        
        if (!$debtor_no) {
            return [];
        }

        $fa_path = self::get_fa_path();
        if (!file_exists($fa_path . '/includes/db.inc')) {
            return [];
        }

        include_once $fa_path . '/includes/db.inc';

        $sql = "SELECT * FROM " . TB_PREF . "debtor_trans 
            WHERE debtor_no = " . db_escape($debtor_no) . "
            AND type = " . db_escape(10) . "
            ORDER BY trans_date DESC LIMIT 20";

        $result = db_query($sql, "Could not get invoices");
        $invoices = [];

        while ($row = db_fetch_assoc($result)) {
            $invoices[] = [
                'id' => (int) $row['trans_no'],
                'type' => $row['type'],
                'reference' => $row['reference'],
                'trans_date' => $row['trans_date'],
                'due_date' => $row['due_date'],
                'total' => (float) $row['total'],
                'ov_discount' => (float) $row['ov_discount'],
                'due_value' => (float) $row['due_value'],
            ];
        }

        return $invoices;
    }

    public static function get_orders($request)
    {
        $debtor_no = self::get_debtor_no();
        
        if (!$debtor_no) {
            return [];
        }

        $fa_path = self::get_fa_path();
        if (!file_exists($fa_path . '/includes/db.inc')) {
            return [];
        }

        include_once $fa_path . '/includes/db.inc';

        $sql = "SELECT * FROM " . TB_PREF . "sales_orders 
            WHERE debtor_no = " . db_escape($debtor_no) . "
            ORDER BY ord_date DESC LIMIT 20";

        $result = db_query($sql, "Could not get orders");
        $orders = [];

        while ($row = db_fetch_assoc($result)) {
            $orders[] = [
                'id' => (int) $row['order_no'],
                'order_no' => $row['order_no'],
                'reference' => $row['reference'],
                'ord_date' => $row['ord_date'],
                'delivery_date' => $row['delivery_date'],
                'total' => (float) $row['total'],
                'status' => $row['status'],
            ];
        }

        return $orders;
    }

    public static function get_profile($request)
    {
        $user_id = self::get_current_user_id();
        $user = get_userdata($user_id);
        $debtor_no = self::get_debtor_no($user_id);

        return [
            'user_id' => $user_id,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'debtor_no' => $debtor_no,
        ];
    }

    public static function update_profile($request)
    {
        $user_id = self::get_current_user_id();
        $debtor_no = self::get_debtor_no($user_id);

        if (!$debtor_no) {
            return new WP_Error('no_customer', 'No linked customer found', ['status' => 404]);
        }

        $display_name = $request->get_param('display_name');
        $phone = $request->get_param('phone');
        $address = $request->get_param('address');
        $city = $request->get_param('city');
        $state = $request->get_param('state');
        $postal_code = $request->get_param('postal_code');
        $country = $request->get_param('country');

        if ($display_name) {
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $display_name,
            ]);
        }

        if ($phone) {
            update_user_meta($user_id, 'billing_phone', $phone);
        }

        $fa_path = self::get_fa_path();
        if (file_exists($fa_path . '/includes/db.inc')) {
            include_once $fa_path . '/includes/db.inc';

            $updates = [];
            if ($display_name) {
                $updates[] = "name = " . db_escape($display_name);
            }
            if ($phone) {
                $updates[] = "phone = " . db_escape($phone);
            }
            if ($address) {
                $updates[] = "address = " . db_escape($address);
            }
            if ($city) {
                $updates[] = "city = " . db_escape($city);
            }
            if ($state) {
                $updates[] = "state = " . db_escape($state);
            }
            if ($postal_code) {
                $updates[] = "postal_code = " . db_escape($postal_code);
            }
            if ($country) {
                $updates[] = "country = " . db_escape($country);
            }

            if (!empty($updates)) {
                $sql = "UPDATE " . TB_PREF . "debtors_master
                    SET " . implode(', ', $updates) . "
                    WHERE debtor_no = " . db_escape($debtor_no);
                db_query($sql, "Could not update profile");
            }
        }

        return ['message' => 'Profile updated successfully'];
    }

    public static function get_announcements($request)
    {
        $debtor_no = self::get_debtor_no();
        $limit = (int) $request->get_param('limit', 20);

        $fa_path = self::get_fa_path();
        if (!file_exists($fa_path . '/includes/db.inc')) {
            return [];
        }

        include_once $fa_path . '/includes/db.inc';

        $sql = "SELECT * FROM " . TB_PREF . "ksf_customerportal_announcements 
            WHERE active = 1 
            AND (expiry_date IS NULL OR expiry_date >= CURDATE())
            ORDER BY created_at DESC 
            LIMIT " . db_escape($limit);

        $result = db_query($sql, "Could not get announcements");
        $announcements = [];

        while ($row = db_fetch_assoc($result)) {
            if ($row['target_type'] === 'all' || $row['target_type'] === 'customer') {
                $announcements[] = [
                    'id' => (int) $row['id'],
                    'title' => $row['title'],
                    'content' => $row['content'],
                    'priority' => $row['priority'],
                    'created_at' => $row['created_at'],
                ];
            }
        }

        return $announcements;
    }

    public static function get_documents($request)
    {
        $debtor_no = self::get_debtor_no();

        if (!$debtor_no) {
            return [];
        }

        $fa_path = self::get_fa_path();
        if (!file_exists($fa_path . '/includes/db.inc')) {
            return [];
        }

        include_once $fa_path . '/includes/db.inc';

        $sql = "SELECT * FROM " . TB_PREF . "ksf_customerportal_customer_documents 
            WHERE debtor_no = " . db_escape($debtor_no) . "
            ORDER BY created_at DESC LIMIT 50";

        $result = db_query($sql, "Could not get documents");
        $documents = [];

        while ($row = db_fetch_assoc($result)) {
            $documents[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'filename' => $row['filename'],
                'file_type' => $row['file_type'],
                'created_at' => $row['created_at'],
            ];
        }

        return $documents;
    }

    public static function reset_password($request)
    {
        $username = $request->get_param('username');

        if (!$username) {
            return new WP_Error('missing_field', 'Username required', ['status' => 400]);
        }

        $user = get_user_by('login', $username);
        if (!$user) {
            $user = get_user_by('email', $username);
        }

        if (!$user) {
            return new WP_Error('not_found', 'User not found', ['status' => 404]);
        }

        $reset_key = get_password_reset_key($user);
        $email = $user->user_email;

        $message = sprintf(
            "Someone requested that the password be reset for the following account:\n\nUsername: %s\n\nIf this was a mistake, just ignore this email and nothing will happen.\n\nTo reset your password, visit the following address: %s",
            $user->user_login,
            network_site_url('wp-login.php?action=rp&key=' . $reset_key . '&login=' . rawurlencode($user->user_login), 'login')
        );

        $sent = wp_mail($email, 'Password Reset Request', $message);

        if (!$sent) {
            return new WP_Error('email_failed', 'Could not send reset email', ['status' => 500]);
        }

        return ['message' => 'Password reset email sent'];
    }

    private static function find_fa_debtor($email)
    {
        $fa_path = self::get_fa_path();
        
        if (!file_exists($fa_path . '/includes/db.inc')) {
            return null;
        }

        global $db;
        include_once $fa_path . '/includes/db.inc';

        $sql = "SELECT debtor_no FROM " . TB_PREF . "debtors_master 
            WHERE email = " . db_escape($email) . " LIMIT 1";

        $result = db_query($sql, "Could not find debtor");
        $row = db_fetch_assoc($result);
        
        return $row ? $row['debtor_no'] : null;
    }
}