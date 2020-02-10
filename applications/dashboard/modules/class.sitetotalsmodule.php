<?php
/**
 * Site totals module.
 *
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

/**
 * Site-wide stats.
 */
class SiteTotalsModule extends Gdn_Module {

    const CACHE_TTL = 3600;

    const CACHE_REFRESH_INTERVAL = 300;

    const CACHE_KEY = 'module.sitetotals';

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'dashboard';
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function getAllCounts() {
        $countsCacheKey = self::CACHE_KEY.'.counts';
        $counts = Gdn::cache()->get($countsCacheKey);

        if ($counts !== Gdn_Cache::CACHEOP_FAILURE) {
            return $counts;
        }

        $counts = ['User' => 0, 'Discussion' => 0, 'Comment' => 0];
        foreach ($counts as $name => $value) {
            $counts[$name] = $this->getCount($name);
        }

        // cache counts
        Gdn::cache()->store($countsCacheKey, $counts, [Gdn_Cache::FEATURE_EXPIRY => self::CACHE_TTL]);

        // set recalculate flag
        Gdn::cache()->store(self::CACHE_KEY.'.recalculate', true, [Gdn_Cache::FEATURE_EXPIRY => self::CACHE_REFRESH_INTERVAL]);

        return $counts;
    }

//    protected function _GetData() {
//        $px = Gdn::database()->DatabasePrefix;
//        $sql = "show table status where Name in ('{$px}User', '{$px}Discussion', '{$px}Comment')";
//
//        $result = ['User' => 0, 'Discussion' => 0, 'Comment' => 0];
//        foreach ($result as $name => $value) {
//            $result[$name] = $this->getCount($name);
//        }
//        $this->setData('Totals', $result);
//    }

    protected function getCount($table) {
        $count = Gdn::sql()
            ->select($table.'ID', 'count', 'CountRows')
            ->from($table)
            ->get()->value('CountRows');

        return $count;
    }

    public function toString() {
        $this->_GetData();
        return parent::toString();
    }
}
