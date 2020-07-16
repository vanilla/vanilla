<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Pagination;

use Garden\Http\HttpResponse;
use Garden\Web\Data;

/**
 * Class WebLinking
 */
class WebLinking {

    const WEB_LINK_REGEX = '/<(?<link>[0-9a-zA-Z$-_.+!*\'(),:?=&%#]+)>;\s+rel="(?<rel>next|prev)"/i';
    const HEADER_NAME = 'Link';

    /** @var array */
    private $links = [];

    /**
     * Add a link.
     *
     * @link http://tools.ietf.org/html/rfc5988
     * @link http://www.iana.org/assignments/link-relations/link-relations.xml
     *
     * @param string $rel Link relation. Either an IANA registered type, or an absolute URL.
     * @param string $uri Target URI for the link.
     * @param array $attributes Link parameters.
     *
     * @return WebLinking
     */
    public function addLink($rel, $uri, $attributes = []) {
        if (empty($this->links[$rel])) {
            $this->links[$rel] = [];
        }

        $this->links[$rel][] = [
            'uri' => $uri,
            'attributes' => $attributes,
        ];

        return $this;
    }

    /**
     * Remove a link.
     *
     * @param string $rel Link relation. Either an IANA registered type, or an absolute URL.
     * @param string $uri Target URI for the link.
     */
    public function removeLink($rel, $uri = null) {
        if (!isset($this->links[$rel])) {
            return;
        }

        if ($uri !== null) {
            $this->links[$rel] = array_filter($this->links[$rel], function ($element) use ($uri) {
                return $element['uri'] !== $uri;
            });
        } else {
            $this->links[$rel] = [];
        }

        if (!$this->links[$rel]) {
            unset($this->links[$rel]);
        }
    }

    /**
     * Return link header string.
     *
     * @return string|null
     */
    public function getLinkHeader() {
        $headerValue = $this->getLinkHeaderValue();
        return $headerValue ? self::HEADER_NAME.': '.$headerValue : null;
    }

    /**
     * Get the link header value.
     */
    public function getLinkHeaderValue(): string {
        $results = [];

        foreach ($this->links as $rel => $links) {
            foreach ($links as $data) {
                $parameters = '';
                foreach ($data['attributes'] as $param => $value) {
                    $parameters .= "; $param=\"$value\"";
                }
                $results[] = "<{$data['uri']}>; rel=\"$rel\"$parameters";
            }
        }

        return implode(", ", $results);
    }

    /**
     * Clear added links.
     * @return WebLinking
     */
    public function clear() {
        $this->links = [];
        return $this;
    }

    /**
     * A convenience function for setting a link heaer.
     *
     * @param Data $data
     */
    public function setHeader(Data $data) {
        $link = $data->getHeader(self::HEADER_NAME);
        if (empty($link)) {
            $link = $this->getLinkHeaderValue();
        } else {
            $link .= ', '.$this->getLinkHeaderValue();
        }
        $data->setHeader(self::HEADER_NAME, $link);
    }

    /**
     * Parse a link
     *
     * @param string $header The link header value.
     *
     * @return array
     * @example
     * [
     *     'previous' => 'https://something.com/page/1
     *     'next' => 'https://something.com/page/3
     * ]
     */
    public static function parseLinkHeaders(string $header): array {
        $segments = explode(',', $header);
        $result = [
            'prev' => null,
            'next' => null,
        ];
        foreach ($segments as $segment) {
            $segment = trim($segment);
            preg_match(self::WEB_LINK_REGEX, $segment, $matches);
            $link = $matches['link'] ?? null;
            $rel = $matches['rel'] ?? null;

            if (!$link) {
                // Badly formed.
                continue;
            }

            if ($rel === 'next') {
                $result['next'] = $link;
            } elseif ($rel === 'prev') {
                $result['prev'] = $link;
            }
        }

        return $result;
    }
}
