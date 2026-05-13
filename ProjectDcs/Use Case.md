# WordPress Customer Portal - Use Cases

## Document Information

| Field | Value |
|-------|-------|
| Document Title | Use Case Specification |
| Module | ksf_WP_CustomerPortal |
| Version | 1.0.0 |
| Author | KSF Development Team |
| Last Updated | May 2026 |

---

## 1. Use Case Overview

### 1.1 Actor Definitions

| Actor | Description | Access Level |
|-------|-------------|--------------|
| **Customer** | End user with WordPress account linked to FA customer | Tickets, Invoices, Orders, Profile |
| **Salesman** | FA sales representative handling approvals | Approval tasks |
| **System** | Automated processes | Sync, notifications |
| **Administrator** | WordPress admin | Full access |

### 1.2 Use Case Index

| ID | Use Case | Primary Actor | Priority |
|----|----------|---------------|----------|
| UC-AUTH-001 | User Login | Customer | Critical |
| UC-AUTH-002 | Link FA Account | System | High |
| UC-AUTH-003 | Password Reset | Customer | High |
| UC-TKT-001 | View Ticket List | Customer | High |
| UC-TKT-002 | View Ticket Details | Customer | High |
| UC-TKT-003 | Create Ticket | Customer | Critical |
| UC-TKT-004 | Add Note | Customer | Medium |
| UC-INV-001 | View Invoices | Customer | High |
| UC-ORD-001 | View Orders | Customer | Medium |
| UC-PROF-001 | View Profile | Customer | High |
| UC-PROF-002 | Update Profile | Customer | High |
| UC-PROF-003 | Change Password | Customer | High |
| UC-ANN-001 | View Announcements | Customer | Low |
| UC-DOC-001 | Download Documents | Customer | Medium |
| UC-WOO-001 | Sync to WooCommerce | System | Medium |
| UC-APR-001 | Create Approval Task | Customer | Medium |
| UC-APR-002 | Review Pending Tasks | Salesman | High |
| UC-APR-003 | Approve Task | Salesman | High |
| UC-APR-004 | Reject Task | Salesman | High |

---

## 2. Use Case Specifications

### 2.1 UC-AUTH-001: User Login

**Description:** Customer logs into WordPress and accesses the portal.

**Primary Actor:** Customer

**Pre-conditions:**
- WordPress user account exists
- Correct credentials provided

**Post-conditions:**
- User authenticated
- FA account linked (if not already)
- Session established with extended cookie

**Basic Flow:**
1. Customer navigates to WordPress login
2. Customer enters credentials
3. WordPress validates credentials
4. wp_login hook triggers on_user_login()
5. System links FA account if not already linked
6. Cookie expiration extended to 7 days
7. Customer redirected to portal dashboard

**Alternative Flows:**

**A1: Invalid Credentials**
1. System displays error message
2. Customer retries or resets password

**A2: No Matching FA Account**
1. Login succeeds to WordPress
2. FA linking skipped
3. Portal access limited until linked

---

### 2.2 UC-AUTH-002: Link FA Account

**Description:** System automatically links WordPress user to FA customer.

**Primary Actor:** System (triggered by login)

**Pre-conditions:**
- WordPress user logged in
- No existing FA link in user meta
- FA customer with matching email exists

**Post-conditions:**
- ksf_fa_debtor_no meta populated

**Basic Flow:**
1. User logs in (UC-AUTH-001)
2. System checks user meta for ksf_fa_debtor_no
3. If not found, system queries FA by email
4. If debtor found, system updates user meta
5. Future requests use cached debtor_no

---

### 2.3 UC-AUTH-003: Password Reset

**Description:** Customer requests password reset via email.

**Primary Actor:** Customer

**Pre-conditions:**
- Valid username or email entered

**Post-conditions:**
- Reset email sent (if account found)

**Basic Flow:**
1. Customer enters username/email in reset form
2. System looks up user by login or email
3. System generates reset key via get_password_reset_key()
4. System sends email with reset link
5. Customer clicks link and sets new password

**Alternative Flows:**

