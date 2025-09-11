<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Gdn;
use Gdn_Upload;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\Formatting\Attachment;
use Vanilla\Models\VanillaMediaSchema;

/**
 * File Embed data object.
 */
class FileEmbed extends AbstractEmbed
{
    const TYPE = "file";

    /**
     * @inheritdoc
     */
    protected function getAllowedTypes(): array
    {
        return [self::TYPE];
    }

    /**
     * @inheritdoc
     */
    public function normalizeData(array $data): array
    {
        // The legacy file embeds have everything underneath attributes.
        $attributes = $data["attributes"] ?? null;
        if ($attributes !== null) {
            $data = $attributes + $data;
        }

        if (!isset($data["foreignUrl"])) {
            $data["foreignUrl"] = null;
        }

        $data["url"] = $this->unnestDownloadUrl($data["url"]);
        $data["downloadUrl"] = self::makeDownloadUrl($data["url"]);

        // Replace non-ASCII characters in the URL with their HTML entities.
        $OriginalPath = parse_url($data["url"], PHP_URL_PATH);
        $encodedUrl = rawurlencode($OriginalPath);
        $data["url"] = str_replace($OriginalPath, $encodedUrl, $data["url"]);

        // Replace the encoded / with proper ones.
        $data["url"] = str_replace("%2F", "/", $data["url"]);

        // The `type` field may contain the mime-type data.
        return $data;
    }

    /**
     * @param string $url
     * @return string
     */
    public static function makeDownloadUrl(string $url): string
    {
        return \Gdn::request()->getSimpleUrl("/api/v2/media/download-by-url?url=" . urlencode($url));
    }

    /**
     * Render the image out.
     *
     * @return string
     */
    public function renderHtml(): string
    {
        $uploader = Gdn::getContainer()->get(Gdn_upload::class);
        if (!$uploader->isOwnWebPath($this->getUrl())) {
            return "<div></div>";
        }

        $viewPath = dirname(__FILE__) . "/FileEmbed.twig";
        return $this->renderTwig($viewPath, [
            "url" => $this->getUrl(),
            "downloadUrl" => $this->getData()["downloadUrl"],
            "name" => $this->data["name"],
            "data" => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema
    {
        return new VanillaMediaSchema(true);
    }

    /**
     * Get the embed as an attachment.
     *
     * @return Attachment
     */
    public function asAttachment(): Attachment
    {
        return Attachment::fromArray($this->getData());
    }

    /**
     * Handle https://higherlogic.atlassian.net/browse/VNLA-8498 that was serialized into the database
     *
     * @param string $url
     * @return string
     */
    public function unnestDownloadUrl(string $url): string
    {
        if (!str_contains(needle: "/api/v2/media/download-by-url", haystack: $url)) {
            return $url;
        }

        // Get the url by removing the download url prefix.
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $decodedQuery);

        if (isset($decodedQuery["url"])) {
            return $this->unnestDownloadUrl($decodedQuery["url"]);
        } else {
            return $url;
        }
    }
}
