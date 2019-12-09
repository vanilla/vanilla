<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
namespace Vanilla\Contracts\Site;

/**
 * Interface TranslationResourceInterface
 */
interface TranslationResourceInterface {
    /**
     * Returns translation resource key (url-code)
     *
     * @return string
     */
    public function resourceKey(): string;

    /**
     * Returns translation resource model record (to initialize)
     *
     * @return array
     */
    public function resourceRecord(): array;
}
