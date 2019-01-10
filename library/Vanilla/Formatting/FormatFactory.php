<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Psr\Container\ContainerInterface;
use Vanilla\Formatting\Exception\FormattingException;

class FormatFactory {
    /** @var [] */
    private $formats = [];

    /** @var ContainerInterface */
    private $container;

    /**
     * Format Factory constructor
     *
     * @param ContainerInterface $container The container instance so we can create Format instances.
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    /**
     * Register a format type and the class name handles it.
     *
     * @param string $formatKey
     * @param string $formatClass
     */
    public function registerFormat(string $formatKey, string $formatClass) {}

    /**
     * Get an instance of a formatter.
     *
     * @param string $formatKey
     * @return AbstractFormat
     * @throws FormattingException()
     */
    public function getFormatter(string $formatKey): AbstractFormat {
        $formatClass = $this->formats[$formatKey];
        $formatter = $this->container->get($formatClass);

        if (!($formatter instanceof AbstractFormat)) {
            throw new FormattingException("Unable to find a formatter for the formatKey $formatKey.");
        }

        return $formatter;
    }
}
