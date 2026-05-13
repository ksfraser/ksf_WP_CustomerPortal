# WordPress Customer Portal - Functional Requirements

## Document Information

| Field | Value |
|-------|-------|
| Document Title | Functional Requirements Specification |
| Module | ksf_WP_CustomerPortal |
| Version | 1.0.0 |
| Author | KSF Development Team |
| Last Updated | May 2026 |

---

## 1. Authentication Requirements

### 1.1 User Authentication

#### FR-AUTH-001: WordPress Authentication

**Description:** Users authenticate via WordPress login system.

**Pre-conditions:**
- WordPress user exists
- Valid credentials provided

**Post-conditions:**
- User logged in
- Session established

**Requirements:**
- Use `wp_signon()` for authentication
- Support "remember me" functionality
- Extend cookie expiration to 7 days

---

#### FR-AUTH-002: FA Account Linking

**Description:** Link WordPress user to FrontAccounting customer by email.

**Pre-conditions:**
- WordPress user logged in
- FA customer with matching email exists

**Post-conditions:**
- `ksf_fa_debtor_no` user meta populated

**Business Rules:**
- Email matching is case-insensitive
- Multiple WP users can link to same FA customer
- Linking happens on first login if not already linked

---

#### FR-AUTH-003: Password Reset

**Description:** Allow users to reset password via email.

**Pre-conditions:**
- Valid username or email provided

**Post-conditions:**
- Reset email sent
- Reset link generated

**Requirements:**
- Use WordPress `get_password_reset_key()`
- Send reset email with link
- Handle invalid users gracefully

---

## 2. Ticket Management

### 2.1 List Tickets

#### FR-TKT-001: Get Ticket List

**Description:** Retrieve all tickets for the logged-in customer.

**Pre-conditions:**
- User authenticated
- FA customer linked

**Post-conditions:**
- Ticket list returned

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| status | string | - | Filter by status (optional) |

**Output Format:**
```json
[
    {
        "id": 1,
        "ticket_number": "TKT-20260513-0001",
        "subject": "Order inquiry",
        "status": "New",
        "priority": "Medium",
        "type": "Question",
        "created_at": "2026-05-13 10:00:00"
    }
]
```

---

#### FR-TKT-002: Get Single Ticket

**Description:** Retrieve full ticket details including notes.

**Pre-conditions:**
- User authenticated
- Ticket exists
- Ticket belongs to user's customer

**Post-conditions:**
- Full ticket data returned

**Output Format:**
```json
{
    "id": 1,
    "ticket_number": "TKT-20260513-0001",
    "subject": "Order inquiry",
    "description": "I have a question about my order",
    "status": "Open",
    "priority": "Medium",
    "type": "Question",
    "created_at": "2026-05-13 10:00:00",
    "notes": [
        {
            "note": "Thank you for contacting us",
            "note_type": "Public",
            "created_by": "admin",
            "created_at": "2026-05-13 11:00:00"
        }
    ]
}
```

---

#### FR-TKT-003: Create Ticket

**Description:** Create a new support ticket.

**Pre-conditions:**
- User authenticated
- Required fields provided

**Post-conditions:**
- Ticket created in FA
- Ticket number returned

**Required Input:**
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| subject | string | Yes | Max 200 chars |
| description | string | Yes | Max 5000 chars |
| type | string | No | Question/Issue/Request/Bug |
| priority | string | No | Low/Medium/High/Critical |

**Business Rules:**
- Ticket number generated: TKT-YYYYMMDD-XXXX
- Status defaults to 'New'
- debtor_no from logged-in user
- created_by from WP user login

---

#### FR-TKT-004: Add Ticket Note

**Description:** Add a note to an existing ticket.

**Pre-conditions:**
- User authenticated
- Ticket exists
- Ticket belongs to user

**Post-conditions:**
- Note added to ticket

**Required Input:**
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| note | string | Yes | Max 2000 chars |
| note_type | string | No | Public/Internal |

---

## 3. Invoice Management

### 3.1 List Invoices

#### FR-INV-001: Get Invoice List

**Description:** Retrieve all invoices for the customer.

**Pre-conditions:**
- User authenticated
- FA customer linked

**Post-conditions:**
- Invoice list returned

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| limit | int | 20 | Max records to return |

**Output Format:**
```json
[
    {
        "id": 1001,
        "type": 10,
        "reference": "INV-2026-001",
        "trans_date": "2026-05-01",
        "due_date": "2026-05-31",
        "total": 1500.00,
        "ov_discount": 0.00,
        "due_value": 1500.00
    }
]
```

**Business Rules:**
- Filter by debtor_no
- Type 10 = Sales Invoice
- Order by trans_date DESC

---

## 4. Order Management

### 4.1 List Orders

#### FR-ORD-001: Get Order List

**Description:** Retrieve all orders for the customer.

**Pre-conditions:**
- User authenticated
- FA customer linked

**Post-conditions:**
- Order list returned

**Output Format:**
```json
[
    {
        "id": 5001,
        "order_no": 5001,
        "reference": "SO-2026-001",
        "ord_date": "2026-05-10",
        "delivery_date": "2026-05-15",
        "total": 500.00,
        "status": "Completed"
    }
]
```

---

## 5. Profile Management

### 5.1 Get Profile

#### FR-PROF-001: Get Customer Profile

**Description:** Retrieve combined WordPress and FA profile data.

**Pre-conditions:**
- User authenticated

