<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Vanilla\Contracts\LocaleInterface;

/**
 * Get configuration related to Dates.
 */
class DateConfig {

    /** @var string */
    private $defaultTimeFormat;

    /** @var string */
    private $defaultDayFormat;

    /** @var string */
    private $defaultYearFormat;

    /** @var string */
    private $defaultFormat;

    /** @var string */
    private $defaultDateTimeFormat;

    /** @var int */
    private $hourOffset;

    /**
     * DI.
     *
     * @param LocaleInterface $locale
     * @param \Gdn_Session $session
     */
    public function __construct(LocaleInterface $locale, \Gdn_Session $session) {
        // Because these values are localizable, the are pulled from the locale instead of the config.
        $this->defaultTimeFormat = $locale->translate('Date.DefaultTimeFormat', '%l:%M%p');
        $this->defaultDayFormat = $locale->translate('Date.DefaultDayFormat', '%B %e');
        $this->defaultYearFormat = $locale->translate('Date.DefaultYearFormat', '%B %Y');
        $this->defaultFormat = $locale->translate('Date.DefaultFormat', '%B %e, %Y');
        $this->defaultDateTimeFormat = $locale->translate('Date.DefaultDateTimeFormat', '%c');
        $this->hourOffset = $session->hourOffset() ?? 0;
    }

    /**
     * @return int
     */
    public function getHourOffset(): int {
        return $this->hourOffset;
    }

    /**
     * @return string
     */
    public function getDefaultTimeFormat(): string {
        return $this->defaultTimeFormat;
    }

    /**
     * @return string
     */
    public function getDefaultDayFormat(): string {
        return $this->defaultDayFormat;
    }

    /**
     * @return string
     */
    public function getDefaultYearFormat(): string {
        return $this->defaultYearFormat;
    }

    /**
     * @return string
     */
    public function getDefaultFormat(): string {
        return $this->defaultFormat;
    }

    /**
     * @return string
     */
    public function getDefaultDateTimeFormat(): string {
        return $this->defaultDateTimeFormat;
    }
}
