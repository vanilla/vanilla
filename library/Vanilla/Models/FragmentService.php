<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use InvalidArgumentException;
use Vanilla\Contracts\Models\FragmentFetcherInterface;

/**
 * Service for fetching resource row fragments. Supported resources are based on those registered with ModelFactory.
 */
class FragmentService {

    /** @var ModelFactory */
    private $factory;

    /**
     * Setup the service.
     *
     * @param ModelFactory $factory
     */
    public function __construct(ModelFactory $factory) {
        $this->factory = $factory;
    }

    /**
     * Retrieve one or more fragments for a registered record type.
     *
     * @param string $recordType
     * @param array $recordIDs
     * @param array $options
     * @return array
     */
    public function get(string $recordType, array $recordIDs, array $options = []): array {
        $model = $this->factory->get($recordType);

        if (!($model instanceof FragmentFetcherInterface)) {
            $class = get_class($model);
            throw new InvalidArgumentException("Record model does not implement " . FragmentFetcherInterface::class . ": {$class}");
        }

        return $model->fetchFragments($recordIDs, $options);
    }
}
