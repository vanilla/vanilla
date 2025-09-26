<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Symfony\Component\Process\Process;
use Vanilla\Setup\ComposerHelper;

/**
 * Service for dev.vanilla.local
 */
class VanillaVanillaService extends AbstractService
{
    const SERVICE_ID = "vanilla";

    public static array $requiredServiceIDs = [VanillaMySqlService::SERVICE_ID];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            new ServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "Vanilla",
                containerName: "vanilla-cloud-php",
                url: $this->getUrls()
            )
        );
    }

    /**
     * Get all of our localhost site urls based of the configurations we have.
     *
     * @return array
     */
    private function getUrls(): array
    {
        $urls = [];
        $confPaths = [...glob(PATH_CONF . "/**/*.php"), ...glob(PATH_CONF . "/*.php")];
        foreach ($confPaths as $confPath) {
            $baseUrl = self::baseUrlForConfigPath($confPath);
            if ($baseUrl === null) {
                continue;
            }
            $urls[] = $baseUrl;
        }
        return $urls;
    }

    /**
     * Get a base url for a config path if possible.
     *
     * @param string $config_path
     * @return string|null
     */
    private function baseUrlForConfigPath(string $config_path): string|null
    {
        if (preg_match("/\\/config.php$/", $config_path)) {
            return "https://dev.vanilla.local";
        } elseif (preg_match("/\\/vanilla.local\\/(.*)\\.php$/", $config_path, $matches)) {
            $node_name = $matches[1];
            return "https://vanilla.local/$node_name";
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.yml",
            "WWWGROUP" => getmygid(),
            "WWWUSER" => getmyuid(),
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getVanillaConfigDefaults(): array
    {
        return [
            // Typically captcha isn't configured on localhost.
            "Garden.Registration.SkipCaptcha" => true,
            "EnabledPlugins.themingapi" => true,

            // Bunch of feature flags to enable.
            "Feature.CommunityManagementBeta.Enabled" => true,
            "Feature.customLayout.discussionList.Enabled" => true,
            "Feature.customLayout.home.Enabled" => true,
            "Feature.customLayout.categoryList.Enabled" => true,
            "Feature.customLayout.post.Enabled" => true,
            "Feature.layoutEditor.discussionThread.Enabled" => true,
            "Feature.escalations.Enabled" => true,
            "Feature.CustomProfileFields.Enabled" => true,
            "Feature.discussionSiteMaps.Enabled" => true,
            "Feature.NewUserManagement.Enabled" => true,
            "Feature.Digest.Enabled" => true,
            "Feature.UnsubscribeLink.Enabled" => true,
            "Feature.widgetBuilder.Enabled" => true,
            "Agent.Configuration" => "/srv/vanilla-repositories/vanilla/docker/agent.stub.json",
        ];
    }

    /**
     * @inheritdoc
     */
    public function start(): void
    {
        // Clear our caches while we're at it.
        ComposerHelper::clearPhpCache();
        ComposerHelper::clearTwigCache();
        ComposerHelper::clearJsDepsCache();

        // Make sure we have all our symlinks
        $this->ensureSymlinks();

        parent::start();
    }

    /**
     * Ensure our config bootstrapping classes are symlinked.
     */
    private function ensureSymlinks(): void
    {
        $bootstrapBeforePath = PATH_CONF . "/bootstrap.before.php";
        $bootstrapEarlyPath = PATH_CONF . "/bootstrap.early.php";
        $this->forceSymlink("../docker/bootstrap.before.php", $bootstrapBeforePath);
        $this->forceSymlink("../docker/bootstrap.docker.php", $bootstrapEarlyPath);

        $cloudLinkScript = PATH_ROOT . "/cloud/scripts/symlink-addons";
        if (file_exists($cloudLinkScript)) {
            // Make sure our cloud addons are all symlinked.
            $process = new Process([$cloudLinkScript], PATH_ROOT);
            $process->mustRun();
        }
        $this->logger()->debug("Symlinks created");
    }

    /**
     * Create a symlink `ln -sf`
     *
     * @param string $symlinkTarget
     * @param string $symlinkFile
     */
    private function forceSymlink(string $symlinkTarget, string $symlinkFile): void
    {
        if (file_exists($symlinkFile)) {
            unlink($symlinkFile);
        }
        $process = new Process(["ln", "-sf", $symlinkTarget, $symlinkFile], PATH_ROOT);
        $process->mustRun();
    }
}
