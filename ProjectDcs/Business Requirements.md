# WordPress Customer Portal - Business Requirements

## Document Information

| Field | Value |
|-------|-------|
| Document Title | Business Requirements Specification |
| Module | ksf_WP_CustomerPortal |
| Version | 1.0.0 |
| Author | KSF Development Team |
| Last Updated | May 2026 |

---

## 1. Project Overview

### 1.1 Purpose Statement

The KSF Customer Portal is a WordPress plugin that connects WordPress users to FrontAccounting, providing customers with self-service access to their support tickets, invoices, orders, and documents. The portal bridges the gap between the e-commerce experience (WordPress/WooCommerce) and the business management system (FrontAccounting).

### 1.2 Problem Statement

Organizations using WordPress for their website and FrontAccounting for business management face several challenges:

- **Customer Fragmentation**: Customers manage accounts in multiple systems
- **Manual Data Entry**: Staff re-enter data between WordPress and FrontAccounting
- **Limited Customer Visibility**: Customers cannot self-service their needs
- **Synchronization Issues**: Data becomes inconsistent between platforms

The Customer Portal addresses these by providing:
- Unified customer experience via WordPress
- Real-time synchronization with FrontAccounting
- Self-service capabilities for support, invoices, and orders
- WooCommerce integration for e-commerce workflows

### 1.3 Module Positioning

```
WordPress Site
    └── ksf_WP_CustomerPortal (Plugin)
            ├── REST API Endpoints
            ├── Shortcodes
            ├── WooCommerce Sync
            └── Salesman Approval
            
ksf_CRM (Business Logic)
    └── ksf_FA_CRM (FA Adapter)
            
ksf_Service (Support Tickets - optional)
```

---

## 2. Scope Definition

### 2.1 In-Scope Features

#### 2.1.1 Authentication & Authorization
- WordPress user authentication
- FA debtor linking via email matching
- Extended auth cookie expiration (7 days)
- Password reset functionality
- Session management

#### 2.1.2 Support Tickets Integration
- List customer tickets
- View ticket details
- Create new tickets
- Add notes to tickets
- Ticket status tracking

#### 2.1.3 Invoice Access
- List customer invoices from FA
- Invoice details display
- Due date tracking
- Payment status

#### 2.1.4 Order Management
- List customer orders from FA
- Order status tracking
- Delivery information

#### 2.1.5 Customer Profile
- View/update WordPress profile
- View/update FA customer details
- Address management
- Contact preferences

#### 2.1.6 Announcements
- Display system announcements
- Priority-based filtering
- Target audience filtering (all/customer)

#### 2.1.7 Document Management
- List customer documents
- Document download links
- File type identification

#### 2.1.8 WooCommerce Synchronization
- Sync FA customers to WooCommerce
- Sync WooCommerce customers to FA
- Order synchronization
- Invoice creation

#### 2.1.9 Salesman Approval Workflow
- Create approval tasks
- Assign to assigned salesman
- Approve/reject workflows
- Email notifications

### 2.2 Out-of-Scope Features

- Direct payment processing (handled by WooCommerce)
- Inventory management
- Direct database access from portal
- Admin configuration UI
- Multi-language support (future)

### 2.3 Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[ksf_portal_tickets]` | Display ticket list |
| `[ksf_portal_ticket_detail]` | Display single ticket |
| `[ksf_portal_invoices]` | Display invoice list |
| `[ksf_portal_orders]` | Display order list |
| `[ksf_portal_ticket_form]` | New ticket submission form |
| `[ksf_portal_announcements]` | Display announcements |
| `[ksf_portal_profile]` | Customer profile form |
| `[ksf_portal_documents]` | Document list with downloads |

### 2.4 REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/ksf-portal/v1/auth` | Authenticate user |
| GET | `/ksf-portal/v1/tickets` | List tickets |
| GET | `/ksf-portal/v1/tickets/:id` | Get ticket details |
| POST | `/ksf-portal/v1/tickets` | Create ticket |
| POST | `/ksf-portal/v1/tickets/:id/notes` | Add note |
| GET | `/ksf-portal/v1/invoices` | List invoices |
| GET | `/ksf-portal/v1/orders` | List orders |
| GET | `/ksf-portal/v1/profile` | Get profile |
| PUT | `/ksf-portal/v1/profile` | Update profile |
| GET | `/ksf-portal/v1/announcements` | List announcements |
| GET | `/ksf-portal/v1/documents` | List documents |
| POST | `/ksf-portal/v1/password/reset` | Password reset |
| POST | `/ksf-portal/v1/woo/sync` | Sync customer to WooCommerce |
| GET | `/ksf-portal/v1/woo/orders` | Sync orders to WooCommerce |
| GET | `/ksf-portal/v1/woo/invoices` | Sync invoices to WooCommerce |
| GET | `/ksf-portal/v1/approvals` | Get pending approvals |

---

## 3. Integration Dependencies

### 3.1 WordPress Requirements

| Requirement | Version | Purpose |
|-------------|---------|---------|
| WordPress | 5.0+ | CMS platform |
| PHP | 7.4+ | Runtime |
| WooCommerce | Optional | E-commerce integration |

### 3.2 FrontAccounting Integration

| Requirement | Purpose |
|-------------|---------|
| FrontAccounting | 2.4+ | Business management |
| ksf_CRM | Latest | Customer data |
| ksf_Service | Latest | Support tickets |
| Database access | Direct query | Data synchronization |

### 3.3 Configuration

The plugin requires the following configuration:

```php
// wp-config.php or plugin settings
define('KSF_FA_PATH', '/var/www/html');  // Path to FA installation
```

---

## 4. User Roles and Permissions

### 4.1 Role Mapping

| WordPress Role | Portal Access | FA Mapping |
|---------------|---------------|-----------|
| Administrator | Full access | FA admin equivalent |
| Editor | Tickets, Invoices, Profile | FA customer |
| Author | Tickets, Profile | FA customer |
| Subscriber | Basic portal access | FA customer |

### 4.2 Access Control

- All portal access requires WordPress login
- FA debtor_no linked to WordPress user
- Shortcodes check authentication before display
- REST endpoints validate auth token

---

## 5. Data Flow

### 5.1 Customer Login Flow

```
WordPress Login
    │
    ▼
on_user_login hook triggered
    │
    ▼
sync_fa_user() called
    │
    ├── Get WordPress user email
    │
    ▼
find_fa_debtor_by_email()
    │
    ├── Query FA debtors_master
    │
    ▼
Link debtor_no to WP user meta
```

### 5.2 Ticket Creation Flow

```
Customer submits via shortcode/form
    │
    ▼
KSF_Portal_Shortcodes::create_ticket_via_api()
    │
    ▼
POST /ksf-portal/v1/tickets
    │
    ▼
KSF_Portal_API::create_ticket()
    │
    ├── Get debtor_no from user meta
    │
    ├── Generate ticket_number
    │
    ├── Insert into fa_st_tickets
    │
    ▼
Return ticket details
```

---

## 6. Security Considerations

### 6.1 Authentication

- WordPress cookies used for session
- REST API validates authentication
- Auth tokens hashed with user ID + timestamp
- Cookie expiration extended to 7 days

### 6.2 Authorization

- debtor_no from user meta prevents impersonation
- All queries scoped to authenticated user's debtor_no
- SQL injection prevented via db_escape()
- XSS prevented via esc_html() and esc_attr()

### 6.3 Data Protection

- Sensitive FA path configurable, not hardcoded
- Customer can only access own data
- Audit trail through ticket notes

---

## 7. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | May 2026 | KSF Development Team | Initial specification |