<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\CustomerPortal;

use PHPUnit\Framework\TestCase;

/**
 * The portal's document list read `TB_PREF . "fa_customer_documents"`, i.e.
 * `0_fa_customer_documents`. That table is created by no schema in the
 * estate, so the query failed with MySQL error 1146 and the portal's
 * Documents tab was permanently empty.
 *
 * FA core has no document entity, so the list stays with the portal, in the
 * 0_ksf_customerportal_* family.
 */
class CustomerDocumentsTableTest extends TestCase
{
    private const DOCUMENTS_TABLE = 'ksf_customerportal_customer_documents';

    private string $moduleDir;

    protected function setUp(): void
    {
        $this->moduleDir = dirname(__DIR__, 2);
    }

    public function testPortalApiDoesNotAddressTheNonExistentFaCustomerDocumentsTable(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/includes/class-ksf-portal-api.php');

        $this->assertStringNotContainsString(
            'fa_customer_documents',
            $contents,
            'the portal must not address 0_fa_customer_documents: no schema creates it'
        );
    }

    public function testPortalApiUsesThePortalOwnedDocumentsTable(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/includes/class-ksf-portal-api.php');

        $this->assertStringContainsString(
            'TB_PREF . "' . self::DOCUMENTS_TABLE,
            $contents,
            'the portal must read the portal-owned customer documents table'
        );
    }

    public function testSchemaCreatesTheCustomerDocumentsTable(): void
    {
        $contents = (string) file_get_contents(
            $this->moduleDir . '/sql/ksf_customerportal_customer_documents.sql'
        );

        $this->assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `0_' . self::DOCUMENTS_TABLE . '`',
            $contents,
            'the DDL for the portal-owned customer documents table must ship with the portal'
        );
    }

    public function testCustomerDocumentsTableCarriesTheColumnsTheQuerySelects(): void
    {
        $contents = (string) file_get_contents(
            $this->moduleDir . '/sql/ksf_customerportal_customer_documents.sql'
        );

        foreach (['debtor_no', 'name', 'filename', 'file_type', 'created_at'] as $column) {
            $this->assertStringContainsString('`' . $column . '`', $contents);
        }
    }
}
