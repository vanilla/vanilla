<?php
/**
 * Gdn_FormatterChain.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Output formatter chain
 *
 * This object facilitates chaining custom formatters for use in the Gdn_FormatObject.
 * A custom formatter is an object with a format($String) method that formats a string in a particular way.
 * Certain calls to the various Gdn_Format methods (such as Html and To) will look for a custom formatter to use before formatting.
 *
 * If you want to create a custom formatter, but have it act in addition to an existing custom formatter than use a Gdn_FormatterChain with this process.
 *  - Create the object with a format($String) method.
 *  - Call the static method Gdn_FormatterChain::chain() to install it on top of the other formatter.
 *  - Depending on the priority you specified your formatter will be called before or after the existing formatter.
 */
class Gdn_FormatterChain {

    const PRIORITY_DEFAULT = 0;

    const PRIORITY_FIRST = 1000;

    const PRIORITY_LAST = -1000;

    /** @var array  */
    protected $_Formatters = [];

    /** Add a formatter to the chain. This method isn't usuall called directly. Use Gdn_FormatterChain::chain() instead.
     *
     * @param object $formatter The formatter to install.
     * @param int $priority The priority of the formatter in the chain. High priorities come first.
     */
    public function add($formatter, $priority = Gdn_FormatterChain::PRIORITY_DEFAULT) {
        // Make sure the priority isn't out of bounds.
        if ($priority < self::PRIORITY_LAST) {
            $priority = self::PRIORITY_LAST;
        } elseif ($priority > self::PRIORITY_FIRST)
            $priority = self::PRIORITY_FIRST;

        $fArray = [$formatter, $priority];
        $this->_Formatters[] = $fArray;

        // Resort the array so it's in priority order.
        usort($this->_Formatters, ['Gdn_FormatterChain', 'Compare']);
    }

    /**
     * Add a formatter and create a chain in the Gdn factory.
     *
     * This is a conveinience method for chaining formatters without having to deal with the object creation logic.
     *
     * @param string $type The type of formatter.
     * @param object $formatter The formatter to install.
     * @param int $priority The priority of the formatter in the chain. High priorities come first.
     * @return Gdn_FormatterChain The chain object that was created.
     */
    public static function chain($type, $formatter, $priority = Gdn_FormatterChain::PRIORITY_DEFAULT) {
        // Grab the existing formatter from the factory.
        $formatter = Gdn::factory($type.'Formatter');

        if ($formatter === null) {
            $chain = new Gdn_FormatterChain();
            Gdn::factoryInstall($type.'Formatter', 'Gdn_FormatterChain', __FILE__, Gdn::FactorySingleton, $chain);
        } elseif (is_a($formatter, 'Gdn_FormatterChain')) {
            $chain = $formatter;
        } else {
            Gdn::factoryUninstall($type.'Formatter');

            // Look for a priority on the existing object.
            if (property_exists($formatter, 'Priority')) {
                $priority = $formatter->Priority;
            } else {
                $priority = self::PRIORITY_DEFAULT;
            }

            $chain = new Gdn_FormatterChain();
            $chain->add($formatter, $priority);
            Gdn::factoryInstall($type.'Formatter', 'Gdn_FormatterChain', __FILE__, Gdn::FactorySingleton, $chain);
        }
        $chain->add($formatter, $priority);
        return $chain;
    }

    /** The function used to sort formatters in the chain.
     *
     * @param array $a The first formatter array to compare.
     * @param array $b The second formatter array to compare.
     * @return int
     */
    public static function compare($a, $b) {
        if ($a[1] < $b[1]) {
            return 1;
        } elseif ($a[1] > $b[1])
            return -1;
        else {
            return 0;
        }
    }

    /** Format a string with all of the formatters in turn.
     *
     * @param string $string The string to format.
     * @return string The formatted string.
     */
    public function format($string) {
        $result = $string;
        foreach ($this->_Formatters as $fArray) {
            $result = $fArray[0]->format($result);
        }
        return $result;
    }
}
