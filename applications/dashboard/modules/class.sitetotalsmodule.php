<?php
/**
 * Site totals module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
        $px = Gdn::database()->DatabasePrefix;
        $sql = "show table status where Name in ('{$px}User', '{$px}Discussion', '{$px}Comment')";

        $result = ['User' => 0, 'Discussion' => 0, 'Comment' => 0];
        foreach ($result as $name => $value) {
            $result[$name] = $this->getCount($name);
        }
        $this->setData('Totals', $result);
    }

    protected function getCount($table) {
        // Try and get the count from the cache.
        $key = "$table.CountRows";
        $count = Gdn::cache()->get($key);
        if ($count !== Gdn_Cache::CACHEOP_FAILURE) {
            return $count;
        }

        // The count wasn't in the cache so grab it from the table.
        $count = Gdn::sql()
            ->select($table.'ID', 'count', 'CountRows')
            ->from($table)
            ->get()->value('CountRows');

        // Save the value to the cache.
        Gdn::cache()->store($key, $count, [Gdn_Cache::FEATURE_EXPIRY => 5 * 60 + mt_rand(0, 30)]);
        return $count;
    }

    public function toString() {
        $this->_GetData();
        return parent::toString();
    }
}
