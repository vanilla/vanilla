<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;

/**
 * Site section definition for a site with only a single section.
 * This is a baseline for when no multisite provider is configured.
 */
class DefaultSiteSection implements SiteSectionInterface {

    const DEFAULT_ID = 0;

    const EMPTY_BASE_PATH = "/";

    const DEFAULT_SECTION_GROUP = "vanilla";

    /** @var string */
    private $configSiteName;

    /** @var string */
    private $configLocaleKey;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config) {
        $this->configSiteName = $config->get('Garden.Title', 'Vanilla');
        $this->configLocaleKey = $config->get('Garden.Locale', 'en');
    }

    /**
     * @inheritdoc
     */
    public function getBasePath(): string {
        return self::EMPTY_BASE_PATH;
    }

    /**
     * @inheritdoc
     */
    public function getContentLocale(): string {
        return $this->configLocaleKey;
    }

    /**
     * @inheritdoc
     */
    public function getSectionName(): string {
        return $this->configSiteName;
    }

    /**
     * @inheritdoc
     */
    public function getSectionID(): int {
        return self::DEFAULT_ID;
    }

    /**
     * @inheritdoc
     */
    public function getSectionGroup(): string {
        return self::DEFAULT_SECTION_GROUP;
    }
}
