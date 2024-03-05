<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use HeadlessChromium\Browser\ProcessAwareBrowser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\CommunicationException;
use HeadlessChromium\Exception\CommunicationException\CannotReadResponse;
use HeadlessChromium\Exception\CommunicationException\InvalidResponse;
use HeadlessChromium\Exception\CommunicationException\ResponseHasError;
use HeadlessChromium\Exception\NavigationExpired;
use HeadlessChromium\Exception\NoResponseAvailable;
use HeadlessChromium\Exception\OperationTimedOut;
use HeadlessChromium\Page;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Cli\Utils\DatabaseCommand;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Utility\Timers;

/**
 * Convert posts to rich or rich2 text.
 */
class ConvertToRich extends DatabaseCommand
{
    use ScriptLoggerTrait;

    const BASE_PATH = "/utility/convert-html/";
    const OPT_THROW_FORM_ERRORS = "throwFormErrors";
    const OPT_DELIVERY_METHOD = "deliveryMethod";
    const OPT_DELIVERY_TYPE = "deliveryType";
    const OPT_PERMANENT = "permanent";

    const RECORD_MAPPING = [
        "discussion" => ["table" => "Discussion", "primaryKey" => "DiscussionID"],
        "comment" => ["table" => "Comment", "primaryKey" => "CommentID"],
    ];

    /** @var int */
    private int $limit;

    /** @var string */
    private string $format;

    /** @var string */
    private string $siteUrl;

    /** @var ProcessAwareBrowser */
    private ProcessAwareBrowser $browser;

    /** @var string|null */
    private $pattern;

    private BrowserFactory $browserFactory;

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setName("to-rich")->setDescription("Convert records (discussions,comments) to rich text.");
        $definition = $this->getDefinition();
        $definition->addOption(
            new InputOption(
                "siteUrl",
                null,
                InputOption::VALUE_REQUIRED,
                "Site to apply the conversion on. e.g. `http://dev.vanilla.localhost`"
            )
        );

        $definition->addOption(
            new InputOption(
                "recordTypes",
                null,
                InputOption::VALUE_OPTIONAL,
                "Comma separated list of the record type to convert (comment,discussion). Default to 'all'."
            )
        );

        $definition->addOption(
            new InputOption(
                "limit",
                null,
                InputOption::VALUE_OPTIONAL,
                "limit the number of posts to convert in a batch. Defaults to 100."
            )
        );

        $definition->addOption(
            new InputOption(
                "format",
                null,
                InputOption::VALUE_OPTIONAL,
                "Format to convert to (`rich`, `rich2`). Defaults to `rich2`."
            )
        );

        $definition->addOption(
            new InputOption("pattern", null, InputOption::VALUE_OPTIONAL, "Pattern to match when converting records.")
        );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->limit = $input->getOption("limit") ?? 100;
        $this->format = $input->getOption("format") ?? "rich2";
        $this->siteUrl = $input->getOption("siteUrl");
        $this->pattern = $input->getOption("pattern");
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $this->logger()->info("Initializing mock browser. This might take a while.");
        $this->browserFactory = new BrowserFactory();
        $this->browserFactory->addOptions([
            "ignoreCertificateErrors" => true,
            "debugLogger" => $this->logger(),
            "connectionDelay" => 1,
        ]);
        $this->browser = $this->browserFactory->createBrowser();
        $this->logger()->info("Mock browser initialized.");

        try {
            $recordTypes = $input->getOption("recordTypes") ?? array_keys(self::RECORD_MAPPING);
            if ($recordTypes === "all") {
                $recordTypes = array_keys(self::RECORD_MAPPING);
            }
            if (!is_array($recordTypes)) {
                $recordTypes = explode(",", $recordTypes);
            }

            foreach ($recordTypes as $recordType) {
                $primaryID = self::RECORD_MAPPING[$recordType]["primaryKey"];
                $where = [];

                if ($this->pattern) {
                    $where = ["Body LIKE" => "%$this->pattern%"];
                }

                do {
                    $records = $this->getRecords($recordType, $where);
                    foreach ($records as $record) {
                        $recordID = $record[$primaryID];
                        $this->logger()->info("Converting $recordType #$recordID.");
                        try {
                            $this->convertRecord($recordType, $recordID);
                        } catch (OperationTimedOut $e) {
                            $this->logger()->info("Retrying conversion of $recordType #$recordID.");

                            // Create a new Browser since the previous one timeout.
                            $this->browser->close();
                            $this->browser = $this->browserFactory->createBrowser();
                            $this->convertRecord($recordType, $recordID);
                        }
                    }
                } while (!empty($records));
            }
        } catch (\Exception $e) {
            $this->logger()->error($e->getMessage());
        } finally {
            $stopTime = microtime(true);
            $time = Timers::formatDuration(($stopTime - $startTime) * 1000);
            $this->logger()->info("Time elapsed: $time");
            $this->browser->close();
        }

        $this->logger()->success("Conversion completed.");
        return self::SUCCESS;
    }

    /**
     * Call `/utility/convert-html/{format}/{recordType}/{recordID}` and save the result in the database.
     *
     * @param string $recordType
     * @param int $recordID
     * @return void
     * @throws CommunicationException
     * @throws CannotReadResponse
     * @throws InvalidResponse
     * @throws ResponseHasError
     * @throws OperationTimedOut
     * @throws NavigationExpired
     * @throws NoResponseAvailable
     */
    protected function convertRecord(string $recordType, int $recordID): void
    {
        $recordStartTime = microtime(true);
        $page = $this->browser->createPage();

        try {
            $url = $this->siteUrl . self::BASE_PATH . $this->format . "/" . $recordType . "/" . $recordID;
            $page->navigate($url)->waitForNavigation(Page::NETWORK_IDLE);
            $html = $page
                ->dom()
                ->querySelector("#formatted-body")
                ->getHTML();

            if (is_null($html)) {
                return;
            }

            preg_match("#<div id=\"formatted-body\".*?>(.+)</div>#", $html, $matches);

            if (isset($matches[1])) {
                $this->updateRecords($recordType, $recordID, $matches[1]);
            } else {
                echo 0;
            }
            $recordEndTime = microtime(true);
            $this->logger()->success(
                "Converted $recordType #$recordID in " .
                    Timers::formatDuration(($recordEndTime - $recordStartTime) * 1000)
            );
        } finally {
            $page->close();
        }
    }

    /**
     * Fetch the Record by record type.
     *
     * @param string $recordType
     * @param int $offset
     * @param array $where
     * @return array
     * @throws \Exception
     */
    protected function getRecords(string $recordType, array $where = []): array
    {
        $sql = $this->getDatabase()->createSql();
        $result = $sql
            ->select()
            ->from(self::RECORD_MAPPING[$recordType]["table"])
            ->where($where)
            ->where("Format", "html")
            ->limit($this->limit)
            ->get()
            ->resultArray();
        return $result;
    }

    /**
     * Update the records.
     *
     * @param string $recordType
     * @param $recordID
     * @param $body
     * @return void
     */
    protected function updateRecords(string $recordType, $recordID, $body): void
    {
        $sql = $this->getDatabase()->createSql();
        $primaryKey = self::RECORD_MAPPING[$recordType]["primaryKey"];
        $table = self::RECORD_MAPPING[$recordType]["table"];
        $result = $sql->update($table, ["Body" => $body, "Format" => $this->format], [$primaryKey => $recordID])->put();

        if (!$result) {
            $this->logger()->error("Failed to update $recordType #$recordID.");
        }
    }
}
