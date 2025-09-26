<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license proprietary
 */

namespace Vanilla\Storage\S3;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Exception;
use Garden\Http\HttpClient;
use Garden\Schema\Schema;
use Gdn_Cache;
use Gdn_Configuration;
use Gdn_Upload;
use InvalidArgumentException;
use Vanilla\Site\OwnSite;
use Vanilla\Storage\StorageProviderInterface;

/**
 * S3 Integration
 *
 * This allows files to be uploaded to S3.
 *
 * URL Format:
 *
 *       |        SOURCE        | PREFIX |   PATH    |
 *  s3://<alias | zone>.<bucket>/<siteid>/folder/file
 *
 * "Aliases" are supported for the <region|zone>.<bucket>, allowing more concise URLs.
 *
 */
class S3StorageProvider extends StorageProviderInterface
{
    // Unique identifier for this storage provider
    const STORAGE_TYPE = "awss3";
    protected const SCHEME = "s3";

    /**
     * @var array An array of allowed alias names.
     */
    protected array $aliases = [
        "content" => [
            "zone" => "",
            "prefix" => "",
            "folder" => "uploads",
        ],

        "uploads" => [
            "zone" => "",
            "prefix" => "",
            "folder" => "uploads",
        ],
    ];

    /**
     * @var array An array of known zones
     */
    protected array $zones = [];

    protected Gdn_Configuration $config;

    protected OwnSite $site;

    protected Gdn_Cache $cache;

    protected HttpClient $httpClient;

    protected S3Client $s3Client;

    private string $bucket = "";

    /**
     * S3StorageProvider constructor.
     *
     */
    public function __construct(Gdn_Configuration $config, OwnSite $site, Gdn_Cache $cache)
    {
        $this->config = $config;
        $this->site = $site;
        $this->cache = $cache;
    }

    /**
     * Adds the appropriate url prefixes for the various cloud files.
     * Previously `gdn_upload_getUrls_handler()`
     *
     * @return array
     * @throws Exception
     */
    public function getUrls(): array
    {
        $urls = [];

        // Ensure the URLs array exists
        foreach ($this->aliases as $alias => $aliasData) {
            $type = self::SCHEME . "://{$alias}";

            if (empty($urls[$type])) {
                $destination = $this->parseDestination($alias);

                if (is_array($destination) && array_key_exists("url", $destination)) {
                    $urls[$type] = rtrim(paths("https://{$destination["url"]}", $destination["root"]), "/");
                } else {
                    // Serve file through secondary URL...
                    $urls[$type] = $this->s3Client->getEndpoint() . "/" . $this->bucket;
                }
            }
        }

        return $urls;
    }

    /**
     * Copy a file locally so that it can be manipulated by php.
     * Previously `gdn_upload_copyLocal_handler()`
     *
     * @param array $parsedFile
     * @return string|null The local path to the copied file.
     * @throws Exception Throws an exception if there was a problem copying the file for local use.
     */
    public function copyLocal(array $parsedFile): string|null
    {
        // Ensure this function only processes S3-stored files.
        if ($parsedFile["Type"] !== self::SCHEME) {
            return null;
        }

        $source = val("Domain", $parsedFile);
        $destination = $this->parseDestination($source);

        if (!$destination) {
            return null;
        }

        // Prepend the upload folder to a file for the remote path.
        $remotePath = paths($destination["root"], $parsedFile["Name"]);

        // Define local temporary file path.
        $localPath = paths(PATH_UPLOADS, "cloudtemp", str_replace("/", "-", $remotePath));

        // Ensure the local directory exists.
        if (!file_exists(dirname($localPath))) {
            mkdir(dirname($localPath), 0777, true);
        }

        // Download the file from S3.
        try {
            $this->s3Client->getObject([
                "Bucket" => $destination["bucket"],
                "Key" => $remotePath,
                "SaveAs" => $localPath,
            ]);
            return $localPath; // Store local path in arguments.
        } catch (AwsException $ex) {
            return null; // Indicate failure.
        }
    }

