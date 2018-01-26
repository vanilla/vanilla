<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Web;

use Garden\Web\ViewInterface;
use Garden\Web\Data;

/**
 * Class JsonView
 */
class JsonView implements ViewInterface {

    /** @var WebLinking */
    private $webLinking;

    /**
     * JsonView constructor.
     *
     * @param WebLinking $webLinking
     */
    public function __construct(WebLinking $webLinking) {
        $this->webLinking = $webLinking;
    }

    /**
     * {@inheritdoc}
     */
    public function render(Data $data) {

        $paging = $data->getMeta('paging', []);

        foreach(($paging['links'] ?? []) as $relType => $uri) {
            $this->webLinking->addLink($relType, $uri);
        }

        $data->setHeader('Link', $this->webLinking->getLinkHeaderValue());

        echo $data->render();
    }
}
