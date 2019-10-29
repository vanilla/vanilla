<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\EmbeddedContent;

use Vanilla\EmbeddedContent\EmbedFilterInterface;

/**
 * A configurable stub embed filter for usage in tests.
 */
class MockEmbedFilter implements EmbedFilterInterface {

    /**
     * @var string[]
     */
    private $supportedTypes;

    /** @var bool */
    private $handleAllTypes;

    /** @var array */
    private $filterResult;

    /**
     * Constuctor.
     *
     * @param bool $handleAllTypes
     * @param array $filterResult
     * @param string[] $supportedTypes
     */
    public function __construct(bool $handleAllTypes, array $filterResult, array $supportedTypes = []) {
        $this->supportedTypes = $supportedTypes;
        $this->handleAllTypes = $handleAllTypes;
        $this->filterResult = $filterResult;
    }

    /**
     * @inheritdoc
     */
    public function canHandleEmbedType(string $embedType): bool {
        return $this->handleAllTypes || in_array($embedType, $this->supportedTypes);
    }

    /**
     * @inheritdoc
     */
    public function filterData(array $data): array {
        return $this->filterResult;
    }
}
