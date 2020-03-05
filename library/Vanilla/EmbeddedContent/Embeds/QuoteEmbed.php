<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbedUtils;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Models\FormatSchema;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Utility\InstanceValidatorSchema;

/**
 * Fallback scraped link embed.
 */
class QuoteEmbed extends AbstractEmbed {

    const TYPE = "quote";

    const SECURE_UNRENDERED_MESSAGE = 'Not rendered yet for security reasons. Did you forget to run QuoteEmbedFilter::filterEmbed()?';

    /**
     * @inheritdoc
     */
    protected function getAllowedTypes(): array {
        return [self::TYPE];
    }

    /**
     * @inheritdoc
     */
    public function normalizeData(array $data): array {
        $data = EmbedUtils::remapProperties($data, [
            'name' => 'attributes.name',
            'bodyRaw' => 'attributes.bodyRaw',
            'format' => 'attributes.format',
            'dateInserted' => 'attributes.dateInserted',
            'insertUser' => 'attributes.insertUser',
            'discussionID' => 'attributes.discussionID',
            'commentID' => 'attributes.commentID',
        ]);

        // Handle the IDs
        $discussionID = $data['discussionID'] ?? null;
        $commentID = $data['commentID'] ?? null;

        if ($discussionID !== null) {
            $data['recordID'] = $discussionID;
            $data['recordType'] = 'discussion';
        } elseif ($commentID !== null) {
            $data['recordID'] = $commentID;
            $data['recordType'] = 'comment';
        }

        if (!isset($data['displayOptions'])) {
            $hasTitle = isset($data['name']);
            $data['displayOptions'] = QuoteEmbedDisplayOptions::minimal($hasTitle);
        } elseif (is_array($data['displayOptions'])) {
            $data['displayOptions'] = QuoteEmbedDisplayOptions::from($data['displayOptions']);
        }

        // Normalize the body into a string.
        // Some older quote embeds had rich quote bodies as arrays.
        $format = $data['format'];
        $bodyRaw = $data['bodyRaw'];

        if (strtolower($format) === RichFormat::FORMAT_KEY && is_array($bodyRaw)) {
            $data['bodyRaw'] = json_encode($bodyRaw, JSON_UNESCAPED_UNICODE);
        }

        // Due to security sentive nature of these they should always be rendered by the filterer.
        $data['body'] = self::SECURE_UNRENDERED_MESSAGE;
        $userLabel = $data['insertUser']['label'] ?? null;
        if ($userLabel !== null) {
            $data['insertUser']['label'] = self::SECURE_UNRENDERED_MESSAGE;
        }

        return $data;
    }

    /**
     * Override to remove rawBody from output. It's unnecssary.
     * @inheritdoc
     */
    public function renderHtml(): string {
        $viewPath = dirname(__FILE__) . '/QuoteEmbed.twig';
        $data = $this->getData();

        // No need to bloat the HTML with this.
        unset($data['bodyRaw']);

        return $this->renderTwig($viewPath, [
            'url' => $this->getUrl(),
            'data' => json_encode($this, JSON_UNESCAPED_UNICODE)
        ]);
    }

    /**
     * Get the display options of the quote.
     *
     * @return QuoteEmbedDisplayOptions
     */
    public function getDisplayOptons(): QuoteEmbedDisplayOptions {
        return $this->data['displayOptions'];
    }

    /**
     * Get the userID of the quote.
     *
     * @return int
     */
    public function getUserID(): int {
        return $this->data['insertUser']['userID'];
    }

    /**
     * Get the name of the user being quoted.
     *
     * @return string
     */
    public function getUsername(): string {
        return $this->data['insertUser']['name'];
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return Schema::parse([
            'recordID:i',
            'recordType:s',
            'body:s', // The body is need currnetly during edit mode,
            // to prevent needing extra server roundtrips to render them.
            'bodyRaw:s|a', // Raw body is the source of truth for the embed.
            'format' => new FormatSchema(true),
            'dateInserted:dt',
            'insertUser' => new UserFragmentSchema(),
            'displayOptions' => new InstanceValidatorSchema(QuoteEmbedDisplayOptions::class),
            'discussionLink:s?',

            // Optional properties
            'category:o?' => [
                'categoryID',
                'name',
                'url',
            ],
        ]);
    }
}