**A1: User Not Found**
1. System displays "Check email for instructions"
2. No indication if user exists (security)

---

### 2.4 UC-TKT-001: View Ticket List

**Description:** Customer views list of their support tickets.

**Primary Actor:** Customer

**Pre-conditions:**
- Customer logged in
- FA customer linked
- Tickets exist for customer

**Post-conditions:**
- Ticket list displayed

**Basic Flow:**
1. Customer clicks "Support Tickets" in portal
2. Shortcode renders ticket list
3. API fetches tickets via REST
4. Tickets displayed in table with:
   - Ticket number
   - Subject
   - Status
   - Priority
   - Created date
5. Customer can click ticket number for details

---

### 2.5 UC-TKT-002: View Ticket Details

**Description:** Customer views full details of a specific ticket.

**Primary Actor:** Customer

**Pre-conditions:**
- Customer logged in
- Ticket ID known (via URL parameter)

**Post-conditions:**
- Full ticket details displayed with notes

**Basic Flow:**
1. Customer clicks ticket link from list
2. URL includes ticket_id parameter
3. API fetches ticket details
4. Display includes:
   - Ticket metadata
   - Description
   - Notes with timestamps
5. Customer can add new note

---

### 2.6 UC-TKT-003: Create Ticket

**Description:** Customer submits a new support ticket.

**Primary Actor:** Customer

**Pre-conditions:**
- Customer logged in
- Required fields provided

**Post-conditions:**
- Ticket created in FA
- Ticket number returned

**Basic Flow:**
1. Customer clicks "New Ticket" in portal
2. Form displayed with fields:
   - Subject (required)
   - Type (dropdown)
   - Priority (dropdown)
   - Description (required)
3. Customer fills form and submits
4. API creates ticket:
   - Generates ticket number
   - Sets debtor_no from user meta
   - Sets created_by from WP user
   - Status defaults to 'New'
5. Success message with ticket number
6. Redirect to ticket detail page

**Alternative Flows:**

**A1: Missing Required Field**
1. Form validation fails
2. Error message displayed
3. Customer corrects and resubmits

---

### 2.7 UC-TKT-004: Add Note

**Description:** Customer adds a note to their ticket.

**Primary Actor:** Customer

**Pre-conditions:**
- Customer logged in
- Ticket exists and belongs to customer

**Post-conditions:**
- Note added to ticket

**Basic Flow:**
1. Customer views ticket detail
2. Customer enters note text
3. Customer submits note
4. API inserts note into fa_st_tickets_notes
5. Note appears in ticket timeline

---

### 2.8 UC-INV-001: View Invoices

**Description:** Customer views their invoice history.

**Primary Actor:** Customer

**Pre-conditions:**
- Customer logged in
- FA customer linked
- Invoices exist

**Post-conditions:**
- Invoice list displayed

**Basic Flow:**
1. Customer clicks "Invoices" in portal
2. API fetches invoices from FA debtor_trans
3. Display includes:
   - Invoice reference
   - Date
   - Due date
   - Total amount
   - Outstanding balance
4. Customer can view PDF (future)

---

### 2.9 UC-ORD-001: View Orders

**Description:** Customer views their order history.

**Primary Actor:** Customer

**Pre-conditions:**
- Customer logged in
- FA customer linked
- Orders exist

**Post-conditions:**
- Order list displayed

**Basic Flow:**
1. Customer clicks "Orders" in portal
2. API fetches orders from FA sales_orders
3. Display includes:
   - Order number
   - Date
   - Delivery date
   - Total
   - Status
4. Customer can track delivery (future)

---

### 2.10 UC-PROF-001: View Profile

**Description:** Customer views their profile information.

**Primary Actor:** Customer

**Pre-conditions:**
- Customer logged in

**Post-conditions:**
- Profile data displayed

**Basic Flow:**
1. Customer clicks "My Profile" in portal
2. API fetches combined data:
   - WordPress user data (name, email)
   - FA customer data (phone, address)
3. Form displayed with read-only email
4. Other fields editable

---

### 2.11 UC-PROF-002: Update Profile

