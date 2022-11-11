<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Garden\Schema\Schema;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Forms\FieldMatchConditional;

/**
 * Class for dealing with root section of a site.
 *
 * @see SectionInterface
 */
class RootSiteSectionProvider implements SiteSectionProviderInterface
{
    /** @var RootSiteSection */
    private $rootSiteSection;

    /**
     * DI.
     *
     * @param RootSiteSection $rootSiteSection
     */
    public function __construct(RootSiteSection $rootSiteSection)
    {
        $this->rootSiteSection = $rootSiteSection;
    }

    /**
     * @inheritDoc
     */
    public function getAll(): array
    {
        return [$this->rootSiteSection];
    }

    /**
     * @inheritDoc
     */
    public function getCurrentSiteSection(): ?SiteSectionInterface
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getSiteSectionIDSchema(?FieldMatchConditional $conditional): ?Schema
    {
        return null;
    }
}
