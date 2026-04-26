<?php

namespace Ksfraser\Salesman;

class SalesmanApprovalService
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

    public function create_approval_task($type, $entity_no, $data, $requesting_user_id)
    {
        $this->init_fa();

        $assigned_salesman = $this->get_assigned_salesman($entity_no);
        if (!$assigned_salesman) {
            return new \WP_Error('no_salesman', 'No salesman assigned to this customer');
        }

        $task_no = 'APR-' . date('Ymd') . '-' . str_pad((time() % 10000), 4, '0', STR_PAD_LEFT);
        $requested_by = get_userdata($requesting_user_id)->user_login;

        $sql = "INSERT INTO " . TB_PREF . "fa_approval_tasks
            (task_no, type, entity_no, data_json, status, requested_by, assigned_to, created_at)
            VALUES (
                " . db_escape($task_no) . ",
                " . db_escape($type) . ",
                " . db_escape($entity_no) . ",
                " . db_escape(json_encode($data)) . ",
                'Pending',
                " . db_escape($requested_by) . ",
                " . db_escape($assigned_salesman) . ",
                NOW()
            )";

        db_query($sql, "Could not create approval task");

        $this->notify_salesman($assigned_salesman, $task_no, $type, $entity_no);

        return [
            'task_no' => $task_no,
            'type' => $type,
            'entity_no' => $entity_no,
            'status' => 'Pending',
            'assigned_to' => $assigned_salesman,
        ];
    }

    public function get_pending_tasks($salesman_username)
    {
        $this->init_fa();

        $sql = "SELECT * FROM " . TB_PREF . "fa_approval_tasks 
            WHERE assigned_to = " . db_escape($salesman_username) . "
            AND status = 'Pending'
            ORDER BY created_at DESC";

        $result = db_query($sql, "Could not get tasks");
        $tasks = [];

        while ($row = db_fetch_assoc($result)) {
            $tasks[] = [
                'task_no' => $row['task_no'],
                'type' => $row['type'],
                'entity_no' => $row['entity_no'],
                'data' => json_decode($row['data_json'], true),
                'status' => $row['status'],
                'requested_by' => $row['requested_by'],
                'created_at' => $row['created_at'],
            ];
        }

        return $tasks;
    }

    public function approve_task($task_no, $salesman_username, $comments = '')
    {
        return $this->update_task_status($task_no, $salesman_username, 'Approved', $comments);
    }

    public function reject_task($task_no, $salesman_username, $comments = '')
    {
        return $this->update_task_status($task_no, $salesman_username, 'Rejected', $comments);
    }

    private function update_task_status($task_no, $salesman_username, $status, $comments)
    {
        $this->init_fa();

        $sql = "UPDATE " . TB_PREF . "fa_approval_tasks
            SET status = " . db_escape($status) . ",
                comments = " . db_escape($comments) . ",
                processed_by = " . db_escape($salesman_username) . ",
                processed_at = NOW()
            WHERE task_no = " . db_escape($task_no) . "
            AND assigned_to = " . db_escape($salesman_username);

        db_query($sql, "Could not update task");

        $sql = "SELECT type, entity_no, data_json FROM " . TB_PREF . "fa_approval_tasks
            WHERE task_no = " . db_escape($task_no);
        $result = db_query($sql, "Could not get task");
        $task = db_fetch_assoc($result);

        if ($task && $status === 'Approved') {
            $this->apply_changes($task['type'], $task['entity_no'], json_decode($task['data_json'], true));
        }

        return [
            'task_no' => $task_no,
            'status' => $status,
        ];
    }

    public function get_salesman_for_customer($debtor_no)
    {
        return $this->get_assigned_salesman($debtor_no);
    }

    public function assign_salesman($debtor_no, $salesman_username)
    {
        $this->init_fa();

        $sql = "UPDATE " . TB_PREF . "debtors_master
            SET salesman = " . db_escape($salesman_username) . "
            WHERE debtor_no = " . db_escape($debtor_no);

        db_query($sql, "Could not assign salesman");

        return ['debtor_no' => $debtor_no, 'salesman' => $salesman_username];
    }

    private function get_assigned_salesman($debtor_no)
    {
        $this->init_fa();

        $sql = "SELECT salesman FROM " . TB_PREF . "debtors_master 
            WHERE debtor_no = " . db_escape($debtor_no);

        $result = db_query($sql, "Could not get salesman");
        $row = db_fetch_assoc($result);

        return $row ? $row['salesman'] : null;
    }

    private function apply_changes($type, $entity_no, $data)
    {
        switch ($type) {
            case 'debtor_update':
                $this->apply_debtor_changes($entity_no, $data);
                break;
            case 'order_update':
                $this->apply_order_changes($entity_no, $data);
                break;
            case 'invoice_update':
                $this->apply_invoice_changes($entity_no, $data);
                break;
        }
    }

    private function apply_debtor_changes($debtor_no, $data)
    {
        $updates = [];
        foreach ($data as $field => $value) {
            $updates[] = "$field = " . db_escape($value);
        }

        $sql = "UPDATE " . TB_PREF . "debtors_master 
            SET " . implode(', ', $updates) . "
            WHERE debtor_no = " . db_escape($debtor_no);

        db_query($sql, "Could not update debtor");
    }

    private function apply_order_changes($order_no, $data)
    {
        $updates = [];
        foreach ($data as $field => $value) {
            $updates[] = "$field = " . db_escape($value);
        }

        $sql = "UPDATE " . TB_PREF . "sales_orders 
            SET " . implode(', ', $updates) . "
            WHERE order_no = " . db_escape($order_no);

        db_query($sql, "Could not update order");
    }

    private function apply_invoice_changes($trans_no, $data)
    {
        $updates = [];
        foreach ($data as $field => $value) {
            $updates[] = "$field = " . db_escape($value);
        }

        $sql = "UPDATE " . TB_PREF . "debtor_trans 
            SET " . implode(', ', $updates) . "
            WHERE trans_no = " . db_escape($trans_no) . "
            AND type = 10";

        db_query($sql, "Could not update invoice");
    }

    private function notify_salesman($salesman_username, $task_no, $type, $entity_no)
    {
        $email = $this->get_salesman_email($salesman_username);
        if (!$email) {
            return;
        }

        $subject = "New Approval Request: $task_no";
        $message = sprintf(
            "You have a new approval request.\n\nTask: %s\nType: %s\nEntity: %s\n\nPlease review at: %s",
            $task_no,
            $type,
            $entity_no,
            admin_url('admin.php?page=ksf_approvals&task=' . $task_no)
        );

        wp_mail($email, $subject, $message);
    }

    private function get_salesman_email($salesman_username)
    {
        $user = get_user_by('login', $salesman_username);
        return $user ? $user->user_email : null;
    }

    private function init_fa()
    {
        if (!file_exists($this->fa_path . '/includes/db.inc')) {
            return new \WP_Error('fa_not_found', 'FA not configured');
        }
        global $db;
        include_once $this->fa_path . '/includes/db.inc';
    }
}