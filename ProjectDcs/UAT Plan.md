# WordPress Customer Portal - UAT Plan

## Document Information

| Field | Value |
|-------|-------|
| Document Title | User Acceptance Testing Plan |
| Module | ksf_WP_CustomerPortal |
| Version | 1.0.0 |
| Author | KSF Development Team |
| Last Updated | May 2026 |

---

## 1. UAT Overview

### 1.1 Purpose

Validate the Customer Portal provides a seamless experience for customers to access their FrontAccounting data through WordPress.

### 1.2 Success Criteria

- All critical scenarios pass
- No security vulnerabilities
- Performance meets targets
- Business sign-off received

---

## 2. Test Scenarios

### 2.1 Scenario: AUTH-001 - User Login Flow

**Priority:** Critical

**Pre-conditions:**
- WordPress user exists
- FA customer with matching email exists

**Test Steps:**
1. Navigate to WordPress login
2. Enter credentials
3. Log in
4. Navigate to portal page
5. Verify FA data accessible

**Expected Results:**
- Login successful
- FA account auto-linked
- Portal data visible

**Pass Criteria:**
- [ ] Login completes without error
- [ ] debtor_no linked in user meta
- [ ] Customer data visible in portal

---

### 2.2 Scenario: TKT-001 - Create Support Ticket

**Priority:** Critical

**Pre-conditions:**
- Customer logged in
- FA account linked

**Test Steps:**
1. Navigate to support page
2. Click "New Ticket"
3. Fill form:
   - Subject: "Test Ticket"
   - Type: "Question"
   - Priority: "Medium"
   - Description: "This is a test ticket for UAT"
4. Submit form
5. Note ticket number

**Expected Results:**
- Ticket created successfully
- Ticket number displayed
- Ticket appears in list

**Pass Criteria:**
- [ ] Form submits
- [ ] Ticket number generated
- [ ] Ticket visible in list
- [ ] FA database updated

---

### 2.3 Scenario: TKT-002 - View Ticket Details

**Priority:** High

**Pre-conditions:**
- Ticket exists

**Test Steps:**
1. Click ticket from list
2. Verify all details displayed
3. Add a note
4. Verify note appears

**Expected Results:**
- Full ticket details shown
- Notes timeline displays correctly
- New note persisted

**Pass Criteria:**
- [ ] All ticket fields visible
- [ ] Notes display in order
- [ ] New note appears after submission

---

### 2.4 Scenario: INV-001 - View Invoices

**Priority:** High

**Pre-conditions:**
- Customer has invoices in FA

**Test Steps:**
1. Navigate to Invoices page
2. Verify invoice table displays
3. Check invoice details match FA

**Expected Results:**
- Invoice list displays
- All invoices for customer shown
- Dates and amounts correct

**Pass Criteria:**
- [ ] Invoices display in table
- [ ] Only customer's invoices shown
- [ ] Data matches FA records

---

### 2.5 Scenario: PROF-001 - Update Profile

**Priority:** High

**Pre-conditions:**
- Customer logged in

**Test Steps:**
1. Navigate to Profile page
2. Update phone number
3. Update address
4. Submit form
5. Verify changes in FA

**Expected Results:**
- Profile updated successfully
- Changes reflected in FA
- Success message displayed

**Pass Criteria:**
- [ ] Form submits without error
- [ ] FA debtors_master updated
- [ ] Changes visible on reload

---

### 2.6 Scenario: WOO-001 - WooCommerce Sync

**Priority:** Medium

**Pre-conditions:**
- WooCommerce installed
- Customer has FA account

**Test Steps:**
1. Trigger sync via API
2. Check WooCommerce customer created
3. Verify WP meta updated

**Expected Results:**
- Customer synced to WooCommerce
- WooCommerce customer ID stored
- Data matches FA

**Pass Criteria:**
- [ ] WooCommerce customer exists
- [ ] Meta properly linked
- [ ] Data consistent

---

### 2.7 Scenario: APR-001 - Approval Workflow

**Priority:** High

**Pre-conditions:**
- Salesman assigned to customer
- Approval task created

**Test Steps:**
1. Salesman logs in
2. Navigate to approvals page
3. View pending task
4. Review changes
5. Approve task
6. Verify changes applied to FA

**Expected Results:**
- Task visible in salesman's queue
- Changes previewed correctly
- Approval applies changes

**Pass Criteria:**
- [ ] Task appears for assigned salesman
- [ ] Changes previewed accurately
- [ ] Approved changes in FA
- [ ] Task status updated

---

### 2.8 Scenario: SEC-001 - Authorization Check

**Priority:** Critical

**Pre-conditions:**
- Customer A and Customer B exist

**Test Steps:**
1. Customer A logs in
2. Attempt to access Customer B's ticket directly via URL
3. Attempt to access Customer B's invoices directly via URL

**Expected Results:**
- Customer A cannot view Customer B's data
- 404 or error returned

**Pass Criteria:**
- [ ] Customer A blocked from Customer B's tickets
- [ ] Customer A blocked from Customer B's invoices
- [ ] No data leakage

---

### 2.9 Scenario: SEC-002 - XSS Prevention

**Priority:** Critical

**Pre-conditions:**
- None (test with malicious input)

**Test Steps:**
1. Create ticket with XSS payload in subject
2. View ticket list
3. View ticket details

**Expected Results:**
- XSS payload escaped in output
- No script execution

**Pass Criteria:**
- [ ] Script tags rendered as text
- [ ] No JavaScript execution

---

### 2.10 Scenario: PERF-001 - Page Load Time

**Priority:** Medium

**Pre-conditions:**
- 20+ tickets exist for customer

**Test Steps:**
1. Navigate to Tickets page
2. Measure page load time
3. Repeat 3 times

**Expected Results:**
- Page loads within 2 seconds

**Pass Criteria:**
- [ ] Load time < 2s consistently

---

## 3. Sign-off Checklist

### 3.1 Functional Checkpoints

- [ ] User login and FA linking works
- [ ] Tickets can be created and viewed
- [ ] Notes can be added to tickets
- [ ] Invoices display correctly
- [ ] Orders display correctly
- [ ] Profile updates save to FA
- [ ] Announcements display
- [ ] Documents downloadable
- [ ] WooCommerce sync functional
- [ ] Approval workflow completes

### 3.2 Security Checkpoints

- [ ] Authorization enforced per customer
- [ ] XSS prevention working
- [ ] SQL injection prevented
- [ ] Auth tokens secure
- [ ] No sensitive data in URLs

### 3.3 Performance Checkpoints

- [ ] Page loads < 2s
- [ ] API responses < 500ms
- [ ] Sync operations < 5s

### 3.4 Visual Checkpoints

- [ ] Shortcodes render correctly
- [ ] CSS applies properly
- [ ] Mobile responsive
- [ ] Error messages display appropriately

---

## 4. Sign-off Approval

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Business Owner | | | |
| Customer Service Manager | | | |
| IT Representative | | | |
| QA Lead | | | |

---

## 5. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | May 2026 | KSF Development Team | Initial UAT plan |