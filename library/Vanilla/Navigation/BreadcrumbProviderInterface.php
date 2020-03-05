<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Navigation;

use Vanilla\Contracts\RecordInterface;

/**
 * Some class that can map breadcrumbs to records.
 */
interface BreadcrumbProviderInterface {
    /**
     * Get a breadcrumb array for a particular record.
     *
     * @param RecordInterface $record
     * @param string $locale
     *
     * @return Breadcrumb[]
     */
    public function getForRecord(RecordInterface $record, string $locale = null): array;

    /**
     * Get the record type that the provider works for.
     *
     * @return string[]
     */
    public static function getValidRecordTypes(): array;
}
