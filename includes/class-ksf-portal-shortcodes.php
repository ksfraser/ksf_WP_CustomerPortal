<?php

class KSF_Portal_Shortcodes
{
    public static function render_tickets($atts, $content = null)
    {
        $atts = shortcode_atts([
            'status' => '',
            'limit' => 20,
        ], $atts);

        self::require_login();

        ob_start();
        
        $tickets = self::get_tickets_via_api($atts['status'], $atts['limit']);
        
        if (is_wp_error($tickets)) {
            echo '<div class="ksf-error">' . $tickets->get_error_message() . '</div>';
            return ob_get_clean();
        }

        if (empty($tickets)) {
            echo '<p class="ksf-no-tickets">No tickets found.</p>';
            return ob_get_clean();
        }
        ?>
        <div class="ksf-ticket-list">
            <table class="ksf-tickets-table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><a href="?ticket_id=<?php echo esc_attr($ticket['id']); ?>"><?php echo esc_html($ticket['ticket_number']); ?></a></td>
                        <td><?php echo esc_html($ticket['subject']); ?></td>
                        <td><span class="ksf-status <?php echo esc_attr($ticket['status']); ?>"><?php echo esc_html($ticket['status']); ?></span></td>
                        <td><?php echo esc_html($ticket['priority']); ?></td>
                        <td><?php echo esc_html(date('M j, Y', strtotime($ticket['created_at']))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_ticket_detail($atts, $content = null)
    {
        self::require_login();

        $ticket_id = isset($_GET['ticket_id']) ? (int) $_GET['ticket_id'] : 0;
        
        if (!$ticket_id) {
            return '<p>No ticket selected.</p>';
        }

        $ticket = self::get_ticket_via_api($ticket_id);
        
        if (is_wp_error($ticket)) {
            return '<div class="ksf-error">' . $ticket->get_error_message() . '</div>';
        }
        ?>
        <div class="ksf-ticket-detail">
            <h2><?php echo esc_html($ticket['ticket_number']); ?>: <?php echo esc_html($ticket['subject']); ?></h2>
            
            <div class="ksf-ticket-meta">
                <p><strong>Status:</strong> <?php echo esc_html($ticket['status']); ?></p>
                <p><strong>Priority:</strong> <?php echo esc_html($ticket['priority']); ?></p>
                <p><strong>Type:</strong> <?php echo esc_html($ticket['type']); ?></p>
                <p><strong>Created:</strong> <?php echo esc_html($ticket['created_at']); ?></p>
            </div>
            
            <div class="ksf-ticket-description">
                <h3>Description</h3>
                <p><?php echo nl2br(esc_html($ticket['description'])); ?></p>
            </div>
            
            <?php if (!empty($ticket['notes'])): ?>
            <div class="ksf-ticket-notes">
                <h3>Notes</h3>
                <?php foreach ($ticket['notes'] as $note): ?>
                <div class="ksf-note">
                    <p><?php echo nl2br(esc_html($note['note'])); ?></p>
                    <small><?php echo esc_html($note['created_by']); ?> - <?php echo esc_html($note['created_at']); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_invoices($atts, $content = null)
    {
        $atts = shortcode_atts([
            'limit' => 20,
        ], $atts);

        self::require_login();

        $invoices = self::get_invoices_via_api($atts['limit']);
        
        if (is_wp_error($invoices)) {
            return '<div class="ksf-error">' . $invoices->get_error_message() . '</div>';
        }

        if (empty($invoices)) {
            return '<p class="ksf-no-invoices">No invoices found.</p>';
        }
        ?>
        <div class="ksf-invoice-list">
            <table class="ksf-invoices-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th>Total</th>
                        <th>Due</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><?php echo esc_html($inv['reference'] ?? $inv['id']); ?></td>
                        <td><?php echo esc_html($inv['trans_date']); ?></td>
                        <td><?php echo esc_html($inv['due_date']); ?></td>
                        <td><?php echo esc_html(format_number($inv['total'])); ?></td>
                        <td><?php echo esc_html(format_number($inv['due_value'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_orders($atts, $content = null)
    {
        $atts = shortcode_atts([
            'limit' => 20,
        ], $atts);

        self::require_login();

        $orders = self::get_orders_via_api($atts['limit']);
        
        if (is_wp_error($orders)) {
            return '<div class="ksf-error">' . $orders->get_error_message() . '</div>';
        }

        if (empty($orders)) {
            return '<p class="ksf-no-orders">No orders found.</p>';
        }
        ?>
        <div class="ksf-order-list">
            <table class="ksf-orders-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo esc_html($order['order_no']); ?></td>
                        <td><?php echo esc_html($order['ord_date']); ?></td>
                        <td><?php echo esc_html(format_number($order['total'])); ?></td>
                        <td><?php echo esc_html($order['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_ticket_form($atts, $content = null)
    {
        self::require_login();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ksf_ticket_submit'])) {
            $result = self::create_ticket_via_api($_POST);
            
            if (!is_wp_error($result)) {
                echo '<div class="ksf-success">Ticket created: ' . esc_html($result['ticket_number']) . '</div>';
            } else {
                echo '<div class="ksf-error">' . esc_html($result->get_error_message()) . '</div>';
            }
        }
        ?>
        <form method="post" class="ksf-ticket-form">
            <?php wp_nonce_field('ksf_create_ticket', 'ksf_nonce'); ?>
            
            <p>
                <label>Subject *</label><br>
                <input type="text" name="subject" required>
            </p>
            
            <p>
                <label>Type</label><br>
                <select name="type">
                    <option value="Question">Question</option>
                    <option value="Issue">Issue</option>
                    <option value="Request">Request</option>
                    <option value="Bug">Bug</option>
                </select>
            </p>
            
            <p>
                <label>Priority</label><br>
                <select name="priority">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </p>
            
            <p>
                <label>Description *</label><br>
                <textarea name="description" rows="5" required></textarea>
            </p>
            
            <p>
                <button type="submit" name="ksf_ticket_submit" class="button button-primary">Submit Ticket</button>
            </p>
        </form>
        <?php
        return ob_get_clean();
    }

    private static function get_tickets_via_api($status = '', $limit = 20)
    {
        $api_url = get_rest_url(null, 'ksf-portal/v1/tickets');
        
        if ($status) {
            $api_url = add_query_arg('status', $status, $api_url);
        }
        
        $response = wp_remote_get($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . wp_get_current_user()->ID,
            ],
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private static function get_ticket_via_api($id)
    {
        $api_url = get_rest_url(null, 'ksf-portal/v1/tickets/' . $id);
        
        $response = wp_remote_get($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . wp_get_current_user()->ID,
            ],
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private static function get_invoices_via_api($limit = 20)
    {
        $api_url = get_rest_url(null, 'ksf-portal/v1/invoices');
        $api_url = add_query_arg('limit', $limit, $api_url);
        
        $response = wp_remote_get($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . wp_get_current_user()->ID,
            ],
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private static function get_orders_via_api($limit = 20)
    {
        $api_url = get_rest_url(null, 'ksf-portal/v1/orders');
        $api_url = add_query_arg('limit', $limit, $api_url);
        
        $response = wp_remote_get($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . wp_get_current_user()->ID,
            ],
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private static function create_ticket_via_api($data)
    {
        $api_url = get_rest_url(null, 'ksf-portal/v1/tickets');
        
        $response = wp_remote_post($api_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . wp_get_current_user()->ID,
            ],
            'body' => json_encode([
                'subject' => $data['subject'],
                'description' => $data['description'],
                'type' => $data['type'],
                'priority' => $data['priority'],
            ]),
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}