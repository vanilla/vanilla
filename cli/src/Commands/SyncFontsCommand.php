<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Garden\Http\HttpClient;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Cli\Utils\SimpleScriptLogger;
use Vanilla\FileUtils;
use Vanilla\Utility\StringUtils;

/**
 * Install command.
 */
class SyncFontsCommand extends Console\Command\Command
{
    use ScriptLoggerTrait;

    const FONTS_SOURCES = [
        "Open Sans" =>
            "https://fonts.googleapis.com/css?family=Open%20Sans:400,400italic,600,700,700italic&display=swap",
        "Montserrat" =>
            "https://fonts.googleapis.com/css?family=Montserrat:400,400italic,600,700,700italic&display=swap",
        "Roboto" => "https://fonts.googleapis.com/css?family=Roboto:400,400italic,600,700,700italic&display=swap",
        "Lato" => "https://fonts.googleapis.com/css?family=Lato:400,400italic,600,700,700italic&display=swap",
        "Roboto Condensed" =>
            "https://fonts.googleapis.com/css?family=Roboto%20Condensed:400,400italic,600,700,700italic&display=swap",
        "Source Sans Pro" =>
            "https://fonts.googleapis.com/css?family=Source%20Sans%20Pro:400,400italic,600,700,700italic&display=swap",
        "Merriweather" =>
            "https://fonts.googleapis.com/css?family=Merriweather:400,400italic,600,700,700italic&display=swap",
        "Raleway" => "https://fonts.googleapis.com/css?family=Raleway:400,400italic,600,700,700italic&display=swap",
        "Roboto Mono" =>
            "https://fonts.googleapis.com/css?family=Roboto%20Mono:400,400italic,600,700,700italic&display=swap",
        "Poppins" => "https://fonts.googleapis.com/css?family=Poppins:400,400italic,600,700,700italic&display=swap",
        "Nunito" => "https://fonts.googleapis.com/css?family=Nunito:400,400italic,600,700,700italic&display=swap",
        "PT Serif" => "https://fonts.googleapis.com/css?family=PT%20Serif:400,400italic,600,700,700italic&display=swap",
    ];

    // User agent required to get woff2 support.
    const USER_AGENT = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.2210.77";

    // Directory we copy the font files too.
    public const OUT_DIR = PATH_ROOT . "/resources/fonts";

    private HttpClient $client;

    /**
     * @param HttpClient $client
     */
    public function __construct(HttpClient $client)
    {
        parent::__construct();
        $this->client = $client;
        $this->client->setThrowExceptions(true);
        $this->client->setDefaultHeader("User-Agent", self::USER_AGENT);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName("sync-fonts")->setDescription(
            "Used to synchronize fonts from google fonts for our static google font service."
        );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach (self::FONTS_SOURCES as $fontName => $fontUrl) {
            $this->syncFont($fontName, $fontUrl);
        }

        return self::SUCCESS;
    }

    /**
     * Download a font css file and all of the fonts referenced inside of it.
     *
     * @param string $fontName The name of the font.
     * @param string $fontUrl The URL of the font css file.
     */
    private function syncFont(string $fontName, string $fontUrl)
    {
        $this->logger()->title("Synchronizing Font - $fontName");
        $fontCss = $this->client->get($fontUrl);
        $fontRootDir = self::OUT_DIR . "/$fontName";
        FileUtils::ensureCleanDirectory($fontRootDir, 0666);

        $fontFileUrls = StringUtils::parseCssUrls($fontCss);
        $countFonts = count($fontFileUrls);
        $this->logger()->info("Found {$countFonts} fonts to download.");

        foreach ($fontFileUrls as $targetName => $fontFileUrl) {
            $filePath = $fontRootDir . "/" . $targetName;
            $contents = $this->client->get($fontFileUrl);
            file_put_contents($filePath, $contents);
            $this->logger()->info(".", [SimpleScriptLogger::CONTEXT_LINE_COUNT => 0]);
        }

        $modifiedCss = str_replace(
            array_values($fontFileUrls),
            array_map(function ($fontDir) {
                return "./" . $fontDir;
            }, array_keys($fontFileUrls)),
            $fontCss
        );
        file_put_contents($fontRootDir . "/font.css", $modifiedCss);

        $this->logger()->success("\nSuccessfully downloaded {$countFonts} fonts.");
    }
}
