<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Metadata\Parser;

use DOMDocument;
use DateTimeImmutable;

class JsonLDParser implements Parser {

    /**
     * @inheritdoc
     */
    public function parse(DOMDocument $document): array {
        /** @var \DOMNodeList $meta */
        $scriptTags = $document->getElementsByTagName('script');
        $result = [];

        /** @var \DOMElement $tag */
        foreach ($scriptTags as $tag) {
            if ($tag->getAttribute('type') === 'application/ld+json') {
                $linkedData = json_decode($tag->textContent, true);
                if (is_array($linkedData)) {
                    $result = $this->processLinkedData($linkedData);
                }
                break;
            }
        }

        return $result;
    }

    /**
     * Handle data for a DiscussionForumPosting document.
     *
     * @param array $linkedData
     * @return array
     * @link http://schema.org/DiscussionForumPosting
     */
    private function processDiscussionForumPosting(array $linkedData): array {
        $title = $linkedData['headline'] ?? '';
        $body = $linkedData['description'] ?? $linkedData['articleBody'] ?? '';
        $insertUser = $linkedData['author'] ?? [];
        $dateInserted = $linkedData['dateCreated'] ?? '';

        $result = [
            'Attributes' => [
                'subtype' => 'discussion',
                'discussion' => [
                    'title' => $title,
                    'body' => $body,
                ]
            ]
        ];

        if ($dateInserted) {
            // Attempt to normalize the format.
            try {
                $dateInsertedDateTime = new DateTimeImmutable($dateInserted);
                $result['Attributes']['discussion']['dateInserted'] = $dateInsertedDateTime->format('c');
            } catch (\Exception $e) {
            }
        }

        // Author information.
        if (!empty($insertUser)) {
            $userFragment = [];
            $userAttributes = [
                'name' => 'name',
                'image' => 'photoUrl',
                'url' => 'url'
            ];
            foreach ($userAttributes as $authorAttribute => $userAttribute) {
                if (array_key_exists($authorAttribute, $insertUser) && is_scalar($insertUser[$authorAttribute])) {
                    $userFragment[$userAttribute] = $insertUser[$authorAttribute];
                }
            }
            $result['Attributes']['discussion']['insertUser'] = $userFragment;
        }

        return $result;
    }

    /**
     * Given a JSON LD collection, process it and return relevant document information.
     *
     * @param array $linkedData
     * @return array
     */
    private function processLinkedData(array $linkedData): array {
        $result = [];

        $context = $linkedData['@context'] ?? null;
        $type = $linkedData['@type'] ?? null;

        if ($context === 'https://schema.org' && $type === 'DiscussionForumPosting') {
            $result = $this->processDiscussionForumPosting($linkedData);
        }

        return $result;
    }
}
