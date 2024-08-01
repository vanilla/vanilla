<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Http\HttpClient;
use Garden\Web\Exception\ClientException;
use Gdn;
use TypeError;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\KalturaEmbed;
use Vanilla\EmbeddedContent\EmbedUtils;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Utility\ArrayUtils;

/**
 * Factory for KalturaEmbed.
 */
class KalturaEmbedFactory extends AbstractEmbedFactory
{
    const DOMAINS = ["mediaspace.kaltura.com", "videos.kaltura.com"];

    /** @var HttpClient */
    private $httpClient;

    /**
     * DI.
     *
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedDomains(): array
    {
        $domains = self::DOMAINS;

        // Fetch custom domains & add them to the list of supported domains.
        $customDomains = Gdn::config(\VanillaSettingsController::CONFIG_KALTURA_DOMAINS, "");
        // If the custom domains config is a string, we split it to an array.
        if (is_string($customDomains)) {
            $customDomains = ArrayUtils::explodeTrim("\n", $customDomains);
        }
        $domains = array_merge($domains, $customDomains);

        return $domains;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain): string
    {
        // This can be rather varied, depending on if the user provides the oEmbed URL or the video's URL.
        // That's why we are allowing a wildcard here. (Was previously "`^/id/.*$`")
        return "`.*`";
    }

    /**
     * Generate the oEmbed API URL from the video's url.
     * Note: Currently the /oembed api supports self-referential urls.
     *
     * @param string $url Source Video URL.
     * @return string oEmbed API URL.
     */
    public function getOEmbedUrl(string $url): string
    {
        $oembedParams = http_build_query(["url" => $url]);
        $parsedUrl = parse_url($url);

        $oembedUrl = $parsedUrl["scheme"] . "://" . $parsedUrl["host"] . "/oembed?" . $oembedParams;
        return $oembedUrl;
    }

    /**
     * Use the page scraper to scrape page data.
     *
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed
    {
        $oembedUrl = $this->getOEmbedUrl($url);
        $response = $this->httpClient->get($oembedUrl, [], [], ["throw" => false]);

        // Example Response JSON
        // phpcs:disable Generic.Files.LineLength
        //{
        //    "entryId":"0_osb0x29b",
        //    "version":"1.0",
        //    "type":"video",
        //    "provider_url":"https:\/\/9008.mediaspace.kaltura.com\/",
        //    "provider_name":"1234578change1",
        //    "title":"Roar.mp4 - Shwiz",
        //    "width":"400",
        //    "height":"285",
        //    "playerId":29387141,
        //    "thumbnail_height":"285",
        //    "thumbnail_width":"400",
        //    "thumbnail_url":"https:\/\/cfvod.kaltura.com\/p\/9008\/sp\/900800\/thumbnail\/entry_id\/0_osb0x29b\/version\/100002\/width\/400\/height\/285",
        //    "author_name":"gonen.radai",
        //    "html":"<iframe id=\"kaltura_player\" src=\"https:\/\/cdnapi.kaltura.com\/p\/9008\/sp\/900800\/embedIframeJs\/uiconf_id\/35650011\/partner_id\/9008?iframeembed=true&playerId=kaltura_player&entry_id=0_osb0x29b&flashvars[leadWithHTML5]=true&flashvars[streamerType]=auto&flashvars[localizationCode]=en&flashvars[loadThumbnailWithKs]=true&flashvars[loadThumbnailsWithReferrer]=true&flashvars[expandToggleBtn.plugin]=false&flashvars[sideBarContainer.plugin]=true&flashvars[sideBarContainer.position]=left&flashvars[sideBarContainer.clickToClose]=true&flashvars[chapters.plugin]=true&flashvars[chapters.layout]=vertical&flashvars[chapters.thumbnailRotator]=false&flashvars[streamSelector.plugin]=true&flashvars[EmbedPlayer.SpinnerTarget]=videoHolder&flashvars[dualScreen.plugin]=true&flashvars[hotspots.plugin]=1&flashvars[Kaltura.addCrossoriginToIframe]=true&wid=1_i0c2o2q5\" width=\"400\" height=\"285\" allowfullscreen webkitallowfullscreen mozAllowFullScreen allow=\"autoplay *; fullscreen *; encrypted-media *\" sandbox=\"allow-forms allow-same-origin allow-scripts allow-top-navigation allow-pointer-lock allow-popups allow-modals allow-orientation-lock allow-popups-to-escape-sandbox allow-presentation allow-top-navigation-by-user-activation\" frameborder=\"0\" title=\"Kaltura Player\"><\/iframe>"
        //}
        // phpcs:enable Generic.Files.LineLength

        $data = [];
        $responseStatusCode = $response->getStatusCode();
        switch ($responseStatusCode) {
            case 200:
                if (!isset($response["html"])) {
                    // Got an unexpected response.
                    // This can be reached by using https://mediaspace.valdosta.edu/id/1_0wsxajr as a source URL,
                    // provided you added mediaspace.valdosta.edu to the DOMAINS array.
                    throw new ClientException("URL did not result in a JSON type response.", 406);
                } else {
                    // Got the expected response
                    $frameSrc = $this->getIframeSrcFromHtml($response["html"]);

                    [$height, $width] = EmbedUtils::extractDimensions($response);
                    $data = [
                        "embedType" => KalturaEmbed::TYPE,
                        "url" => $url,
                        "name" => $response["title"] ?? null,
                        "height" => $height,
                        "width" => $width,
                        "photoUrl" => $response["thumbnail_url"] ?? null,
                        "frameSrc" => $frameSrc ?? null,
                    ];
                }
                break;
            case 401:
                // Unauthorized
                throw new ClientException("You are not authorized to access this URL.", $responseStatusCode);
                break;
            default:
                $message = $response->getReasonPhrase();
                $responseBody = $response->getBody();
                if (isset($responseBody["error"]["message"])) {
                    $message = $responseBody["error"]["message"];
                }
                // Default thrown exception for any other unhandled error.
                throw new ClientException('Client exception: "' . $message . '".', $responseStatusCode);
                break;
        }

        return new KalturaEmbed($data);
    }

    /**
     * Returns the iFrame's `src` attribute's value provided by the Kaltura API.
     *
     * @param string $html iFrame embed code provided from the Kaltura API
     * @return string iFrame video src attribute
     */
    public function getIframeSrcFromHtml(string $html): string
    {
        $dom = new HtmlDocument($html);
        $iframeDomNodes = $dom->queryCssSelector("iframe");

        $frameSrc = "";

        $firstNode = $iframeDomNodes->item(0);
        if (!$firstNode instanceof \DOMElement) {
            throw new TypeError("iFrame node isn't an instance of DOMElement");
        }

        $frameSrc = $firstNode->getAttribute("src");

        return $frameSrc;
    }
}
