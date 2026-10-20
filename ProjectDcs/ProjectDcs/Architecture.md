# WordPress Customer Portal - Architecture

## Document Information

| Field | Value |
|-------|-------|
| Document Title | Technical Architecture Specification |
| Module | ksf_WP_CustomerPortal |
| Version | 1.0.0 |
| Author | KSF Development Team |
| Last Updated | May 2026 |

---

## 1. Architecture Overview

### 1.1 Design Philosophy

The KSF Customer Portal follows the WordPress plugin architecture with service-based integration to FrontAccounting. It uses the standard WordPress patterns:
- **Plugin structure** with activation/deactivation hooks
- **REST API** for programmatic access
- **Shortcodes** for content embedding
- **Service classes** for FA integration

### 1.2 Module Structure

```
ksf_WP_CustomerPortal/
├── ksf-customer-portal.php          # Main plugin class
├── admin/
│   └── settings.php                 # Admin settings page
├── includes/
│   ├── class-ksf-portal-autoloader.php
│   ├── class-ksf-portal-api.php      # REST API handlers
│   ├── class-ksf-portal-auth.php     # Authentication utilities
│   ├── class-ksf-portal-announcements.php
│   ├── class-ksf-portal-shortcodes.php
│   ├── import.php
│   └── services/
│       ├── class-ksf-salesman-approval.php
│       └── class-ksf-woo-sync.php
├── assets/
│   ├── portal.css                    # Frontend styles
│   └── portal.js                     # Frontend scripts
├── public/                           # Public assets
└── ProjectDcs/
    └── ProjectDcs/
```

### 1.3 Component Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    KSF_Customer_Portal                          │
│                    (Singleton Plugin)                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌───────────────┐    ┌───────────────┐    ┌───────────────┐   │
│  │ REST Routes   │    │ Shortcodes    │    │ Hooks         │   │
│  │ (API)         │    │ (Content)     │    │ (Lifecycle)   │   │
│  └───────┬───────┘    └───────┬───────┘    └───────────────┘   │
│          │                    │                                 │
│          ▼                    ▼                                 │
│  ┌───────────────┐    ┌───────────────┐                        │
│  │ KSF_Portal_API │◀───│ KSF_Portal_  │                        │
│  │               │    │ Shortcodes    │                        │
│  └───────┬───────┘    └───────────────┘                        │
│          │                                                       │
│          ▼                                                       │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │                    Services                                  ││
│  ├─────────────────────────────────────────────────────────────┤│
│  │  KSF_Portal_Auth       │ WooCommerceSyncService             ││
│  │  - require_login()     │ - sync_customer_to_woo()          ││
│  │  - link_account()      │ - sync_woo_customer_to_fa()       ││
│  │  - has_customer_access()│ - sync_orders_to_woo()           ││
│  ├─────────────────────────────────────────────────────────────┤│
│  │  SalesmanApprovalService                                     ││
│  │  - create_approval_task()                                   ││
│  │  - get_pending_tasks()                                     ││
│  │  - approve_task() / reject_task()                          ││
│  └─────────────────────────────────────────────────────────────┘│
│          │                                                       │
│          ▼                                                       │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │               FrontAccounting Integration                    ││
│  │               (Direct DB Query via db.inc)                   ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Core Components

### 2.1 KSF_Customer_Portal (Plugin Main Class)

**Singleton pattern for plugin initialization.**

```php
class KSF_Customer_Portal
{
    private static $instance = null;
    
    public static function get_instance();
    
    private function __construct()
    {
        // Register REST routes
        add_action('init', [$this, 'register_rest_routes']);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Login hook
        add_action('wp_login', [$this, 'on_user_login'], 10, 2);
        
        // Cookie extension
        add_filter('auth_cookie_expiration', [$this, 'extend_auth_cookie'], 10, 2);
        
        // Register shortcodes
        add_shortcode('ksf_portal_tickets', ...);
        // ... more shortcodes
    }
}
```

### 2.2 KSF_Portal_API

**REST API handler for all data operations.**

