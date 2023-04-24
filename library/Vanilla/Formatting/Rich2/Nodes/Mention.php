<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

use Vanilla\Utility\HtmlUtils;

class Mention extends AbstractLeafNode
{
    const TYPE_KEY = "@";

    public bool $getChildren = false;

    /**
     * Overrides the method for rendering the html content of a mention.
     *
     * @return string
     */
    protected function renderHtmlContent(): string
    {
        $userID = $this->getUserID();
        $name = $this->getUserName();

        $sanitizedUserID = filter_var($userID, FILTER_SANITIZE_NUMBER_INT);
        $sanitizedName = htmlspecialchars($name);
        $attributes = HtmlUtils::attributes([
            "class" => "atMention",
            "data-username" => $sanitizedName,
            "data-userid" => $sanitizedUserID,
            "href" => $this->getUrl(),
        ]);
        return "<a $attributes>@{$this->getUserName()}</a>";
    }

    /**
     * Overrides the method for rendering the text content of a mention.
     *
     * @return string
     */
    protected function renderTextContent(): string
    {
        return "@" . $this->getUserName();
    }

    /**
     * Get the username for this mention
     *
     * @return string
     */
    public function getUserName(): string
    {
        return $this->data["name"] ?? "";
    }

    /**
     * Set the username for this mention
     *
     * @param string $name
     * @return void
     */
    public function setUserName(string $name)
    {
        $this->data["name"] = $name;
    }

    /**
     * Get the userID for this mention
     *
     * @return int
     */
    public function getUserID(): int
    {
        return $this->data["userID"] ?? -1;
    }

    /**
     * Set the userID for this mention
     *
     * @param int $userID
     * @return void
     */
    public function setUserID(int $userID)
    {
        $this->data["userID"] = $userID;
    }

    /**
     * Get the URL for this mention
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->data["url"] ?? "";
    }

    /**
     * Set the URL for this mention
     *
     * @param string $url
     * @return void
     */
    public function setUrl(string $url)
    {
        $this->data["url"] = $url;
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultTypeName(): string
    {
        return self::TYPE_KEY;
    }

    /**
     * @inheritDoc
     */
    public static function getAllowedChildClasses(): array
    {
        return [Text::class];
    }
}
