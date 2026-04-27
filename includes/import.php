<?php

function ksf_portal_register_import_shortcodes()
{
    add_shortcode('ksf_import_employees', function() {
        $target_fields = [
            'emp_no', 'first_name', 'last_name', 'email', 
            'phone', 'department', 'position', 'location', 'joined_date'
        ];
        
        $ui = ImportUI::create([
            'module' => 'employee',
            'target_fields' => $target_fields,
            'upload_dir' => wp_upload_dir()['path'],
            'processor' => function($row) {
                global $wpdb;
                
                $emp_no = $row['emp_no'] ?? '';
                if (empty($emp_no)) return false;
                
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}ksf_employees WHERE emp_no = %s",
                    $emp_no
                ));
                
                if ($existing) {
                    $wpdb->update(
                        $wpdb->prefix . 'ksf_employees',
                        $row,
                        ['emp_no' => $emp_no]
                    );
                } else {
                    $wpdb->insert($wpdb->prefix . 'ksf_employees', $row);
                }
                
                return ['emp_no' => $emp_no];
            }
        ]);
        
        return $ui->renderShortcode($target_fields, 'employee');
    });
    
    add_shortcode('ksf_import_customers', function() {
        $target_fields = [
            'debtor_no', 'name', 'email', 'phone', 
            'address', 'city', 'state', 'postal_code', 'country'
        ];
        
        $ui = ImportUI::create([
            'module' => 'customer',
            'target_fields' => $target_fields,
        ]);
        
        return $ui->renderShortcode($target_fields, 'customer');
    });
}

add_action('init', 'ksf_portal_register_import_shortcodes');