```php
class KSF_Portal_API
{
    // Authentication
    public static function authenticate($request);
    public static function check_auth($request);
    
    // Tickets
    public static function get_tickets($request);
    public static function get_ticket($request);
    public static function create_ticket($request);
    public static function add_ticket_note($request);
    
    // Documents
    public static function get_invoices($request);
    public static function get_orders($request);
    public static function get_documents($request);
    
    // Profile
    public static function get_profile($request);
    public static function update_profile($request);
    
    // Utilities
    public static function get_announcements($request);
    public static function reset_password($request);
    public static function get_debtor_no($user_id = null);
    
    private static function find_fa_debtor($email);
}
```

### 2.3 KSF_Portal_Shortcodes

**Content rendering via WordPress shortcodes.**

```php
class KSF_Portal_Shortcodes
{
    // Shortcode handlers
    public static function render_tickets($atts, $content = null);
    public static function render_ticket_detail($atts, $content = null);
    public static function render_invoices($atts, $content = null);
    public static function render_orders($atts, $content = null);
    public static function render_ticket_form($atts, $content = null);
    public static function render_announcements($atts, $content = null);
    public static function render_profile($atts, $content = null);
    public static function render_documents($atts, $content = null);
    
    // API helper methods
    private static function get_tickets_via_api($status, $limit);
    private static function get_ticket_via_api($id);
    // ... more helpers
}
```

### 2.4 KSF_Portal_Auth

**Authentication utilities and account linking.**

```php
class KSF_Portal_Auth
{
    public static function require_login();
    public static function get_user_customer_id($user_id = null);
    public static function link_account($wp_user_id, $debtor_no);
    public static function unlink_account($wp_user_id);
    public static function has_customer_access($wp_user_id = null);
}
```

---

## 3. Service Components

### 3.1 WooCommerceSyncService

**Bidirectional synchronization between FA and WooCommerce.**

```php
namespace Ksfraser\WooCommerce;

class WooCommerceSyncService
{
    // FA to WooCommerce
    public function sync_customer_to_woo($debtor_no);
    public function sync_orders_to_woo($debtor_no);
    public function sync_invoices_to_woo($debtor_no);
    
    // WooCommerce to FA
    public function sync_woo_customer_to_fa($woo_customer_id);
    
    // Payment integration
    public function create_payment_link($invoice_no, $woo_order_id = null);
    
    // Internal helpers
    private function init_fa();
    private function find_wp_user_by_debtor($debtor_no);
    private function create_wp_user($debtor);
    private function create_woo_customer($debtor);
    // ... more helpers
}
```

### 3.2 SalesmanApprovalService

**Approval workflow for customer-initiated changes.**

```php
namespace Ksfraser\Salesman;

class SalesmanApprovalService
{
    public function create_approval_task($type, $entity_no, $data, $requesting_user_id);
    public function get_pending_tasks($salesman_username);
    public function approve_task($task_no, $salesman_username, $comments = '');
    public function reject_task($task_no, $salesman_username, $comments = '');
    public function get_salesman_for_customer($debtor_no);
    public function assign_salesman($debtor_no, $salesman_username);
    
    private function update_task_status($task_no, $salesman_username, $status, $comments);
    private function apply_changes($type, $entity_no, $data);
    private function notify_salesman($salesman_username, $task_no, $type, $entity_no);
}
```

---

## 4. Data Flow Architecture

### 4.1 REST API Request Flow

```
Client Request
    │
    ▼
WordPress Router (wp_rest)
    │
    ▼
ksf-portal/v1 route matched
    │
    ▼
KSF_Portal_API::method()
    │
    ├── Authenticate (check_auth)
    │
    ├── Get debtor_no from WP user
    │
    ├── Init FA database
    │   └── include_once FA_PATH/includes/db.inc
    │
    ├── Execute FA query
    │   └── db_query() with db_escape()
    │
    ▼
Return JSON response
```

### 4.2 Shortcode Render Flow

```
Page Load with Shortcode
    │
    ▼
KSF_Portal_Shortcodes::render_*()
    │
    ├── Check authentication
    │
    ├── Call REST API via wp_remote_get/post
    │
    ├── Receive JSON response
    │
    ├── Parse response data
    │
    ▼
Render HTML template
    │
    ├── Loop through data
    │
    ├── Apply CSS classes (ksf-*)
    │
    └── Return HTML string
```

