<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots\Embeds;

/**
 * Blot for rendering finalized @mentions.
 */
class MentionBlot extends AbstractInlineEmbedBlot {

    /** @var string */
    private $username;

    /**
     * Prepend an @ onto the text content of the blot.
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);
        $this->username = $this->content;
        $this->content = "@".$this->username;
    }

    /**
     * Return the username for the mention.
     *
     * @return string
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * @inheritDoc
     */
    protected static function getInsertKey(): string {
        return "insert.mention.name";
    }

    /**
     * @inheritDoc
     */
    protected function getContainerHTMLTag(): string {
        return "a";
    }

    /**
     * @inheritDoc
     */
    protected function getContainerHMTLAttributes(): array {
        $mentionData = $this->currentOperation["insert"]["mention"] ?? [];
        $userID = $mentionData["userID"] ?? -1;
        $name = $mentionData["name"] ?? "";

        $sanitizedUserID = filter_var($userID, FILTER_SANITIZE_NUMBER_INT);
        $sanitizedName = htmlspecialchars($name);
        $url = $this->getMentionUrl($name);

        return [
            "class" => "atMention",
            "data-username" => $sanitizedName,
            "data-userid" => $sanitizedUserID,
            "href" => $url,
        ];
    }

    /**
     * Get a valid URL for a users profile for the mention,
     *
     * @param string $name The name of user to get the url for. This shouldn't be escaped yet.
     *
     * @return string
     */
    private function getMentionUrl(string $name): string {
        $encodedName = rawurlencode($name);
        $mentionPath = "/profile/$encodedName";
        return url($mentionPath, true);
    }
}
