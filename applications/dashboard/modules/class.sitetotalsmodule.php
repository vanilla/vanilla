<?php
/**
 * Site totals module.
 *
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Vanilla\Scheduler\SchedulerInterface;

/**
 * Class SiteTotalsModule
 */
class SiteTotalsModule extends Gdn_Module {

    /** @var int */
    const CACHE_TTL = 43200;

    /** @var int */
    const RECALCULATE_INTERVAL = 900;

    /** @var int */
    const LOCK_INTERVAL = 60;

    /** @var string */
    const CACHE_KEY = 'module.sitetotals';

    /** @var string */
    const COUNTS_KEY = self::CACHE_KEY.'.counts';

    /** @var string */
    const LOCK_KEY = self::CACHE_KEY.'.lock';

    /** @var string */
    const RECALCULATE_KEY = self::CACHE_KEY.'.recalculate';

    /** @var bool Will add online users count to module data */
    public $onlineUsers = false;

    /** @var bool A combination of discussions and comments in data as posts*/
    public $totalPosts = false;

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
            $this->tryRecalculate();
        }

        // get totals from cache
        $totals = Gdn::cache()->get(self::COUNTS_KEY);

        return $totals;
    }

    /**
     * Attempt to implement lock system. A failure likely means the
     * cache key already exists, which would mean the lock is already in place.
     *
     * @return bool
     */
    private function tryRecalculate():bool {
        $lock = Gdn::cache()->get(self::LOCK_KEY);

        if ($lock === Gdn_Cache::CACHEOP_FAILURE) { //already locked
            $added = Gdn::cache()->add(self::LOCK_KEY, mt_rand(1, 999999), [Gdn_Cache::FEATURE_EXPIRY => self::LOCK_INTERVAL]);

            if ($added) {
                /** @var Vanilla\Scheduler\SchedulerInterface $scheduler */
                $scheduler = Gdn::getContainer()->get(Vanilla\Scheduler\SchedulerInterface::class);
                $scheduler->addJob(
                    Vanilla\Scheduler\Job\CallbackJob::class,
                    [
                        "callback" => function () {
                            $this->getAllCounts();
                        }
                    ]
                );

                return true;
            }
        }

        return false;
    }

    /**
     * Triggers getCount to request the DB
     */
    private function getAllCounts() {
        $counts = ['User' => 0, 'Discussion' => 0, 'Comment' => 0];

        foreach ($counts as $name => $value) {
            $count = $this->getCount($name);
            $counts[$name] = $count;
        }

        if (in_array(null, $counts)) {
            Logger::log('sitetotalsmodule_request_error', 'DB request returning unexpected values', $counts);
            return false;    //bail without overriding the cache
        }

        //total of discussions and comments, as posts
        if ($this->totalPosts) {
            $counts['Post'] = $counts['Discussion'] + $counts['Comment'];
        }

        // cache counts
        Gdn::cache()->store(self::COUNTS_KEY, $counts, [Gdn_Cache::FEATURE_EXPIRY => self::CACHE_TTL]);

        //cache recalculate key
        Gdn::cache()->store(self::RECALCULATE_KEY, time(), [Gdn_Cache::FEATURE_EXPIRY => self::RECALCULATE_INTERVAL]);
    }

    /**
     * Query the DB for count
     *
     * @param string $table
     * @return int
     */
    protected function getCount($table): ?int {
        $count = Gdn::sql()
            ->select($table.'ID', 'count', 'CountRows')
            ->from($table)
            ->get()->value('CountRows', null);

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
        $this->EventArguments['OnlineUsers'] = $this->onlineUsers;
        $this->FireEvent('BeforeRender');
        return parent::toString();
    }
}
