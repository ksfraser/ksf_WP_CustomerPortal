<?php

class KSF_Portal_Announcements
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

    public function get_announcements($debtor_no = null, $limit = 20)
    {
        $this->init_fa();

        $sql = "SELECT * FROM " . TB_PREF . "fa_announcements 
            WHERE active = 1 
            AND (expiry_date IS NULL OR expiry_date >= CURDATE())
            ORDER BY created_at DESC 
            LIMIT " . db_escape($limit);

        $result = db_query($sql, "Could not get announcements");
        $announcements = [];

        while ($row = db_fetch_assoc($result)) {
            if ($row['target_type'] === 'all' || $row['target_type'] === 'customer') {
                if ($row['target_type'] === 'customer' && $debtor_no) {
                    if ($this->is_targeted_customer($row['id'], $debtor_no)) {
                        $announcements[] = $this->format_announcement($row);
                    }
                } else {
                    $announcements[] = $this->format_announcement($row);
                }
            }
        }

        return $announcements;
    }

    public function get_announcement($id)
    {
        $this->init_fa();

        $sql = "SELECT * FROM " . TB_PREF . "fa_announcements 
            WHERE id = " . db_escape($id) . " AND active = 1";

        $result = db_query($sql, "Could not get announcement");
        $row = db_fetch_assoc($result);

        return $row ? $this->format_announcement($row) : null;
    }

    public function create_announcement($data)
    {
        $this->init_fa();

        $id = db_insert(TB_PREF . "fa_announcements");
        
        $sql = "INSERT INTO " . TB_PREF . "fa_announcements
            (id, title, content, target_type, target_value, priority, active, created_by, created_at)
            VALUES (
                " . db_escape($id) . ",
                " . db_escape($data['title']) . ",
                " . db_escape($data['content']) . ",
                " . db_escape($data['target_type'] ?? 'all') . ",
                " . db_escape($data['target_value'] ?? '') . ",
                " . db_escape($data['priority'] ?? 'normal') . ",
                " . db_escape($data['active'] ?? 1) . ",
                " . db_escape($data['created_by'] ?? 'system') . ",
                NOW()
            )";

        db_query($sql, "Could not create announcement");

        return ['id' => $id, 'title' => $data['title']];
    }

    private function format_announcement($row)
    {
        return [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'content' => $row['content'],
            'priority' => $row['priority'],
            'created_at' => $row['created_at'],
            'expiry_date' => $row['expiry_date'],
        ];
    }

    private function is_targeted_customer($announcement_id, $debtor_no)
    {
        $this->init_fa();

        $sql = "SELECT COUNT(*) as cnt FROM " . TB_PREF . "fa_announcement_targets
            WHERE announcement_id = " . db_escape($announcement_id) . "
            AND debtor_no = " . db_escape($debtor_no);

        $result = db_query($sql, "Could not check target");
        $row = db_fetch_assoc($result);

        return $row && $row['cnt'] > 0;
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