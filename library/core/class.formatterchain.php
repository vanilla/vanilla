<?php
/**
 * Gdn_FormatterChain.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Output formatter chain
 *
 * This object facilitates chaining custom formatters for use in the Gdn_FormatObject.
 * A custom formatter is an object with a Format($String) method that formats a string in a particular way.
 * Certain calls to the various Gdn_Format methods (such as Html and To) will look for a custom formatter to use before formatting.
 *
 * If you want to create a custom formatter, but have it act in addition to an existing custom formatter than use a Gdn_FormatterChain with this process.
 *  - Create the object with a Format($String) method.
 *  - Call the static method Gdn_FormatterChain::Chain() to install it on top of the other formatter.
 *  - Depending on the priority you specified your formatter will be called before or after the existing formatter.
 */
class Gdn_FormatterChain {

    const PRIORITY_DEFAULT = 0;

    const PRIORITY_FIRST = 1000;

    const PRIORITY_LAST = -1000;

    /** @var array  */
    protected $_Formatters = array();

    /** Add a formatter to the chain. This method isn't usuall called directly. Use Gdn_FormatterChain::Chain() instead.
     *
     * @param object $Formatter The formatter to install.
     * @param int $Priority The priority of the formatter in the chain. High priorities come first.
     */
    public function add($Formatter, $Priority = Gdn_FormatterChain::PRIORITY_DEFAULT) {
        // Make sure the priority isn't out of bounds.
        if ($Priority < self::PRIORITY_LAST) {
            $Priority = self::PRIORITY_LAST;
        } elseif ($Priority > self::PRIORITY_FIRST)
            $Priority = self::PRIORITY_FIRST;

        $FArray = array($Formatter, $Priority);
        $this->_Formatters[] = $FArray;

        // Resort the array so it's in priority order.
        usort($this->_Formatters, array('Gdn_FormatterChain', 'Compare'));
    }

    /**
     * Add a formatter and create a chain in the Gdn factory.
     *
     * This is a conveinience method for chaining formatters without having to deal with the object creation logic.
     *
     * @param string $Type The type of formatter.
     * @param object $Formatter The formatter to install.
     * @param int $Priority The priority of the formatter in the chain. High priorities come first.
     * @return Gdn_FormatterChain The chain object that was created.
     */
    public static function chain($Type, $Formatter, $Priority = Gdn_FormatterChain::PRIORITY_DEFAULT) {
        // Grab the existing formatter from the factory.
        $Formatter = Gdn::factory($Type.'Formatter');

        if ($Formatter === null) {
            $Chain = new Gdn_FormatterChain();
            Gdn::factoryInstall($Type.'Formatter', 'Gdn_FormatterChain', __FILE__, Gdn::FactorySingleton, $Chain);
        } elseif (is_a($Formatter, 'Gdn_FormatterChain')) {
            $Chain = $Formatter;
        } else {
            Gdn::factoryUninstall($Type.'Formatter');

            // Look for a priority on the existing object.
            if (property_exists($Formatter, 'Priority')) {
                $Priority = $Formatter->Priority;
            } else {
                $Priority = self::PRIORITY_DEFAULT;
            }

            $Chain = new Gdn_FormatterChain();
            $Chain->add($Formatter, $Priority);
            Gdn::factoryInstall($Type.'Formatter', 'Gdn_FormatterChain', __FILE__, Gdn::FactorySingleton, $Chain);
        }
        $Chain->add($Formatter, $Priority);
        return $Chain;
    }

    /** The function used to sort formatters in the chain.
     *
     * @param array $A The first formatter array to compare.
     * @param array $B The second formatter array to compare.
     * @return int
     */
    public static function compare($A, $B) {
        if ($A[1] < $B[1]) {
            return 1;
        } elseif ($A[1] > $B[1])
            return -1;
        else {
            return 0;
        }
    }

    /** Format a string with all of the formatters in turn.
     *
     * @param string $String The string to format.
     * @return string The formatted string.
     */
    public function format($String) {
        $Result = $String;
        foreach ($this->_Formatters as $FArray) {
            $Result = $FArray[0]->format($Result);
        }
        return $Result;
    }
}
