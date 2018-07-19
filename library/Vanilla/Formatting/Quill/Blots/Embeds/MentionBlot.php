<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Formatting\Quill\Blots\Embeds;

/**
 * Blot for rendering finalized @mentions.
 */
class MentionBlot extends AbstractInlineEmbedBlot {

    /**
     * Prepend an @ onto the text content of the blot.
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);
        $this->content = "@".$this->content;
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
