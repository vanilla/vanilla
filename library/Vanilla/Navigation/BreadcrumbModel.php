<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Navigation;

use Vanilla\Contracts\RecordInterface;

/**
 * Provider of breadcrumb data.
 */
class BreadcrumbModel {

    /** @var array A mapping of recordType to breadcrumb provider. */
    private $providers = [];

    /**
     * @param BreadcrumbProviderInterface $provider
     */
    public function addProvider(BreadcrumbProviderInterface $provider) {
        $types = $provider::getValidRecordTypes();
        foreach ($types as $type) {
            $this->providers[$type] = $provider;
        }
    }

    /**
     * Get a breadcrumb array for a particular record.
     *
     * @param RecordInterface $record
     * @param string $locale
     *
     * @return Breadcrumb[]
     */
    public function getForRecord(RecordInterface $record, string $locale = null): array {
        /** @var BreadcrumbProviderInterface|null $provider */
        $provider = $this->providers[$record->getRecordType()] ?? null;
        if (!$provider) {
            throw new BreadcrumbProviderNotFoundException($record->getRecordType() . " could not be found");
        }

        return $provider->getForRecord($record, $locale);
    }

    /**
     * Convert an array of breadcrumbs into
     *
     * @param Breadcrumb[] $crumbs The array of breadcrumbs to convert to JSON-LD.
     *
     * @return array Breadcrumb data as array of structure ['Name'=>'', 'Url'=>'//'].
     */
    public static function crumbsAsArray(array $crumbs): array {
        $crumbList = [];
        foreach ($crumbs as $index => $crumb) {
            $crumbList[] = [
                'Name' => $crumb->getName(),
                'Url' => $crumb->getUrl(),
            ];
        }
        return $crumbList;
    }
}
