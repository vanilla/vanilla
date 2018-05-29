<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Metadata\Parser;

use DOMDocument;

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
        $body = $linkedData['description'] ?? '';
        $insertUser = $linkedData['author'] ?? [];

        $result = [
            'Attributes' => [
                'discussion' => [
                    'title' => $title,
                    'body' => $body
                ]
            ]
        ];

        // Author information.
        if (!empty($insertUser)) {
            $result['Attributes']['discussion']['insertUser'] = [];
            if (array_key_exists('name', $insertUser) && is_string($insertUser['name'])) {
                $result['Attributes']['discussion']['insertUser']['name'] = $insertUser['name'];
            }
            if (array_key_exists('image', $insertUser) && is_string($insertUser['image'])) {
                $result['Attributes']['discussion']['insertUser']['photoUrl'] = $insertUser['image'];
            }
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

        if ($context === 'http://schema.org/' && $type === 'DiscussionForumPosting') {
            $result = $this->processDiscussionForumPosting($linkedData);
        }

        return $result;
    }
}