    /**
     * Override file deletes for files stored in S3.
     * Previously `gdn_upload_delete_handler()`
     *
     * @param array $parsedFile The upload arguments. Expected structure:
     * *  [
     * *      "Parsed" => [
     * *          "Type" => "s3", // Storage type, must match self::SCHEME
     * *          "Name" => "path/to/file.jpg", // Remote file path
     * *          "Url" => "http://files-api.vanilla.local/default-bucket/path/to/file.jpg" // Public URL
     * *      ]
     * *  ]
     * @throws Exception Throws an exception if there was a problem deleting the file.
     */
    public function delete(array $parsedFile): void
    {
        // Ensure this file is stored in MinIO (S3-compatible)
        if ($parsedFile["Type"] !== self::SCHEME) {
            return;
        }

        // Extract storage details
        $fileKey = $parsedFile["Name"] ?? null;
        if (!$fileKey) {
            throw new Exception("Missing file key for deletion.", 400);
        }

        try {
            // Delete the file from MinIO
            $this->s3Client->deleteObject([
                "Bucket" => $this->bucket,
                "Key" => $fileKey,
            ]);

            // **Cloudflare Cache Invalidation** (Optional)
            $zoneID = $this->config->get("Cloudflare.S3StorageProvider.ZoneID");
            $token = $this->config->get("Cloudflare.S3StorageProvider.Token");
            $url = $parsedFile["Url"] ?? false;

            if ($zoneID && $token && $url) {
                $this->httpClient->post(
                    "https://api.cloudflare.com/client/v4/zones/$zoneID/purge_cache",
                    [
                        "files" => [$url],
                    ],
                    [
                        "Content-Type" => "application/json",
                        "Authorization" => "Bearer $token",
                    ]
                );
            }
        } catch (AwsException $ex) {
            if ($ex->getStatusCode() != 404) {
                // Ignore "not found" errors, throw other errors
                throw new Exception("Error deleting file: " . $ex->getMessage(), $ex->getStatusCode());
            }
        }
    }

    /**
     * Override file uploading to save to S3.
     * Previously `gdn_upload_saveAs_handler()`
     *
     * @param string $sourcePath
     * @param string $target
     * @param array $options
     * @return array
     * @throws Exception Throws an exception if there is an error saving the files.
     */
    public function saveAs(string $sourcePath, string $target, array $options = []): array
    {
        //    public function saveAs(array &$args): void
        $parsed = Gdn_Upload::parse($target);

        // Extract values from $args
        $filePath = $sourcePath;
        $fileKey = $parsed["SaveName"] ?? null;
        //        $originalFilename = $args["OriginalFilename"] ?? ($args["Options"]["OriginalFilename"] ?? basename($fileKey));
        $originalFilename = val("OriginalFilename", $options);
        $tempPath = $sourcePath;

        // Get special source from upload arguments
        $source = $options["source"] ?? "";
        if (!array_key_exists($source, $this->aliases)) {
            $source = "uploads";
        }

        // Ensure required values exist
        if (!$sourcePath || !$fileKey) {
            throw new InvalidArgumentException("Missing required file parameters: 'Path' and 'SaveName'.");
        }

        // Detect MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Enforce safe MIME type handling
        // Handle PHP bug for SVGs
        if ($mimetype === "image/svg") {
            $mimetype = "image/svg+xml";
        }
        // Force non-image files to "application/octet-stream" (prevents execution)
        elseif (!$mimetype || substr($mimetype, 0, 6) !== "image/") {
            $mimetype = "application/octet-stream";
        }

        // Upload the file with public-read ACL
        $result = $this->s3Client->putObject([
            "Bucket" => $this->bucket,
            "Key" => $fileKey,
            "SourceFile" => $filePath,
            "ACL" => "public-read",
            "ContentType" => $mimetype,
            "Metadata" => [
                "OriginalFilename" => $originalFilename,
            ],
        ]);

        if ($result) {
            // Construct the final URL
            $finalUrl = self::SCHEME . "://{$source}/{$parsed["Name"]}";

            // Parse the final URL again
            $finalParsed = Gdn_Upload::parse($finalUrl);

            // Remove the temporary file
            @unlink($tempPath);

            // Merge parsed data while keeping existing keys intact
            return array_replace($parsed, $finalParsed);
        } else {
            throw new Exception("There was an error saving the file to S3.", 500);
        }
    }

