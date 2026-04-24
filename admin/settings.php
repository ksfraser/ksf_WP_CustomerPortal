<?php

add_action('admin_menu', 'ksf_portal_admin_menu');

function ksf_portal_admin_menu()
{
    add_options_page(
        'KSF Customer Portal',
        'KSF Portal',
        'manage_options',
        'ksf-portal-settings',
        'ksf_portal_settings_page'
    );
}

function ksf_portal_settings_page()
{
    if (isset($_POST['ksf_save_settings'])) {
        check_admin_referer('ksf_save_settings');
        
        update_option('ksf_fa_path', sanitize_text_field($_POST['fa_path']));
        update_option('ksf_fa_db_user', sanitize_text_field($_POST['fa_db_user']));
        update_option('ksf_fa_db_pass', sanitize_text_field($_POST['fa_db_pass']));
        
        echo '<div class="updated"><p>Settings saved!</p></div>';
    }
    
    $fa_path = get_option('ksf_fa_path', '/var/www/html');
    $db_user = get_option('ksf_fa_db_user', '');
    $db_pass = get_option('ksf_fa_db_pass', '');
    ?>
    <div class="wrap">
        <h1>KSF Customer Portal Settings</h1>
        
        <form method="post">
            <?php wp_nonce_field('ksf_save_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th>FrontAccounting Path</th>
                    <td>
                        <input type="text" name="fa_path" value="<?php echo esc_attr($fa_path); ?>" class="regular-text">
                        <p class="description">Full path to FrontAccounting installation (e.g., /var/www/html)</p>
                    </td>
                </tr>
                
                <tr>
                    <th>DB Username (optional)</th>
                    <td>
                        <input type="text" name="fa_db_user" value="<?php echo esc_attr($db_user); ?>" class="regular-text">
                        <p class="description">If FA uses different DB credentials</p>
                    </td>
                </tr>
                
                <tr>
                    <th>DB Password (optional)</th>
                    <td>
                        <input type="password" name="fa_db_pass" value="<?php echo esc_attr($db_pass); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="ksf_save_settings" class="button button-primary">Save Settings</button>
            </p>
        </form>
        
        <hr>
        
        <h2>Shortcodes</h2>
        <table class="form-table">
            <tr>
                <th>[ksf_portal_tickets]</th>
                <td>Display customer's support tickets list</td>
            </tr>
            <tr>
                <th>[ksf_portal_ticket_detail]</th>
                <td>Display individual ticket detail (use ?ticket_id=123)</td>
            </tr>
            <tr>
                <th>[ksf_portal_invoices]</th>
                <td>Display customer's invoices</td>
            </tr>
            <tr>
                <th>[ksf_portal_orders]</th>
                <td>Display customer's orders</td>
            </tr>
            <tr>
                <th>[ksf_portal_ticket_form]</th>
                <td>Display ticket creation form</td>
            </tr>
        </table>
        
        <hr>
        
        <h2>API Endpoints</h2>
        <table class="form-table">
            <tr>
                <th>POST /wp-json/ksf-portal/v1/auth</th>
                <td>Authenticate user <code>{"username": "user", "password": "pass"}</code></td>
            </tr>
            <tr>
                <th>GET /wp-json/ksf-portal/v1/tickets</th>
                <td>Get customer's tickets</td>
            </tr>
            <tr>
                <th>GET /wp-json/ksf-portal/v1/tickets/{id}</th>
                <td>Get single ticket</td>
            </tr>
            <tr>
                <th>POST /wp-json/ksf-portal/v1/tickets</th>
                <td>Create new ticket</td>
            </tr>
            <tr>
                <th>GET /wp-json/ksf-portal/v1/invoices</th>
                <td>Get invoices</td>
            </tr>
            <tr>
                <th>GET /wp-json/ksf-portal/v1/orders</th>
                <td>Get orders</td>
            </tr>
        </table>
    </div>
    <?php
}