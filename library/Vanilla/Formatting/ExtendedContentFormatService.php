<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting;

use Vanilla\Contracts\Formatting\FormatInterface;

/**
 * Extension of Format Service to all for more configurability.
 */
class ExtendedContentFormatService extends FormatService {
    /**
     * Overrides parent::registerFormat to allow for extended content.
     *
     * @param string $formatKey
     * @param FormatInterface|string $format
     *
     * @return $this For method chaining.
     */
    public function registerFormat(string $formatKey, $format): FormatService {
        if ($format instanceof FormatInterface) {
            $format->setAllowExtendedContent(true);
        }
        parent::registerFormat($formatKey, $format);
        return $this;
    }

    /**
     * Overridden to apply extended content configuration.
     *
     * @param string $formatClass
     *
     * @return FormatInterface
     */
    protected function constructFormat(string $formatClass): FormatInterface {
        $format = parent::constructFormat($formatClass);
        $format->setAllowExtendedContent(true);
        return $format;
    }
}
