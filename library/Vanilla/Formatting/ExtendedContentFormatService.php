<?php


namespace Vanilla\Formatting;


use Vanilla\Contracts\Formatting\FormatInterface;

class ExtendedContentFormatService extends FormatService {
    /**
     * Overrides parent::registerFormat to allow for extended content.
     *
     * @param string $formatKey
     * @param FormatInterface $format
     *
     * @return $this For method chaining.
     */
    public function registerFormat(string $formatKey, FormatInterface $format): FormatService {
        $format->setAllowExtendedContent(true);
        parent::registerFormat($formatKey, $format);
        return $this;
    }

}
