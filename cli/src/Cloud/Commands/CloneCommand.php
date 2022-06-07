<?php
/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Cloud\Commands;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\Cli\Utils\ShellException;
use Vanilla\Cli\Utils\ShellUtils;
use Vanilla\Cli\Utils\SimpleScriptLogger;
use Vanilla\Cli\Cloud\Utils\VanillaConsole;

/**
 * Clone command.
 */
class CloneCommand
{
    /** @var boolean */
    private $auto;

    /** @var SimpleScriptLogger */
    private $logger;

    /** @var bool */
    private $hasHub;

    /** @var bool */
    private $skipVersionCheck;

    /** @var VanillaConsole  */
    private $vc;

    /** @var string */
    private $email;

    /** @var string */
    private $password;

    /** @var int */
    private $siteID;

    /**
     * These are plugins that should be ignored when detecting plugins.
     * @var array
     */
    protected static $ignoredPlugins = [
        // Internal vanilla tools.
        "vfspoof",
        "vfsupport",
        "customdomain", // Ops tool
        "privatecommunity", // Now part of core
    ];

    /**
     * These are themes that should be ignored when detecting themes.
     * @var array
     */
    protected static $ignoredThemes = [];

    /**
     * These are repositories that should not be pulled.
     * @var string[]
     */
    protected static $ignoredRepos = ["vanilla-cloud"];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->logger = new SimpleScriptLogger();
        $this->vc = new VanillaConsole($this->logger);
    }

    /**
     * The email of the vanilla console user.
     *
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * The password of the vanilla console user.
     *
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * The site id of the site to copy.
     *
     * @param int $siteID
     */
    public function setSiteID(int $siteID): void
    {
        $this->siteID = $siteID;
    }

    /**
     * Skips the version check.
     *
     * @param bool $skipVersionCheck
     */
    public function setSkipVersionCheck(bool $skipVersionCheck): void
    {
        $this->skipVersionCheck = $skipVersionCheck;
    }

    /**
     * Symlinks missing addons automatically.
     *
     * @param bool $auto
     */
    public function setAuto(bool $auto): void
    {
        $this->auto = $auto;
    }

    /**
     * This is the schema of all settings that should be imported from the site.
     *
     * @return Schema
     */
    public function importSettingsSchema(): Schema
    {
        return Schema::parse([
            "EnabledPlugins?",
            "Garden?" => Schema::parse([
                // Basic site info
                "Title?",
                "Description?",
                // Themes
                "Theme?",
                "Themes?" => Schema::parse(["Visible?"]),
                "MobileTheme?",
                "CurrentTheme?",
                "HomepageTitle?",
                // Formatter
                "InputFormatter?",
                // Advanced site info
                "OrgName?",
                "Profile?",
                "Logo?",
                "MobileLogo?",
                "MobileAddressBarColor?",
                "OrgName?",
                "Format?",
                "EmojiSet?",
                "Thumbnail?",
            ]),
        ]);
    }

    /**
     * Clone a site and all it's configuration using vanilla console.
     *
     * @throws ValidationException Throws an exception if the configuration is invalid.
     * @throws ShellException Throws an exception is a command fails.
     */
    public function clone()
    {
        $this->hubCheck();

        $this->vcLogin();

        $fetchedConfig = $this->fetchConfig();

        $config = $this->importConfig($fetchedConfig);

        if (!$this->skipVersionCheck) {
            $this->checkVersion($fetchedConfig);
        }
        $this->symlinkPlugins($config);
        $this->symlinkThemes($config);

        $filename = PATH_CONF . "/$this->siteID.vanilla.localhost.php";
        $this->saveConfig($config, $filename);

        $this->logger->title("🚀 Liftoff");
        $url = "https://$this->siteID.vanilla.localhost/";
        $this->logger->success("Deployed successfully at $url (Cmd+click to open)");
        exit();
    }

    /**
     * Checks if the hub CLI is available
     */
    public function hubCheck()
    {
        $this->logger->title("Checking hub CLI availability");
        exec("hub --version", $output, $resultCode);
        if ($this->hasHub = $resultCode === 0 && count($output) > 1) {
            $this->logger->success("Hub CLI is available!");
        } else {
            $this->logger->warning("Hub CLI is not installed, some features will not be available");
        }
    }

    /**
     * Check if we are logged into vanilla console and prompt credentials if needed
     */
    public function vcLogin()
    {
        $this->logger->title("Checking vanilla console credentials");
        if ($this->vc->checkLoggedIn()) {
            $this->logger->success("Logged in!");
        } else {
            if (!$this->email || !$this->password) {
                $this->logger->info("Please enter your vanilla console credentials");
                $this->email = ShellUtils::promptString("Email: ");
                $this->password = ShellUtils::promptPassword("Password: ");
                $this->logger->info("\n");
            }
            $this->logger->info("Logging in...");
            if (!$this->vc->login($this->email, $this->password)) {
                $this->logger->error("Exiting.");
                exit();
            }
        }
    }

    /**
     * Import config array into a Gdn_Configuration object
     *
     * @return mixed
     */
    public function fetchConfig()
    {
        $this->logger->title("Fetching site config");
        if (!$this->siteID) {
            $matches = ShellUtils::promptPreg("Please enter the site ID: (site ID or vanilla console URL) ", "/\d{7}/");
            $this->siteID = $matches[0];
        }
        $fetchedConfig = $this->vc->getConfig($this->siteID);
        $this->logger->success("Successfully parsed config for site $this->siteID.");
        return $fetchedConfig;
    }

    /**
     * Check the installed version of vanilla against the site's version
     *
     * @param array $config
     * @throws ShellException Throws an exception if a command fails.
     */
    public function checkVersion(array $config)
    {
        $this->logger->title("Site version");
        $siteVer = $config["Garden"]["Version"];
        ShellUtils::command("git fetch");
        $curBranch = ShellUtils::command("git branch --show-current")[0];
        if (str_ends_with($siteVer, $curBranch)) {
            $this->logger->success("Site version matches current branch.");
            ShellUtils::command("git pull");
        } else {
            $this->logger->warning("Fetched site is on version $siteVer. Your current branch is $curBranch");
            $branchName = "origin/release/$siteVer";
            if (ShellUtils::promptYesNo("Would you like to check out $branchName?")) {
                ShellUtils::command("git checkout \"$branchName\"", [], "Could not checkout branch $curBranch");
                ShellUtils::command("git pull");
            } else {
                return;
            }
        }
    }

    /**
     * Import config array into a Gdn_Configuration object
     *
     * @param mixed $configArray
     * @return \Gdn_Configuration
     * @throws ValidationException Throws an exception if the schema is invalid.
     */
    public function importConfig($configArray): \Gdn_Configuration
    {
        $this->logger->title("Importing config file");
        $config = new \Gdn_Configuration();
        $config->load(PATH_CONF . "/config-defaults.php");
        $this->logger->info("Applying import schema");
        try {
            $whitelistedConfig = $this->importSettingsSchema()->validate($configArray);
            $this->logger->info("Importing config");
            $config->loadArray($whitelistedConfig, "imported");
            //$this->logger->warning("Enabling debug flag");
            //$config->set("Debug", true);
            return $config;
        } catch (ValidationException $exception) {
            $this->logger->error($exception->getMessage());
            $this->logger->error("Configuration is invalid. Exiting.");
            exit();
        }
    }

    /**
     * Save config file
     *
     * @param mixed $config
     * @param string $filename
     */
    public function saveConfig($config, string $filename)
    {
        $this->logger->title("Saving config file");
        $this->logger->info("Checking if file exists");
        if (file_exists($filename)) {
            ShellUtils::promptYesNo("File $filename already exists. Replace?", true);
            $this->logger->info("Deleting existing file");
            unlink($filename);
        }
        $handle = fopen($filename, "x");
        fclose($handle);
        $this->logger->info("Saving to $filename");
        $config->save($filename);
    }

    /**
     * Check that all plugins are correctly symlinked
     *
     * @param \Gdn_Configuration $config
     * @throws ShellException Throws an exception if a command fails.
     */
    public function symlinkPlugins(\Gdn_Configuration $config)
    {
        $this->logger->title("Checking enabled plugins");
        // Get enabled plugins.
        $enabledPlugins = array_map(
            "strtolower",
            array_keys(
                array_filter($config->get("EnabledPlugins"), function ($v) {
                    return $v; // True when the plugin is enabled.
                })
            )
        );
        // Get available plugins.
        $existingPlugins = array_map("strtolower", scandir("plugins"));
        if (!$existingPlugins) {
            $this->logger->error("You must run this command from vanilla root directory. Exiting.");
            exit();
        }
        // Check which plugins are missing by subtracting existing from and enabled plugins.
        $missingPlugins = array_diff($enabledPlugins, $existingPlugins, self::$ignoredPlugins);
        // Prompt the user for missing plugins.
        $this->resolveMissingPlugin($missingPlugins, "plugins");
    }

    /**
     * Check that all themes are correctly symlinked
     *
     * @param \Gdn_Configuration $config
     * @throws ShellException Throws an exception if a command fails.
     */
    public function symlinkThemes(\Gdn_Configuration $config)
    {
        $this->logger->title("Checking theme");
        // Get visible themes.
        $requiredThemes = explode(",", $config->get("Garden.Themes.Visible"));
        // Add the current theme if it is not in visible themes.
        $currentTheme = $config->get("Garden.Theme");
        if ($currentTheme && !in_array($currentTheme, $requiredThemes)) {
            $requiredThemes[] = $currentTheme;
        }
        // Add the mobile theme if it is not in visible themes.
        $mobileTheme = $config->get("Garden.MobileTheme");
        if ($mobileTheme && !in_array($mobileTheme, $requiredThemes)) {
            $requiredThemes[] = $mobileTheme;
        }
        // Lowercase required themes.
        $requiredThemes = array_map("strtolower", $requiredThemes);
        // Get available themes.
        $existingThemes = array_map("strtolower", array_merge(scandir("themes"), scandir("addons/themes")));
        if (!$existingThemes) {
            $this->logger->error("You must run this command from vanilla root directory. Exiting.");
            exit();
        }
        // Check which themes are missing by subtracting existing from and enabled themes.
        $missingThemes = array_diff($requiredThemes, $existingThemes, self::$ignoredThemes);
        // Prompt the user for missing themes.
        $this->resolveMissingPlugin($missingThemes, "themes");
    }

    /**
     * Try to resolve missing plugins one by one.
     *
     * @param array $missingPlugins List of plugins names to resolve
     * @param string $type Either "plugin" or "theme"
     * @throws ShellException Throws an exception if a command fails.
     */
    public function resolveMissingPlugin(array $missingPlugins, string $type)
    {
        $missingPluginsCount = count($missingPlugins);
        if ($missingPluginsCount > 0) {
            $this->logger->warning("$missingPluginsCount missing $type");
        } else {
            $this->logger->success("No missing $type!");
        }
        $i = 0;
        foreach ($missingPlugins as $pluginName) {
            $i++;
            $this->logger->warning("\n($i/$missingPluginsCount) $pluginName is missing");
            $symlinkablePlugins = $this->findSymlinkablePlugins($pluginName, $type);
            // If there are no symlinkable plugins, try to look for repos we can clone.
            if (
                count($symlinkablePlugins) === 0 &&
                $this->findRepositories($pluginName, $type, ["vanilla", "vanillaforums"])
            ) {
                // We should look for plugins to symlink again.
                $symlinkablePlugins = $this->findSymlinkablePlugins($pluginName, $type);
            }
            if ($this->auto) {
                // Symlink first plugin automatically
                if (count($symlinkablePlugins) > 0) {
                    $this->symlinkPlugin($symlinkablePlugins[0]);
                }
                continue;
            }
            $choices = [];
            foreach ($symlinkablePlugins as $index => $path) {
                $choices[$index] = "symlink $path";
            }
            $choices["s"] = "skip this $type";
            $choices["x"] = "exit the script";
            $choices["a"] = "automatically resolve all (same as --auto)";
            $choice = ShellUtils::promptChoices("Select a command:", $choices);
            if ($choice === "s") {
                // Skip.
                continue;
            } elseif ($choice === "x") {
                // Exit.
                exit();
            } elseif ($choice === "a") {
                // Automatically symlink.
                $this->setAuto(true);
                if (count($symlinkablePlugins) > 0) {
                    $this->symlinkPlugin($symlinkablePlugins[0]);
                }
            } else {
                // Symlink index $choice.
                $this->symlinkPlugin($symlinkablePlugins[$choice]);
            }
        }
    }

    /**
     * Symlinks a plugin using it's source path
     *
     * @param string $path The path of a plugin to symlink, such as "../addons/plugins/AuthorSelector"
     * @throws ShellException Throws an exception if a command fails.
     */
    public function symlinkPlugin(string $path)
    {
        // Get the target path.
        $parts = explode("/", $path);
        $dir = $parts[count($parts) - 2];
        $plugin = $parts[count($parts) - 1];
        $target = "$dir/$plugin";
        // Symlink the path.
        $this->logger->info("Symlinking  $path => $target");
        ShellUtils::command("(cd \"$dir\" && ln -s \"../$path\")");
        // Check the symlink
        if (is_dir($target)) {
            $this->logger->success("Success");
        } else {
            $this->logger->error("Failed");
            exit();
        }
    }

    /**
     * Finds repositories containing a missing plugin.
     *
     * @param string $pluginName Name of the plugin to resolve.
     * @param string $type Either "plugin" or "theme"
     * @param array $orgs Organizations to look into
     * @return bool
     * @throws ShellException Throws an exception if a command fails.
     */
    public function findRepositories(string $pluginName, string $type, array $orgs): bool
    {
        $path = "$type/$pluginName";
        foreach ($orgs as $org) {
            $result = ShellUtils::command("hub api https://api.github.com/search/code\?q\=$path+in:path+org:$org")[0];
            $json = json_decode($result);
            // Get all found repositories.
            $repositories = array_filter($json->items, function ($item) use ($path) {
                return str_contains($item->path, $path);
            });
            // Get the unique names of the repos.
            $repositoryNames = array_unique(
                array_map(function ($item) {
                    return $item->repository->full_name;
                }, $repositories)
            );
            // Exclude ignored repos.
            $repositoryNames = array_diff($repositoryNames, self::$ignoredRepos);
            // Prompt pulling the first found repo.
            if (count($repositoryNames) > 0) {
                if (
                    $this->auto ||
                    ShellUtils::promptYesNo(
                        "$path was found in $repositoryNames[0].\nWould you like to clone $repositoryNames[0]?"
                    )
                ) {
                    ShellUtils::command("(cd .. && git clone git@github.com:$repositoryNames[0].git)");
                    $this->logger->success("Cloned repo successfully.\n");
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Finds a plugin in the workspace and list them.
     *
     * @param string $pluginName Name of the plugin to resolve
     * @param string $type Either "plugins" or "themes".
     * @return array|false
     */
    public function findSymlinkablePlugins(string $pluginName, string $type): array
    {
        return glob("../**/$type/$pluginName", GLOB_ONLYDIR);
    }
}
