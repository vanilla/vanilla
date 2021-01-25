<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme\VariableProviders;

use Vanilla\Theme\VariableDefaultsProviderInterface;
use Vanilla\Theme\VariablesProviderInterface;

/**
 * Variable provider to apply quick links variables to the current theme.
 */
class QuickLinksVariableProvider implements VariablesProviderInterface, VariableDefaultsProviderInterface {

    /** @var QuickLinkProviderInterface[] */
    protected $providers = [];

    /** @var array|null */
    private $allLinks = null;

    /**
     * @param QuickLinkProviderInterface $linkProvider
     */
    public function addQuickLinkProvider(QuickLinkProviderInterface $linkProvider) {
        $this->providers[] = $linkProvider;
    }

    /**
     * @return QuickLink[]
     */
    public function getAllLinks(): array {
        if ($this->allLinks === null) {
            $this->allLinks = [];
            foreach ($this->providers as $provider) {
                foreach ($provider->provideQuickLinks() as $link) {
                    $this->allLinks[] = $link;
                }
            }
            usort($this->allLinks, function (QuickLink $a, QuickLink $b) {
                return ($a->getSort() <=> $b->getSort());
            });
        }

        return $this->allLinks;
    }

    /**
     * @return array
     */
    public function getVariables(): array {
        $allLinks = $this->getAllLinks();
        $counts = [];
        foreach ($allLinks as $link) {
            $counts[$link->getID()] = $link->getCount();
        }

        return [
            'quickLinks' => [
                'counts' => $counts,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getVariableDefaults(): array {
        return [
            'quickLinks' => [
                'links' => $this->getAllLinks(),
            ],
        ];
    }

    /**
     * Reset all providers
     */
    public function resetProviders() {
        $this->providers = [];
    }
    /**
     * Return all providers.
     *
     * @return QuickLinkProviderInterface[]
     */
    public function getAllProviders(): array {
        return $this->providers;
    }
}
