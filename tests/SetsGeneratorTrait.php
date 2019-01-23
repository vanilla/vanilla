<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;


/**
 * Trait SetsGeneratorTrait.
 */
trait SetsGeneratorTrait {

    /**
     * Generate all possible set combinations from a list of properties and their value(s).
     *
     * Example: ['a' => [0, 1], 'b' => [2, 3]] would yield:
     * [
     *   ['a' => 0, 'b' => 2],
     *   ['a' => 1, 'b' => 2],
     *   ['a' => 0, 'b' => 3],
     *   ['a' => 1, 'b' => 3],
     * ]
     *
     * @param array $properties List of properties and their possible values.
     * @return array Sets of all the possible combination or property/value
     */
    protected function combinatorialSetsGenerator($properties) {
        $propertiesCurrentIndex = [];
        $propertiesMaxIndex = [];
        $setsCount = 1;
        foreach ($properties as $propertyName => $propertyPossibleValues) {
            $propertiesCurrentIndex[$propertyName] = 0;
            $propertiesMaxIndex[$propertyName] = count($propertyPossibleValues) - 1;
            $setsCount *= $propertiesMaxIndex[$propertyName] + 1;
        }
        $propertiesSets = array_fill(0, $setsCount, array_fill_keys(array_keys($properties), null));

        foreach ($propertiesSets as &$set) {
            foreach ($set as $propertyName => &$value) {
                if (is_callable($properties[$propertyName][$propertiesCurrentIndex[$propertyName]])) {
                    $value = $properties[$propertyName][$propertiesCurrentIndex[$propertyName]](...array_values($set));
                } else {
                    $value = $properties[$propertyName][$propertiesCurrentIndex[$propertyName]];
                }
            }

            foreach ($propertiesCurrentIndex as $propertyName => &$index) {
                if ($index === 0) {
                    if ($propertiesMaxIndex[$propertyName] > 0) {
                        $index += 1;
                        break;
                    }
                } else {
                    if ($index + 1 <= $propertiesMaxIndex[$propertyName]) {
                        $index += 1;
                        break;
                    } else {
                        $index = 0;
                    }
                }
            }
        }

        return $propertiesSets;
    }
}
