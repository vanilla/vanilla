<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Web;

/**
 * Class WebLinking
 */
class WebLinking {

    /** @var array */
    private $links = [];

    /**
     * Add a link.
     *
     * @link http://tools.ietf.org/html/rfc5988
     * @link http://www.iana.org/assignments/link-relations/link-relations.xml
     *
     * @param string $uri Target URI for the link.
     * @param string $rel Link relation. Either an IANA registered type, or an absolute URL.
     * @param array $attributes Link parameters.
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
            $this->links[$rel] = array_filter($this->links[$rel], function($element) use ($uri) {
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
        return $headerValue ? 'Link: '.$headerValue : null;
    }

    /**
     * return link header value.
     */
    public function getLinkHeaderValue() {
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
}
