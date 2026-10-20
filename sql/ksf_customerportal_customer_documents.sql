-- 0_ksf_customerportal_customer_documents
-- Customer documents shown in the WordPress customer portal.
--
-- WHY THIS TABLE EXISTS (and why it is not FA core):
--   FA core has no document entity and no customer-document store, and the
--   portal's document list is a portal-domain record (a file the customer is
--   allowed to see, attached to a debtor). The table therefore stays with the
--   portal, in the 0_ksf_customerportal_* family.
--
-- The FA install engine replaces the literal 0_ with TB_PREF. This DDL has to
-- be applied to the FrontAccounting database by the portal's deployment step -
-- the plugin itself runs inside WordPress and has no FA-side installer.

CREATE TABLE IF NOT EXISTS `0_ksf_customerportal_customer_documents` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `debtor_no` INT(11) NOT NULL COMMENT 'FA debtor number (0_debtors_master.debtor_no)',
    `name` VARCHAR(255) NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_debtor` (`debtor_no`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
