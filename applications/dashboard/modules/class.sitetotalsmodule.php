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

    const RECALCULATE_INTERVAL = 300;

    const CACHE_KEY = 'module.sitetotals';

    const COUNTS_KEY = self::CACHE_KEY.'.counts';

    const RECALCULATE_KEY = self::CACHE_KEY.'.recalculate';

    /**
     * SiteTotalsModule constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'dashboard';
    }

    /**
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Get total counts
     *
     * @return mixed
     */
    public function getTotals() {
        // check recalculate flag
        $recalculateFlag = Gdn::cache()->get(self::RECALCULATE_KEY);

        if ($recalculateFlag === Gdn_Cache::CACHEOP_FAILURE) {  // expired
            $this->tryRegenerate();
        }

        // get totals from cache
        $totals = Gdn::cache()->get(self::COUNTS_KEY);

        return $totals;
    }

    /**
     * Attempt to implement lock system. A failure likely means the
     * cache key already exists, which would mean the lock is already in place.
     */
    private function tryRegenerate() {
        $lockKey = mt_rand(0.9999999);
        $added = Gdn::cache()->add(self::RECALCULATE_KEY, $lockKey, [Gdn_Cache::FEATURE_EXPIRY => self::RECALCULATE_INTERVAL]);
        if ($added) {
            /** @var Vanilla\Scheduler\SchedulerInterface $scheduler */
            $scheduler = Gdn::getContainer()->get(Vanilla\Scheduler\SchedulerInterface::class);
            $scheduler->addJob($this->getAllCounts());
        }
    }

    /**
     * Fire counts request to the DB
     */
    private function getAllCounts() {
        $counts = ['User' => 0, 'Discussion' => 0, 'Comment' => 0];
        foreach ($counts as $name => $value) {
            $counts[$name] = $this->getCount($name);
        }

        // cache counts
        Gdn::cache()->store(self::COUNTS_KEY, $counts, [Gdn_Cache::FEATURE_EXPIRY => self::CACHE_TTL]);
    }

    /**
     * Query the DB for count
     *
     * @param string $table
     * @return mixed
     */
    protected function getCount($table) {
        $count = Gdn::sql()
            ->select($table.'ID', 'count', 'CountRows')
            ->from($table)
            ->get()->value('CountRows');

        return $count;
    }

    /**
     * @return string
     */
    public function toString() {
        $totals = $this->getTotals();

        if (empty($totals)) {
            return '';   //don't render the module
        }

        $this->setData('Totals', $totals);
        return parent::toString();
    }
}
