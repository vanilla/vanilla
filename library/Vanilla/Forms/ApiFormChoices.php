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
class ApiFormChoices implements FormChoicesInterface
{
    /**
     * ApiFormChoices constructor.
     *
     * @param string $indexUrl
     * @param string $singleUrl
     * @param string $valueKey
     * @param string $labelKey
     * @param string|null $extraLabelKey
     */
    public function __construct(
        private string $indexUrl = "",
        private string $singleUrl = "",
        private string $valueKey = "",
        private string $labelKey = "",
        private ?string $extraLabelKey = null,
        private ?array $staticOptions = null
    ) {
    }

    public function getChoices(): array
    {
        return [
            "api" => [
                "searchUrl" => $this->indexUrl,
                "singleUrl" => $this->singleUrl,
                "valueKey" => $this->valueKey,
                "labelKey" => $this->labelKey,
                "extraLabelKey" => $this->extraLabelKey,
            ],
        ];
    }

    /**
     * Get all choices.
     *
     * @return array
     */
    public function getOptionsData(): array
    {
        $result = [
            "optionsLookup" => [
                "searchUrl" => $this->normalizeApiUrl($this->indexUrl),
                "singleUrl" => $this->normalizeApiUrl($this->singleUrl),
                "valueKey" => $this->valueKey,
                "labelKey" => $this->labelKey,
                "extraLabelKey" => $this->extraLabelKey,
            ],
        ];

        if (!empty($this->staticOptions)) {
            $result["options"] = $this->staticOptions;
        }
        return $result;
    }

    /**
     * The select automatically prefixes the input with /api/v2, so make sure we remove it.
     *
     * @param string $url
     * @return string
     */
    private function normalizeApiUrl(string $url): string
    {
        // If the url starts with APIv2, remove it
        if (str_starts_with($url, "/api/v2")) {
            return substr($url, 7);
        } else {
            return $url;
        }
    }
}
