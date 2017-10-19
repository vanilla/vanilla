<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

namespace Vanilla\Exception;

use Garden\Web\Exception\ForbiddenException;

/**
 * An exception tha represents a configuration test failing.
 */
class ConfigurationException extends ForbiddenException {
    /**
     * Construct a {@link ConfigurationException} object.
     *
     * @param string The configuration that failed the test.
     * @param array $context Additional information for the error.
     */
    public function __construct($configuration, array $context = []) {
        $context['configuration'] = $configuration;

        $msg = sprintf('The %s configuration value disallow you to do that.', $configuration);

        parent::__construct($msg, $context);
    }
}
