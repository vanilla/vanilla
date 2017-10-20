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
     * @param string The configurations that failed the test.
     * @param array $context Additional information for the error.
     *   - You can set $context['configurationValue'] = value; to specify that the configuration requires a specific value to be set.
     */
    public function __construct($configuration, array $context = []) {
        $context['configuration'] = $configuration;

        if (array_key_exists('configurationValue', $context)) {
            $message = sprintft(
                "The %s config must be set to %s to support the current action.",
                $configuration,
                json_encode($context['configurationValue'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        } else {
            $message = sprintft("The %s config is required to support the current action.", $configurationName);
        }

        parent::__construct($message, $context);
    }
}
