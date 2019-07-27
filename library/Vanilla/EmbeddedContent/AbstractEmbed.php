<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

use DateTime;
use DateTimeInterface;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\Web\TwigRenderTrait;

/**
 * The base Embed class.
 *
 * Responsibilities
 * - Storing/validating embed data.
 * - Rendering that data as HTML.
 */
abstract class AbstractEmbed implements \JsonSerializable {

    use TwigRenderTrait;

    /** @var array */
    protected $data;

    /**
     * Create the embed by taking some data and validating it.
     *
     * @param array $data
     *
     * @throws ValidationException If the data doesn't match the specification.
     */
    public function __construct(array $data) {
        // Validate the data before assigning local variables.
        $data = $this->normalizeCommonData($data);
        $normalizedData = $this->normalizeData($data);
        $validatedData = $this->fullSchema()->validate($normalizedData);
        $this->data = $validatedData;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize() {
        return $this->jsonFilter($this->data);
    }

    /**
     * Prepare data for json_encode
     *
     * @param mixed $value
     * @return mixed
     */
    private function jsonFilter($value) {
        $fn = function (&$value, $key = '') use (&$fn) {
            if (is_array($value)) {
                array_walk($value, function (&$childValue, $childKey) use ($fn, $key) {
                    $fn($childValue, $childKey);
                });
            } elseif ($value instanceof DateTimeInterface) {
                $value = $value->format(DateTime::RFC3339);
            }
        };

        if (is_array($value)) {
            array_walk($value, $fn);
        } else {
            $fn($value);
        }

        return $value;
    }

    /**
     * Get the URL for the embed.
     *
     * @return string
     */
    public function getUrl(): string {
        return $this->data['url'];
    }

    /**
     * Render the HTML form of the embed.
     *
     * This default implementation assumes javascript rendering the browser.
     * It places the encoded embed content in the HTML for the javascript to find.
     *
     * @return string
     */
    public function renderHtml(): string {
        $viewPath = dirname(__FILE__) . '/AbstractEmbed.twig';
        return $this->renderTwig($viewPath, [
            'url' => $this->getUrl(),
            'data' => $this
        ]);
    }

    /**
     * Some types of data normalization are quite common with the original embeds.
     * This will be performed here.
     *
     * @param array $data
     * @return array
     */
    protected function normalizeCommonData(array $data): array {
        $data = EmbedUtils::remapProperties($data, [
            'embedType' => 'type',
        ]);
        return $data;
    }

    /**
     * Normalize backwards compatible versions of the data structure.
     * By default no normalization is implemented.
     *
     * @param array $data The raw data.
     * @return array The normalized data.
     */
    public function normalizeData(array $data): array {
        return $data;
    }

    /**
     * Get the various values of `type` that this embed supports.
     *
     * @return string[]
     */
    abstract protected function getAllowedTypes(): array;

    /**
     * Get the Garden\Schema for your embed content.
     *
     * This will be added to the base schema. Any fields not specified here will be stripped.
     *
     * @example
     * The base structure looks like this.
     * [
     *     'url:s',
     *     'type:s',
     *     // Whatever you specify here.
     * ]
     *
     * @return Schema
     */
    abstract protected function schema(): Schema;

    /**
     * @return Schema
     */
    private function fullSchema(): Schema {
        $baseSchema = Schema::parse([
            'url' => [
                'type' => 'string',
                'format' => 'uri',
            ],
            'embedType:s' => [
                'enum' => $this->getAllowedTypes(),
            ],
            'name:s?'
        ]);

        return $this->schema()->merge($baseSchema);
    }
}
