# WordPress Customer Portal - Test Plan

## Document Information

| Field | Value |
|-------|-------|
| Document Title | Test Plan |
| Module | ksf_WP_CustomerPortal |
| Version | 1.0.0 |
| Author | KSF Development Team |
| Last Updated | May 2026 |

---

## 1. Test Overview

### 1.1 Scope

**In Scope:**
- REST API endpoints
- Shortcode rendering
- Authentication flows
- WooCommerce synchronization
- Salesman approval workflow

**Out of Scope:**
- WordPress core functionality
- FrontAccounting core functionality
- WooCommerce core functionality

---

## 2. Unit Test Cases

### 2.1 Authentication Tests

#### TC-AUTH-001: Valid Authentication

**Test ID:** TC-AUTH-001  
**Priority:** Critical

**Steps:**
1. POST to /ksf-portal/v1/auth with valid credentials
2. Assert response includes token
3. Assert response includes debtor_no

**Expected Result:** Authentication successful

---

#### TC-AUTH-002: Invalid Authentication

**Test ID:** TC-AUTH-002  
**Priority:** Critical

**Steps:**
1. POST to /ksf-portal/v1/auth with invalid password
2. Assert 401 status code
3. Assert error message returned

---

#### TC-AUTH-003: Link FA Account

**Test ID:** TC-AUTH-003  
**Priority:** High

**Steps:**
1. User with matching email logs in
2. Assert ksf_fa_debtor_no meta set
3. Assert debtor_no matches FA record

---

### 2.2 Ticket Tests

#### TC-TKT-001: Get Ticket List

**Test ID:** TC-TKT-001  
**Priority:** High

**Steps:**
1. Authenticate as customer with tickets
2. GET /ksf-portal/v1/tickets
3. Assert response is array
4. Assert each ticket has required fields

---

#### TC-TKT-002: Get Single Ticket

**Test ID:** TC-TKT-002  
**Priority:** High

**Steps:**
1. Authenticate as customer
2. GET /ksf-portal/v1/tickets/1
3. Assert response includes notes array
4. Assert ticket belongs to customer

---

#### TC-TKT-003: Create Ticket

**Test ID:** TC-TKT-003  
**Priority:** Critical

**Steps:**
1. Authenticate as customer
2. POST /ksf-portal/v1/tickets with subject, description
3. Assert ticket created
4. Assert ticket_number generated
5. Assert status is 'New'

---

#### TC-TKT-004: Create Ticket - Missing Subject

**Test ID:** TC-TKT-004  
**Priority:** High

**Steps:**
1. POST without subject
2. Assert 400 error
3. Assert error mentions required field

---

#### TC-TKT-005: Add Ticket Note

**Test ID:** TC-TKT-005  
**Priority:** Medium

**Steps:**
1. Authenticate as customer
2. POST note to ticket
3. Assert note added
4. Assert note associated with ticket

---

### 2.3 Invoice Tests

#### TC-INV-001: Get Invoice List

**Test ID:** TC-INV-001  
**Priority:** High

**Steps:**
1. Authenticate as customer with invoices
2. GET /ksf-portal/v1/invoices
3. Assert response is array
4. Assert invoices belong to customer

---

### 2.4 Order Tests

#### TC-ORD-001: Get Order List

**Test ID:** TC-ORD-001  
**Priority:** Medium

**Steps:**
1. Authenticate as customer with orders
2. GET /ksf-portal/v1/orders
3. Assert response is array
4. Assert orders belong to customer

---

### 2.5 Profile Tests

#### TC-PROF-001: Get Profile

**Test ID:** TC-PROF-001  
**Priority:** High

**Steps:**
1. Authenticate as customer
2. GET /ksf-portal/v1/profile
3. Assert response includes user_id, email, debtor_no

---

#### TC-PROF-002: Update Profile

**Test ID:** TC-PROF-002  
**Priority:** High

