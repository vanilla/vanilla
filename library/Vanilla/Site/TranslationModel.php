<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\Site\TranslationProviderInterface;

/**
 * Class TranslationModel
 * @package Vanilla\Site
 */
class TranslationModel {
    /** @var TranslationProviderInterface[] $providers */
    private $providers;

    /**
     * Register translation provider
     *
     * @param TranslationProviderInterface $provider
     */
    public function addProvider(TranslationProviderInterface $provider) {
        $this->providers[] = $provider;
    }

    /**
     * Returns provider which supports content translation
     *
     * @return TranslationProviderInterface|null
     */
    public function getContentTranslationProvider(): ?TranslationProviderInterface {
        foreach ($this->providers as $provider) {
            if ($provider->supportsContentTranslation()) {
                return $provider;
            }
        }
        return null;
    }
}
