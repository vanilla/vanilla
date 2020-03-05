<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Vanilla\Contracts\Web\UASnifferInterface;

/**
 * @inheritdoc
 */
class UASniffer implements UASnifferInterface {

    /** @var \Gdn_Session */
    private $session;

    /**
     * DI.
     *
     * @param \Gdn_Session $session
     */
    public function __construct(\Gdn_Session $session) {
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function isIE11(): bool {
        // Bailout early on guest requests.
        // We do not vary cache headers for guests, so we can't rely on the UA string in those scenarios.
        if (!$this->session->isValid()) {
            return false;
        }

        // Implementation based on https://stackoverflow.com/a/24913015
        $agentString = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (strpos($agentString, 'rv:11.0') !== false
            && strpos($agentString, 'Trident/7.0;')!== false
        ) {
            return true;
        } else {
            return false;
        }
    }
}
