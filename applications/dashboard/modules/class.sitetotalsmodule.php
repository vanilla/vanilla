<?php
/**
 * Site totals module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Site-wide stats.
 */
class SiteTotalsModule extends Gdn_Module {

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'dashboard';
    }

    public function assetTarget() {
        return 'Panel';
    }

    protected function _GetData() {
        $Px = Gdn::database()->DatabasePrefix;
        $Sql = "show table status where Name in ('{$Px}User', '{$Px}Discussion', '{$Px}Comment')";

        $Result = array('User' => 0, 'Discussion' => 0, 'Comment' => 0);
        foreach ($Result as $Name => $Value) {
            $Result[$Name] = $this->getCount($Name);
        }
        $this->setData('Totals', $Result);
    }

    protected function getCount($Table) {
        // Try and get the count from the cache.
        $Key = "$Table.CountRows";
        $Count = Gdn::cache()->get($Key);
        if ($Count !== Gdn_Cache::CACHEOP_FAILURE) {
            return $Count;
        }

        // The count wasn't in the cache so grab it from the table.
        $Count = Gdn::sql()
            ->select($Table.'ID', 'count', 'CountRows')
            ->from($Table)
            ->get()->value('CountRows');

        // Save the value to the cache.
        Gdn::cache()->store($Key, $Count, array(Gdn_Cache::FEATURE_EXPIRY => 5 * 60 + mt_rand(0, 30)));
        return $Count;
    }

    public function toString() {
        $this->_GetData();
        return parent::ToString();
    }
}
