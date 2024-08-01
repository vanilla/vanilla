<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Models\SiteMetaExtra;

/**
 * Class for adding extra site meta related to category following, notifications and digest settings.
 */
class CategorySiteMetaExtra extends SiteMetaExtra
{
    /** @var ConfigurationInterface */
    protected ConfigurationInterface $config;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Get email and digest values to add to the site meta.
     * These are used to ensure the category following modals display relevant options.
     *
     * @return array
     */
    public function getValue(): array
    {
        $digestConfig = $this->config->get("Garden.Digest.Enabled", false);
        $emailDisabled = $this->config->get("Garden.Email.Disabled");
        return [
            "emails" => [
                "enabled" => !$emailDisabled,
                "digest" => !$emailDisabled && $digestConfig,
            ],
        ];
    }
}
