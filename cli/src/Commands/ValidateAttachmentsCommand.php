<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Exception;
use Garden\Container\MissingArgumentException;
use Garden\Http\HttpClient;
use Gdn;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Cli\Utils\DatabaseCommand;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\CurrentTimeStamp;
use Vanilla\Utility\Timers;

/**
 * Go through the GDN_Media table and validate that the file is present on the CDN.
 */
class ValidateAttachmentsCommand extends DatabaseCommand
{
    use ScriptLoggerTrait;

    const REPORT_FULL = "full";
    const REPORT_NOT_FOUND = "not-found";

    /** @var int */
    private int $batchSize = 1000;

    /** @var string|bool */
    private $baseUrl;

    /** @var string|bool */
    private $reportPath;

    /** @var false|resource */
    private $fileStream = false;

    /* @var HttpClient */
    private HttpClient $httpClient;

    /** @var int */
    private int $success = 0;

    /** @var int */
    private int $failure = 0;

    /** @var string */
    private string $reportType;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName("validate-attachments")->setDescription(
            "Command to validate the entries of the GDN_Media table."
        );
        $definition = $this->getDefinition();
        $definition->addOption(new InputOption("siteID", null, InputOption::VALUE_OPTIONAL, "Site ID of the site"));
        $definition->addOption(
            new InputOption(
                "cdnBaseUrl",
                null,
                InputOption::VALUE_OPTIONAL,
                "Base Url of the CDN e.g. 'https://us.v-cdn.net/{SiteID}'"
            )
        );
        $definition->addOption(
            new InputOption("report-path", null, InputOption::VALUE_OPTIONAL, "Path where to save the csv report.")
        );

        $definition->addOption(
            new InputOption(
                "report-type",
                null,
                InputOption::VALUE_OPTIONAL,
                "Type of report: `full`, `not-found`. Will default to `not-found`"
            )
        );
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->httpClient = Gdn::getContainer()->get(HttpClient::class);

        // Make sure we have a valid domain.
        $siteID = $input->getOption("siteID");
        $cdnBaseURl = $input->getOption("cdnBaseUrl");
        $this->baseUrl = $cdnBaseURl ?? ("https://us.v-cdn.net/{$siteID}" ?? false);
        if (!$this->baseUrl) {
            throw new MissingArgumentException("SiteID or cdnBaseUrl");
        }

        // Create a CSV report if requested.
        $this->reportPath = $input->getOption("report-path") ?? false;
        if ($this->reportPath) {
            $this->reportType = $input->getOption("report-type") ?? self::REPORT_NOT_FOUND;
            $reportPath =
                $this->reportPath .
                "/" .
                "file_report_" .
                $input->getOption("db-name") .
                "_" .
                CurrentTimeStamp::get() .
                ".csv";
            $this->fileStream = fopen($reportPath, "w");
            fwrite($this->fileStream, "MediaID,Name,Path,ForeignID,ForeignTable,ResponseCode\n");
        }
    }

    /**
     * Fetch every file to make sure it's a valid entry.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $totalStartTime = microtime(true);
        $offset = 0;
        $medias = $this->fetchMediaRecords($offset);

        while ($medias) {
            foreach ($medias as $media) {
                try {
                    $media["FullPath"] = $this->setDomain($media["Path"]);
                    $headers = get_headers($media["FullPath"]);
                    if (strpos($headers[0], "200")) {
                        $this->success++;
                        $this->logger()->success("Media ID {$media["MediaID"]} response: {$headers[0]}");
                    } else {
                        $this->failure++;
                        $context = $this->formatRecords($media, $headers[0]);
                        $this->logger()->warning("Media ID {$media["MediaID"]} response: {$headers[0]}", $context);
                    }

                    if ($this->reportPath && (!strpos($headers[0], "200") || $this->reportType == self::REPORT_FULL)) {
                        $this->writeReport($media, $headers[0]);
                    }
                } catch (Exception $e) {
                    $this->logger()->error("Failed to verify record: {$media["MediaID"]}", [
                        "MediaID" => $media["MediaID"],
                        "Path" => $media["FullPath"],
                        "ForeignID" => $media["ForeignID"],
                        "ForeignTable" => $media["ForeignTable"],
                        "ThumbPath" => $media["ThumbPath"],
                        "Error" => $e->getMessage(),
                        "ErrorCode" => $e->getCode(),
                    ]);
                    continue;
                } finally {
                    sleep(1);
                }
            }
            $offset += $this->batchSize;
            $medias = $this->fetchMediaRecords($offset);
        }

        if ($this->fileStream) {
            fclose($this->fileStream);
        }

        $totalStopTime = microtime(true);
        $time = Timers::formatDuration(($totalStopTime - $totalStartTime) * 1000);
        $this->logger()->success("Files reached: $this->success");
        $this->logger()->warning("Files not reached: $this->failure");
        $this->logger()->success("Total validation time: $time");
        return self::SUCCESS;
    }

    /**
     * Fetch the records from GDN_Media.
     *
     * @param int $offset
     * @return array|null
     * @throws Exception
     */
    protected function fetchMediaRecords(int $offset = 0): ?array
    {
        $sql = $this->getDatabase()->createSql();

        $result = $sql
            ->select(["MediaID", "Name", "Path", "ForeignID", "ForeignTable", "ThumbPath"])
            ->from("Media")
            ->offset($offset)
            ->limit($this->batchSize)
            ->get()
            ->resultArray();

        return $result;
    }

    /**
     * Set the proper domain.
     *
     * @param string $path
     * @return String
     */
    protected function setDomain(string $path): string
    {
        $path = str_replace("s3:/", $this->baseUrl, $path);
        return $path;
    }

    /**
     * Write a row for the csv report.
     *
     * @param $media
     * @param $response
     * @return void
     */
    protected function writeReport($media, $response): void
    {
        $rows = $this->formatRecords($media, $response);
        fputcsv($this->fileStream, $rows);
    }

    /**
     * @param array $media
     * @param string $response
     * @return array
     */
    public function formatRecords(array $media, string $response): array
    {
        $r = [
            "MediaID" => $media["MediaID"],
            "Name" => $media["Name"],
            "Path" => $media["Path"],
            "ForeignID" => $media["ForeignID"],
            "ForeignTable" => $media["ForeignTable"],
            "ResponseCode" => $response,
        ];
        return $r;
    }
}
