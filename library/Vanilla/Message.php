<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

/**
 * Represent a basic UI message.
 */
class Message {

    const TYPE_ALERT = "Alert";

    const TYPE_CASUAL = "Casual";

    const TYPE_INFORMATION = "Info";

    const TYPE_WARNING = "Warning";

    /** @var string */
    private $htmlBody;

    /** @var array */
    private $classes = ["DismissMessage"];

    /** @var string */
    private $type;

    /**
     * Create a new message.
     *
     * @param string $htmlBody
     * @param string $type
     */
    public function __construct(string $htmlBody, string $type) {
        $this->setHtmlBody($htmlBody);
        $this->setType($type);
    }

    /**
     * Render contents when the instance is referenced as a string.
     *
     * @return string
     */
    public function __toString() {
        $classes = $this->classes;
        $classes[] = "{$this->type}Message";
        return '<div class="' . implode(" ", $classes) . '">' . $this->htmlBody . '</div>';
    }

    /**
     * Set the HTML content of this message.
     *
     * @param string $htmlBody
     */
    public function setHtmlBody(string $htmlBody) {
        $this->htmlBody = $htmlBody;
    }

    /**
     * Set the message type.
     *
     * @param string $type
     */
    public function setType(string $type) {
        $validTypes = [static::TYPE_ALERT, static::TYPE_CASUAL, static::TYPE_INFORMATION, static::TYPE_WARNING];

        if (!in_array($type, $validTypes)) {
            throw new \InvalidArgumentException("Invalid type: {$type}. Type must be one of the following: " . implode(", ", $validTypes));
        }

        $this->type = $type;
    }
}
