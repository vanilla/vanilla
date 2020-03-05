<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent;

use DateTime;
use DateTimeInterface;
use Garden\JsonFilterTrait;
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
    use JsonFilterTrait;

    /** @var array */
    protected $data = [];

    /**
     * Create the embed by taking some data and validating it.
     *
     * @param array $data
     *
     * @throws ValidationException If the data doesn't match the specification.
     */
    public function __construct(array $data) {
        $this->updateData($data);
    }

    /**
     * Update various embed data fields.
     *
     * @param array $fieldsToUpdate A sparse array of fields and data to update.
     * @param bool $revalidate Whether or not we should re-normalize and validate the content.
     *
     * @throws ValidationException If the data doesn't match the specification.
     */
    public function updateData(array $fieldsToUpdate, bool $revalidate = true) {
        $data = array_merge($this->data, $fieldsToUpdate);

        // Validate the data before assigning local variables.
        if ($revalidate) {
            $data = $this->normalizeCommonData($data);
            $data = $this->normalizeData($data);
            $data = $this->fullSchema()->validate($data);
        }
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function jsonSerialize() {
        return $this->getData();
    }

    /**
     * Get normalized data from the embed.
     *
     * @return array;
     */
    public function getData(): array {
        return $this->jsonFilter($this->data);
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
            'data' => json_encode($this, JSON_UNESCAPED_UNICODE)
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