**Steps:**
1. Authenticate as customer
2. PUT with new display_name, phone
3. Assert profile updated in WP
4. Assert profile updated in FA

---

### 2.6 WooCommerce Tests

#### TC-WOO-001: Sync Customer to WooCommerce

**Test ID:** TC-WOO-001  
**Priority:** Medium

**Pre-conditions:**
- WooCommerce active

**Steps:**
1. Authenticate as customer
2. POST /ksf-portal/v1/woo/sync
3. Assert WooCommerce customer created/updated
4. Assert woo_customer_id stored

---

#### TC-WOO-002: Sync Orders

**Test ID:** TC-WOO-002  
**Priority:** Low

**Steps:**
1. Authenticate as customer
2. GET /ksf-portal/v1/woo/orders
3. Assert orders synced with WooCommerce IDs

---

### 2.7 Approval Tests

#### TC-APR-001: Create Approval Task

**Test ID:** TC-APR-001  
**Priority:** Medium

**Steps:**
1. Create task via SalesmanApprovalService
2. Assert task created in database
3. Assert task_no generated
4. Assert status is 'Pending'

---

#### TC-APR-002: Get Pending Tasks

**Test ID:** TC-APR-002  
**Priority:** High

**Steps:**
1. Authenticate as salesman
2. GET /ksf-portal/v1/approvals
3. Assert tasks for assigned customer

---

#### TC-APR-003: Approve Task

**Test ID:** TC-APR-003  
**Priority:** High

**Steps:**
1. Authenticate as assigned salesman
2. Approve pending task
3. Assert status changes to 'Approved'
4. Assert changes applied

---

#### TC-APR-004: Reject Task

**Test ID:** TC-APR-004  
**Priority:** High

**Steps:**
1. Authenticate as assigned salesman
2. Reject pending task with reason
3. Assert status changes to 'Rejected'
4. Assert changes discarded

---

## 3. Integration Test Cases

### 3.1 E2E: Complete Customer Journey

**Test ID:** ITC-001  
**Priority:** Critical

**Scenario:**
1. Customer logs in
2. Views invoices
3. Creates support ticket
4. Adds note to ticket
5. Updates profile
6. Views announcements

**Expected Result:** All operations successful

---

### 3.2 E2E: Salesman Approval Flow

**Test ID:** ITC-002  
**Priority:** High

**Scenario:**
1. Customer requests profile change
2. Approval task created
3. Salesman reviews and approves
4. Changes applied to FA

**Expected Result:** Complete workflow functional

---

## 4. Test Data

### 4.1 Test Customer

```php
$testCustomer = [
    'email' => 'test@example.com',
    'debtor_no' => 'DEB-TEST-001',
    'name' => 'Test Customer',
    'address' => '123 Test St',
];
```

### 4.2 Test Salesman

```php
$testSalesman = [
    'user_login' => 'salesman1',
    'user_email' => 'salesman@example.com',
];
```

---

## 5. Traceability Matrix

| Requirement | Test Case(s) |
|-------------|--------------|
| FR-AUTH-001 | TC-AUTH-001, TC-AUTH-002 |
| FR-AUTH-002 | TC-AUTH-003 |
| FR-TKT-001 | TC-TKT-001 |
| FR-TKT-002 | TC-TKT-002 |
| FR-TKT-003 | TC-TKT-003, TC-TKT-004 |
| FR-INV-001 | TC-INV-001 |
| FR-ORD-001 | TC-ORD-001 |
| FR-PROF-001 | TC-PROF-001 |
| FR-PROF-002 | TC-PROF-002 |
| FR-WOO-001 | TC-WOO-001 |
| FR-APR-001 | TC-APR-001 |
| FR-APR-002 | TC-APR-002 |
| FR-APR-003 | TC-APR-003 |
| FR-APR-004 | TC-APR-004 |

---

## 6. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | May 2026 | KSF Development Team | Initial test plan |