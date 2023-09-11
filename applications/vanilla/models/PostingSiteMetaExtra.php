<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

/**
 * Class for adding extra site meta related to posting settings.
 */
class PostingSiteMetaExtra extends \Vanilla\Models\SiteMetaExtra
{
    /** @var \Vanilla\Contracts\ConfigurationInterface */
    protected $config;

    /**
     * DI.
     *
     * @param \Vanilla\Contracts\ConfigurationInterface $config
     */
    public function __construct(\Vanilla\Contracts\ConfigurationInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): array
    {
        $meta = $this->getPostingSiteMetaExtra();
        return $meta;
    }

    /**
     * Get the posting settings values to add to the site meta.
     *
     * @return array
     */
    public function getPostingSiteMetaExtra(): array
    {
        $autosaveEnabled = $this->config->get("Vanilla.Drafts.Autosave", true);
        $trustedDomains = $this->config->get("Garden.TrustedDomains");
        $disableUrlEmbeds = $this->config->get("Garden.Format.DisableUrlEmbeds");
        return [
            "community" => [
                "drafts" => [
                    "autosave" => $autosaveEnabled,
                ],
            ],
            "trustedDomains" => $trustedDomains,
            "disableUrlEmbeds" => $disableUrlEmbeds,
        ];
    }
}