**Description:** Customer updates their profile information.

**Primary Actor:** Customer

**Pre-conditions:**
- Customer logged in
- Valid data provided

**Post-conditions:**
- Profile updated in WP and FA

**Basic Flow:**
1. Customer views profile form (UC-PROF-001)
2. Customer modifies fields
3. Customer submits form
4. API updates:
   - WordPress display_name
   - FA debtors_master fields
5. Success message displayed

---

### 2.12 UC-ANN-001: View Announcements

**Description:** Customer views system announcements.

**Primary Actor:** Customer

**Pre-conditions:**
- Customer logged in

**Post-conditions:**
- Active announcements displayed

**Basic Flow:**
1. Customer visits portal
2. Shortcode renders announcements section
3. API fetches announcements:
   - active = 1
   - (expiry_date IS NULL OR expiry_date >= today)
   - target_type IN ('all', 'customer')
4. Announcements displayed with priority styling

---

### 2.13 UC-DOC-001: Download Documents

**Description:** Customer downloads their documents.

**Primary Actor:** Customer

**Pre-conditions:**
- Customer logged in
- Documents exist

**Post-conditions:**
- Download link provided

**Basic Flow:**
1. Customer clicks "Documents" in portal
2. API fetches customer documents
3. Table displayed with:
   - Document name
   - File type
   - Created date
   - Download button
4. Customer clicks download
5. File served with appropriate headers

---

### 2.14 UC-WOO-001: Sync to WooCommerce

**Description:** System synchronizes FA customer to WooCommerce.

**Primary Actor:** System

**Pre-conditions:**
- WooCommerce installed and active
- Customer has FA account

**Post-conditions:**
- WooCommerce customer created/updated
- Meta links established

**Basic Flow:**
1. Trigger sync via webhook or manual
2. Fetch FA debtor data
3. Find or create WP user
4. Create or update WooCommerce customer
5. Store woo_customer_id in WP meta
6. Return sync result

---

### 2.15 UC-APR-001: Create Approval Task

**Description:** System creates approval task when customer requests change requiring salesman approval.

**Primary Actor:** System

**Pre-conditions:**
- Customer initiates change
- Salesman assigned to customer

**Post-conditions:**
- Approval task created
- Salesman notified

**Basic Flow:**
1. Customer submits change request
2. System calls SalesmanApprovalService
3. Service gets assigned salesman from FA
4. Service creates task in fa_approval_tasks
5. Service notifies salesman via email
6. Task appears in salesman's queue

---

### 2.16 UC-APR-002: Review Pending Tasks

**Description:** Salesman reviews pending approval tasks.

**Primary Actor:** Salesman

**Pre-conditions:**
- User is assigned salesman
- Pending tasks exist

**Post-conditions:**
- Task details displayed

**Basic Flow:**
1. Salesman accesses approval page
2. System fetches tasks assigned to user
3. Tasks displayed with:
   - Task number
   - Type
   - Entity
   - Requested by
   - Created date
4. Salesman clicks task for details

---

### 2.17 UC-APR-003: Approve Task

**Description:** Salesman approves a task and applies changes.

**Primary Actor:** Salesman

**Pre-conditions:**
- Task is pending
- User is assigned salesman

**Post-conditions:**
- Task approved
- Changes applied to entity
- Customer notified (future)

**Basic Flow:**
1. Salesman reviews task details
2. Salesman clicks Approve
3. Salesman adds optional comments
4. System updates task status to 'Approved'
5. System calls apply_changes()
6. Changes committed to entity

---

### 2.18 UC-APR-004: Reject Task

**Description:** Salesman rejects a task.

**Primary Actor:** Salesman

**Pre-conditions:**
- Task is pending
- User is assigned salesman

**Post-conditions:**
- Task rejected
- Changes discarded

**Basic Flow:**
1. Salesman reviews task details
2. Salesman clicks Reject
3. Salesman enters rejection reason
4. System updates task status to 'Rejected'
5. Changes discarded

---

## 3. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | May 2026 | KSF Development Team | Initial specification |