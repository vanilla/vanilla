<?php
/**
 * @author Dani Stark <dstark@higherlogic.com>
 * @copyright 2009-2021 Higher-Logic Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Web\Exception;

/**
 * PartialCompletionException class.
 */
class PartialCompletionException extends HttpException {

    /**
     * @var array
     */
    private $failedIDs = [];

    /**
     * @var array
     */
    private $successIDs = [];

    /**
     * @var array
     */
    private $notTriedIDs = [];

    /**
     * Initialize a {@link PartialCompletionException}.
     *
     * @param array $exceptions Exceptions array.
     * @param array $failedIDs Failed resource IDs.
     * @param array $successIDs Successful resource IDs.
     * @param array $notTriedIDs Timeout resource IDs.
     */
    public function __construct(array $exceptions = [], array $failedIDs = [], array $successIDs = [], array $notTriedIDs = []) {
        $code = [];
        $this->notTriedIDs = $notTriedIDs;
        $this->failedIDs = $failedIDs;
        $this->successIDs = $successIDs;

        $status = 408;
        if (!empty($exceptions)) {
            foreach ($exceptions as $key => $value) {
                $code[$key] = $value->getCode();
            }
            $status = max($code);
        }
        parent::__construct('Failed processing some resources.', $status, ['failedIDs' => $failedIDs, 'successIDs' => $successIDs, 'notTriedIDs' => $notTriedIDs]);
    }

    /**
     * Get notTriedIDs.
     *
     * @return array
     */
    public function getNotTried(): array {
        return $this->notTriedIDs;
    }

    /**
     * Get successIDs.
     *
     * @return array
     */
    public function getSuccessIDs(): array {
        return $this->successIDs;
    }

    /**
     * Get failedIDs.
     *
     * @return array
     */
    public function getFailedIDs(): array {
        return $this->failedIDs;
    }
}
