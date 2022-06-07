<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Garden\Schema\Schema;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\SchemaFactory;
use Vanilla\Utility\InstanceValidatorSchema;

/**
 * Schema for validating a site section.
 */
class SiteSectionSchema extends InstanceValidatorSchema
{
    /**
     * Configure the class.
     */
    public function __construct()
    {
        parent::__construct(SiteSectionInterface::class);
    }

    /**
     * Convert a site section into an array. Useful for API/JSON output.
     *
     * @param SiteSectionInterface $section
     *
     * @return array
     */
    public static function toArray(SiteSectionInterface $section): array
    {
        return [
            "basePath" => $section->getBasePath(),
            "contentLocale" => $section->getContentLocale(),
            "sectionGroup" => $section->getSectionGroup(),
            "sectionID" => $section->getSectionID(),
            "name" => $section->getSectionName(),
            "apps" => $section->applications(),
            "attributes" => $section->getAttributes(),
        ];
    }

    /**
     * Since this is an `InstanceValidatorSchema` normally, get an actual definition of what will be outputted
     * for use in things like forms.
     *
     * @return Schema
     */
    public static function getSchema(): Schema
    {
        return SchemaFactory::parse([
            "basePath:s" => [
                "minLength" => 0, // Could be '' for the default section.
                "description" => "The base url of the site section.",
            ],
            "contentLocale:s" => "The locale key for content in this site section.",
            "sectionGroup:s" => "The group holding this site section (and potentially others).",
            "sectionID:s" => "The id of the site section.",
            "name:s" => "The name of the site section.",
            "apps:o",
            "attributes:o",
        ]);
    }
}
