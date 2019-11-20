<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Web\Exception\NotFoundException;
use Garden\Web\RequestInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\Asset\SiteAsset;

/**
 * Embed factory with utilities for matching our own site.
 */
abstract class AbstractOwnSiteEmbedFactory extends AbstractEmbedFactory {

    /** @var RequestInterface */
    private $request;

    /** @var SiteSectionModel */
    private $sectionModel;

    /**
     * DI
     *
     * @param RequestInterface $request
     * @param SiteSectionModel $sectionModel
     */
    public function __construct(
        RequestInterface $request,
        SiteSectionModel $sectionModel
    ) {
        $this->request = $request;
        $this->sectionModel = $sectionModel;
    }

    /**
     * @return array
     */
    protected function getSupportedDomains(): array {
        return [
            $this->request->getHost(),
        ];
    }

    /**
     * Get a regex representing the root of the site w/ all allowable site sections and siteRoot.
     */
    protected function getRegexRoot(): string {
        $allowedSlugs = [];
        foreach ($this->sectionModel->getAll() as $siteSection) {
            if ($siteSection->getBasePath() !== '') {
                $allowedSlugs[] = $siteSection->getBasePath();
            }
        }
        $siteSectionRegex = count($allowedSlugs) > 0 ?
            '(' . implode('|', $allowedSlugs) . ')?'
            : '';
        $root = $this->request->getAssetRoot() . $siteSectionRegex;

        // Escape slashes.
        $root = str_replace('/', '\/', $root);
        return $root;
    }
}
