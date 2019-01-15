<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Garden\Container\Container;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Exception\FormatterNotFoundException;

class FormatFactory {
    /** @var [] */
    private $formats = [];

    /** @var Container */
    private $container;

    /**
     * Format Factory constructor
     *
     * @param Container $container The container instance so we can create Format instances.
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * Register a format type and the class name handles it.
     *
     * @param string $formatKey
     * @param string $formatClass
     */
    public function registerFormat(string $formatKey, string $formatClass) {
        $formatKey = strtolower($formatKey);
        $this->formats[$formatKey] = $formatClass;
    }

    /**
     * Get an instance of a formatter.
     *
     * @param string $formatKey
     * @return FormatInterface
     * @throws FormatterNotFoundException
     */
    public function getFormatter(string $formatKey): FormatInterface {
        $formatKey = strtolower($formatKey);
        $formatClass = $this->formats[$formatKey];
        $errorMessage = "Unable to find a formatter for the formatKey $formatKey.";
        if (!$formatClass) {
            throw new FormatterNotFoundException($errorMessage);
        }

        try {
            $formatter = $this->container->get($formatClass);
        } catch (ContainerException $e) {
            throw new FormatterNotFoundException($errorMessage);
        }

        return $formatter;
    }
}
