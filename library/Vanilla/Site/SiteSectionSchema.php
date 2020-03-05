<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Utility\InstanceValidatorSchema;

/**
 * Schema for validating a site section.
 */
class SiteSectionSchema extends InstanceValidatorSchema {

    /**
     * Configure the class.
     */
    public function __construct() {
        parent::__construct(SiteSectionInterface::class);
    }

    /**
     * Convert a site section into an array. Useful for API/JSON output.
     *
     * @param SiteSectionInterface $section
     *
     * @return array
     */
    public static function toArray(SiteSectionInterface $section): array {
        return [
            'basePath' => $section->getBasePath(),
            'contentLocale' => $section->getContentLocale(),
            'sectionGroup' => $section->getSectionGroup(),
            'sectionID' => $section->getSectionID(),
            'name' => $section->getSectionName(),
            'apps' => $section->applications(),
        ];
    }
}
