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
     * @param array The configurations that failed the test.
     * @param array $context Additional information for the error.
     *   - You can set $context['configurationsRequiredValues'][{name}] = value|array
     */
    public function __construct($configurations, array $context = []) {
        if (!is_array($configurations)) {
            $configurations = [$configurations];
        }

        $context['configurations'] = $configurations;

        $messageParts = [];

        $or = t('or');
        foreach ($configurations as $configurationName) {
            if (isset($context['requiredValues']) && array_key_exists($configurationName, $context['requiredValues'])) {
                $requiredValues = $context['requiredValues'][$configurationName];
                if (!is_array($requiredValues)) {
                    $requiredValues = [$requiredValues];
                }
                array_walk($requiredValues, function(&$value) { $value = $this->translateValue($value); });

                $messageParts[] = sprintft("The $configurationName config must be set to %s to support the current action.", implode(" $or ", $requiredValues));
            } else {
                $messageParts[] = t("The $configurationName config is required to support the current action.");
            }
        }

        parent::__construct(implode(' ', $messageParts), $context);
    }

    /**
     * Translate values to human readable format.
     *
     * @param mixed $value
     * @return string
     */
    protected function translateValue($value) {
        if (is_string($value)) {
            $value = "\"$value\"";
        } elseif ($value === true) {
            $value = 'true';
        } elseif ($value === false) {
            $value = 'false';
        } elseif ($value === null) {
            $value = 'null';
        } elseif (is_array($value)) {
            foreach ($value as &$content) {
                $content = $this->translateValue($content);
            }
            $value = str_replace(["\n    ", "\n", 'Array(, '], [', ', '', 'Array('], print_r($value, true));
        }

        return (string)$value;
    }
}
