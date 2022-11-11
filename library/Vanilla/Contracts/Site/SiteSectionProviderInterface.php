<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;
use Garden\Schema\Schema;
use Vanilla\Forms\FieldMatchConditional;

/**
 * Provider for site sections.
 *
 * This is called a "provider" because it does not contain any methods for creating/modifying sections.
 * Some implementations may contain this behaviour but it is not strictly defined for this interface.
 */
interface SiteSectionProviderInterface
{
    /**
     * Returns all sections of the site.
     *
     * @return SiteSectionInterface[]
     */
    public function getAll(): array;

    /**
     * Get the current site section for the request automatically if possible.
     *
     * @return SiteSectionInterface
     */
    public function getCurrentSiteSection(): ?SiteSectionInterface;

    /**
     * Get a schema for a site section picker.
     *
     * @param FieldMatchConditional|null $conditional
     *
     * @return Schema|null
     */
    public function getSiteSectionIDSchema(?FieldMatchConditional $conditional): ?Schema;
}