### 4.3 WooCommerce Sync Flow

```
Trigger (Webhook or Manual)
    │
    ▼
WooCommerceSyncService::sync_*()
    │
    ├── Init FA database
    │
    ├── Get source data
    │   └── FA debtor or WooCommerce customer
    │
    ├── Transform data format
    │
    ├── Create/update target record
    │
    │   ├── Create WP user if needed
    │   ├── Link debtor_no to user meta
    │   └── Create WooCommerce customer
    │
    ▼
Return sync result
```

---

## 5. Database Schema

### 5.1 FrontAccounting Tables (Read/Write)

| Table | Purpose | Access |
|-------|---------|--------|
| `debtors_master` | Customer records | Read/Write |
| `ksf_supporttickets_tickets` | Support tickets | Read/Write |
| `ksf_supporttickets_tickets_notes` | Ticket notes | Read/Write |
| `debtor_trans` | Invoices/transactions | Read |
| `sales_orders` | Sales orders | Read |
| `ksf_customerportal_announcements` | System announcements | Read |
| `ksf_customerportal_customer_documents` | Customer documents | Read |
| `ksf_customerportal_approval_tasks` | Approval workflow | Read/Write |

### 5.2 WordPress Tables (Used)

| Table | Purpose | Access |
|-------|---------|--------|
| `wp_users` | WordPress users | Read/Write |
| `wp_usermeta` | User metadata | Read/Write |
| `wp_{prefix}postmeta` | Document links | Read/Write |

### 5.3 User Metadata

| Meta Key | Purpose |
|----------|---------|
| `ksf_fa_debtor_no` | Linked FA customer ID |
| `ksf_woo_customer_id` | Linked WooCommerce customer ID |
| `billing_phone` | Customer phone |

---

## 6. Security Implementation

### 6.1 SQL Injection Prevention

```php
// Always use db_escape for user input
$sql = "SELECT * FROM " . TB_PREF . "debtors_master 
    WHERE debtor_no = " . db_escape($debtor_no);

// String values escaped
$sql = "UPDATE debtors_master 
    SET name = " . db_escape($name) . "
    WHERE debtor_no = " . db_escape($debtor_no);
```

### 6.2 XSS Prevention

```php
// In shortcode templates
echo esc_html($ticket['subject']);
echo esc_attr($ticket['ticket_number']);

// URL generation
echo esc_url($download_url);
```

### 6.3 Authentication Flow

```php
// REST endpoint authentication
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
```

---

## 7. Dependencies

### 7.1 WordPress Core

| File | Purpose |
|------|---------|
| `wp-includes/pluggable.php` | wp_signon, wp_get_current_user |
| `wp-includes/user.php` | get_userdata, wp_update_user |
| `wp-includes/rest-api.php` | register_rest_route |
| `wp-includes/functions.php` | wp_remote_get, wp_remote_post |

### 7.2 WooCommerce (Optional)

| Class | Purpose |
|-------|---------|
| `WC_Customer` | Customer data |
| `WC()->checkout()` | Order creation |

### 7.3 FrontAccounting

| File | Purpose |
|------|---------|
| `FA_PATH/includes/db.inc` | Database functions |

---

## 8. Error Handling

### 8.1 Error Response Format

```php
return new WP_Error('auth_failed', $user->get_error_message(), ['status' => 401]);
return new WP_Error('no_customer', 'No linked customer found', ['status' => 404]);
return new WP_Error('fa_not_found', 'FA not configured', ['status' => 500]);
```

### 8.2 Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `auth_failed` | 401 | Authentication failed |
| `no_customer` | 404 | No FA customer linked |
| `fa_not_found` | 500 | FA not configured |
| `missing_field` | 400 | Required field missing |
| `not_found` | 404 | Resource not found |
| `email_failed` | 500 | Email sending failed |

---

## 9. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | May 2026 | KSF Development Team | Initial specification |