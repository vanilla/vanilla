<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;

/**
 * Class for dealing with sections of a site.
 *
 * @see SectionInterface
 */
class SingleSiteSectionProvider implements SiteSectionProviderInterface {

    /** @var DefaultSiteSection */
    private $defaultSite;

    /**
     * DI.
     *
     * @param DefaultSiteSection $defaultSite
     */
    public function __construct(DefaultSiteSection $defaultSite) {
        $this->defaultSite = $defaultSite;
    }

    /**
     * @inheritdoc
     */
    public function getAll(): array {
        return [$this->defaultSite];
    }

    /**
     * @inheritdoc
     */
    public function getByID(int $id): ?SiteSectionInterface {
        if ($id === $this->defaultSite->getSectionID()) {
            return $this->defaultSite;
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getByBasePath(string $basePath): ?SiteSectionInterface {
        if ($basePath === $this->defaultSite->getBasePath()) {
            return $this->defaultSite;
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getForLocale(string $localeKey): array {
        if ($localeKey === $this->defaultSite->getContentLocale()) {
            return [$this->defaultSite];
        } else {
            return [];
        }
    }
}
