<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

/**
 * Class ApiFormChoices
 *
 * @package Vanilla\Forms
 */
class ApiFormChoices implements FormChoicesInterface {

    /**
     * @var string $indexUrl
     */
    private $indexUrl;

    /**
     * @var string $singleUrl
     */
    private $singleUrl;

    /**
     * @var string $valueKey
     */
    private $valueKey;

    /**
     * @var string $labelKey
     */
    private $labelKey;

    /**
     * ApiFormChoices constructor.
     *
     * @param string $indexUrl
     * @param string $singleUrl
     * @param string $labelKey
     * @param string $valueKey
     */
    public function __construct(
        string $indexUrl = '',
        string $singleUrl = '',
        string $labelKey = '',
        string $valueKey = ''
    ) {
        $this->indexUrl = $indexUrl;
        $this->singleUrl = $singleUrl;
        $this->labelKey = $valueKey;
        $this->valueKey = $labelKey;
    }

    /**
     * Get all choices.
     *
     * @return array
     */
    public function getChoices(): array {
        return [
            'api' => [
                'searchUrl' => $this->indexUrl,
                'singleUrl' => $this->singleUrl,
                'valueKey' => $this->valueKey,
                'labelKey' => $this->labelKey,
            ]
        ];
    }
}
