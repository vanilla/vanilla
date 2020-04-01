<?php
/**
 * Message module.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handle display of a message.
 */
class MessageModule extends Gdn_Module {

    /** @var string */
    protected $_Message;

    /**
     *
     *
     * @param string $sender
     * @param bool $message
     */
    public function __construct($sender = '', $message = false) {
        parent::__construct($sender);

        // Filter message markup, if present.
        if (is_string($message['Content'] ?? null)) {
            /** @var Vanilla\Formatting\Html\HtmlSanitizer */
            $htmlSanitizer = Gdn::getContainer()->get(Vanilla\Formatting\Html\HtmlSanitizer::class);
            $message['Content'] = $htmlSanitizer->filter($message['Content']);
        }

        $this->_ApplicationFolder = 'dashboard';
        $this->_Message = $message;
    }

    /**
     *
     *
     * @return mixed|string
     */
    public function assetTarget() {
        return $this->_Message == false ? 'Content' : val('AssetTarget', $this->_Message);
    }
}