    /**
     * Parse a $source into its resolved destination
     *
     * @param string $sourceDomain
     *
     * @return array|false
     *
     * @throws Exception
     */
    protected function parseDestination(string $sourceDomain): false|array
    {
        $zone = $sourceDomain;
        $bucket = null;

        if (stristr($sourceDomain, ".") !== false) {
            $domainParts = explode(".", $sourceDomain);
            $zone = $domainParts[0];
            $bucket = $domainParts[1];
        }

        $sourceData = [
            "alias" => $zone,
        ];
        if ($bucket) {
            $sourceData["bucket"] = $bucket;
        }

        // Resolve aliases to zones and buckets
        $resolved = $this->resolveAliases($sourceData);
        if (!array_key_exists("identity", $resolved) || !array_key_exists("bucket", $resolved)) {
            return false;
        }

        $path = "/";

        if (array_key_exists("folder", $resolved)) {
            $path = paths($resolved["folder"], $path);
        }

        if (array_key_exists("prefix", $resolved)) {
            $path = paths($resolved["prefix"], $path);
        }

        $resolved["root"] = $path;

        return $resolved;
    }

    /**
     * Recursive function to parse aliases and inferred data from source URLs
     *
     * @param array $sourceData
     *
     * @return array
     * @throws Exception
     */
    protected function resolveAliases(array $sourceData): array
    {
        $hash = md5(serialize($sourceData));

        if (array_key_exists("alias", $sourceData) && array_key_exists($sourceData["alias"], $this->aliases)) {
            $aliasData = $this->aliases[$sourceData["alias"]];
            unset($sourceData["alias"]);
            $sourceData = array_merge($aliasData, $sourceData);
        }

        if (array_key_exists("zone", $sourceData) && !array_key_exists("identity", $sourceData)) {
            if (array_key_exists($sourceData["zone"], $this->zones)) {
                $zoneData = $this->zones[$sourceData["zone"]];
                $sourceData = array_merge($zoneData, $sourceData);
            }
        }

        if (array_key_exists("prefix", $sourceData)) {
            $sourceData["prefix"] = $this->config->get("S3.Prefix", $this->site->getSiteID() ?? null);
        }

        if (!array_key_exists("url", $sourceData) && isset($sourceData["bucket"])) {
            $sourceData["url"] = "{$this->s3Client->getEndpoint()}/{$sourceData["bucket"]}";
        }

        $new = md5(serialize($sourceData));
        $changed = $new != $hash;
        if ($changed) {
            return $this->resolveAliases($sourceData);
        }

        return $sourceData;
    }

    /**
     * Set the S3 client configuration.
     *
     * @param array $configs
     * If $configs is an empty array, the configuration will be pulled from the config file.
     * $configs should be an array with the following keys:
     * - Endpoint
     * - Region
     * - Credentials["Key"]
     * - Credentials["Secret"]
     * - Prefix
     * - Zone
     *
     * @throws Exception
     */
    public function setConfig(array $configs = []): void
    {
        if (empty($configs)) {
            $configs = $this->config->get("S3");
        }

        // Define schema validation
        $schema = [
            "type" => "object",
            "properties" => [
                "Region" => ["type" => "string"],
                "Credentials" => [
                    "type" => "object",
                    "properties" => [
                        "Key" => ["type" => "string"],
                        "Secret" => ["type" => "string"],
                    ],
                    "required" => ["Key", "Secret"],
                ],
                "Prefix" => ["type" => "string"],
                "Zone" => ["type" => "string"],
                "Endpoint" => ["type" => "string"],
            ],
            "required" => ["Region", "Prefix", "Zone", "Endpoint", "Credentials"],
        ];

        // Validate the schema
        if (!Schema::parse($schema)->isValid($configs)) {
            throw new Exception("Invalid S3 configuration.");
        }

        // Set the bucket value
        $this->bucket = $configs["Prefix"];

        // Initialize aliases from config
        $this->aliases["content"]["zone"] = $configs["Zone"];
        $this->aliases["content"]["prefix"] = $configs["Prefix"];

        $this->aliases["uploads"]["zone"] = $configs["Zone"];
        $this->aliases["uploads"]["prefix"] = $configs["Prefix"];

        // Initialize S3 client
        $this->s3Client = new S3Client([
            "version" => "latest",
            "region" => $configs["Region"],
            "credentials" => [
                "key" => $configs["Credentials"]["Key"],
                "secret" => $configs["Credentials"]["Secret"],
            ],
            "endpoint" => $configs["Endpoint"],
            "use_path_style_endpoint" => true,
            "http" => [
                "verify" => false, // Disable SSL verification if using self-signed certs
            ],
        ]);
    }
}