**Post-conditions:**
- Profile data returned

**Output Format:**
```json
{
    "user_id": 1,
    "email": "customer@example.com",
    "display_name": "John Customer",
    "debtor_no": "DEB-001",
    "phone": "555-1234",
    "address": "123 Main St",
    "city": "Springfield",
    "state": "IL",
    "postal_code": "62701",
    "country": "US"
}
```

---

#### FR-PROF-002: Update Profile

**Description:** Update WordPress and FA customer information.

**Pre-conditions:**
- User authenticated
- FA customer linked

**Post-conditions:**
- Profile updated in WP and FA

**Input Fields:**
| Field | Type | Required | Update Target |
|-------|------|----------|--------------|
| display_name | string | No | WordPress |
| phone | string | No | WordPress meta + FA |
| address | string | No | FA debtors_master |
| city | string | No | FA debtors_master |
| state | string | No | FA debtors_master |
| postal_code | string | No | FA debtors_master |
| country | string | No | FA debtors_master |

---

## 6. Announcements

### 6.1 List Announcements

#### FR-ANN-001: Get Announcements

**Description:** Retrieve active announcements for the customer.

**Pre-conditions:**
- User authenticated

**Post-conditions:**
- Announcement list returned

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| limit | int | 20 | Max records |

**Output Format:**
```json
[
    {
        "id": 1,
        "title": "System Maintenance",
        "content": "Scheduled maintenance on May 15",
        "priority": "High",
        "created_at": "2026-05-10 00:00:00"
    }
]
```

**Business Rules:**
- Only active announcements (active = 1)
- Only non-expired (expiry_date >= CURDATE or NULL)
- Only target_type 'all' or 'customer'

---

## 7. Documents

### 7.1 List Documents

#### FR-DOC-001: Get Document List

**Description:** Retrieve customer documents.

**Pre-conditions:**
- User authenticated
- FA customer linked

**Post-conditions:**
- Document list returned

**Output Format:**
```json
[
    {
        "id": 1,
        "name": "Invoice PDF",
        "filename": "invoice-2026-001.pdf",
        "file_type": "application/pdf",
        "created_at": "2026-05-01 00:00:00"
    }
]
```

---

## 8. WooCommerce Integration

### 8.1 Customer Sync

#### FR-WOO-001: Sync FA Customer to WooCommerce

**Description:** Create or update WooCommerce customer from FA data.

**Pre-conditions:**
- User authenticated
- FA customer linked

**Post-conditions:**
- WP user linked to WooCommerce customer
- WooCommerce customer ID stored in meta

**Output:**
```json
{
    "user_id": 1,
    "woo_customer_id": 1234,
    "debtor_no": "DEB-001"
}
```

---

#### FR-WOO-002: Sync WooCommerce Customer to FA

**Description:** Create or update FA customer from WooCommerce data.

**Pre-conditions:**
- WooCommerce customer ID provided

**Post-conditions:**
- FA debtor created/updated

**Business Rules:**
- Match by email
- Create debtor if not found
- Link WP user to new debtor

---

### 8.2 Order Sync

#### FR-WOO-003: Sync Orders to WooCommerce

**Description:** Export FA orders to WooCommerce.

**Pre-conditions:**
- User authenticated
- FA customer linked

**Post-conditions:**
- Orders exported with WooCommerce IDs

---

### 8.3 Invoice Sync

#### FR-WOO-004: Sync Invoices to WooCommerce

**Description:** Export FA invoices with payment links.

**Pre-conditions:**
- User authenticated
- FA customer linked

**Post-conditions:**
- Invoice data available for WooCommerce

---

## 9. Salesman Approval Workflow

### 9.1 Create Approval Task

#### FR-APR-001: Create Approval Task

**Description:** Route customer changes for salesman approval.

**Pre-conditions:**
- User authenticated
- Salesman assigned to customer

**Post-conditions:**
- Approval task created
- Salesman notified

**Input:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| type | string | Yes | debtor_update/order_update/invoice_update |
| entity_no | string | Yes | The entity being changed |
| data | array | Yes | The proposed changes |

---

#### FR-APR-002: Get Pending Tasks

**Description:** Get approval tasks assigned to salesman.

**Pre-conditions:**
- User is assigned salesman

**Post-conditions:**
- Task list returned

---

#### FR-APR-003: Approve Task

**Description:** Approve a pending task and apply changes.

**Pre-conditions:**
- User is assigned salesman
- Task exists and is pending

**Post-conditions:**
- Task status changed to Approved
- Changes applied to entity

---

#### FR-APR-004: Reject Task

**Description:** Reject a pending task.

**Pre-conditions:**
- User is assigned salesman
- Task exists and is pending

**Post-conditions:**
- Task status changed to Rejected
- Changes discarded

---

## 10. Non-Functional Requirements

### 10.1 Performance

| Requirement | Target | Description |
|-------------|--------|-------------|
| API response time | < 500ms | All endpoints |
| Shortcode render time | < 200ms | Page rendering |
| Sync operations | < 2s | WooCommerce sync |

### 10.2 Security

| Requirement | Implementation |
|-------------|----------------|
| SQL injection | db_escape() on all inputs |
| XSS prevention | esc_html(), esc_attr() on output |
| Authentication | WordPress auth check on all endpoints |
| Authorization | debtor_no scoped queries |

---

## 11. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | May 2026 | KSF Development Team | Initial specification |