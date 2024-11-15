<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli;

require_once PATH_LIBRARY_CORE . "/functions.render.php";

use Garden\Container\Container;
use Garden\Container\Reference;
use Garden\Web\RequestInterface;
use Gdn;
use Gdn_Configuration;
use Gdn_Locale;
use Gdn_Plugin;
use Gdn_Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UserModel;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Bootstrap;
use Vanilla\Cli\Commands;
use Vanilla\Cli\Docker\Service\VanillaVanillaService;
use Vanilla\Cli\Utils\InstallData;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Cli\Utils\SimpleScriptLogger;
use Vanilla\Contracts;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\LocaleInterface;
use Vanilla\Contracts\Web\UASnifferInterface;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbedFilter;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\EmbeddedContent\LegacyEmbedReplacer;
use Vanilla\Models\Model;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Utility\ContainerUtils;
use Vanilla\Web\UASniffer;
use VanillaHtmlFormatter;
use Symfony\Component\Console;
use VanillaTests\Fixtures\Scheduler\InstantScheduler;

/**
 * Entrypoint for the vanilla-scripts cli.
 */
class VanillaCli extends Console\Application
{
    use ScriptLoggerTrait;

    /** @var Container */
    private Container $container;

    public function __construct()
    {
        parent::__construct("vnla", "2.0");
        // Kludge to prevent printing of the full path.
        $_SERVER["PHP_SELF"] = "vnla";
        $this->container = $this->createContainer();
        $addonManager = $this->container->get(AddonManager::class);
        $addonManager->startAddonsByKey(["dashboard"], Addon::TYPE_ADDON);

        foreach ($this->getCommandClasses() as $commandClass) {
            $this->add($this->container->get($commandClass));
        }
        $this->addPlaceholderCommands();
    }

    /**
     * @return array<class-string<Console\Command\Command>>
     */
    protected function getCommandClasses(): array
    {
        return [
            Commands\BuildCommand::class,
            Commands\StorybookCommand::class,
            Commands\VanillaCacheCommand::class,
            Commands\InstallCommand::class,
            Commands\SplitTestsCommand::class,
            Commands\IndexMentionsCommand::class,
            Commands\BackportCommand::class,
            Commands\DockerCommand::class,
            Commands\DockerBuildCommand::class,
            Commands\ValidateAttachmentsCommand::class,
            Commands\SyncFontsCommand::class,
            Commands\RecalculatePoints::class,
            Commands\ConvertToRich::class,
            Commands\ReparseRich2Quotes::class,
            Commands\SpawnSiteCommand::class,
            Commands\ValidateDataCommand::class,
            Commands\GenerateDataCommand::class,
        ];
    }

    /**
     * @return void
     */
    protected function addPlaceholderCommands(): void
    {
        $this->add(
            new Commands\PlaceholderCommand(
                "composer",
                "Execute a composer command.",
                new Console\Input\InputDefinition([])
            )
        );
        $this->add(
            new Commands\PlaceholderCommand("php", "Execute a php command.", new Console\Input\InputDefinition([]))
        );
    }

    /**
     * @return Container
     */
    protected function createContainer(): Container
    {
        $container = new Container();
        Gdn::setContainer($container);
        Bootstrap::configureContainer($container);

        $container->setInstance(Container::class, $container);

        // Config
        $container
            ->rule(ConfigurationInterface::class)
            ->setClass(Gdn_Configuration::class)
            ->setShared(true)
            ->addCall("autoSave", [false])
            ->addCall("defaultPath", [PATH_ROOT . "/conf/config-defaults.php"])
            ->addCall("load", [PATH_ROOT . "/conf/config-defaults.php", "Configuration", true])
            ->addAlias("Config")
            ->addAlias(Gdn_Configuration::class);

        // Scheduler
        $container->rule(SchedulerInterface::class)->setClass(InstantScheduler::class);

        // Use an in memory cache.
        $container
            ->rule(\Gdn_Cache::class)
            ->setClass(\Gdn_Dirtycache::class)
            ->setShared(true)
            ->setFactory(function () {
                return new \Gdn_Dirtycache();
            });

        // Logging
        $container
            ->rule(LoggerAwareInterface::class)
            ->addCall("setLogger")
            ->rule(LoggerInterface::class);
        $container->rule(LoggerInterface::class)->setClass(SimpleScriptLogger::class);
        $container
            ->setClass(SimpleScriptLogger::class)
            ->rule(\Vanilla\Utility\Timers::class)
            ->setShared(true);

        // Cache
        $container
            ->rule(\Vanilla\Web\Asset\DeploymentCacheBuster::class)
            ->setShared(true)
            ->setConstructorArgs([
                "deploymentTime" => ContainerUtils::config("Garden.Deployed"),
            ])
            ->rule(\Gdn_Dirtycache::class)
            ->setShared(true)
            ->addAlias(\Gdn_Cache::class);

        // EventManager
        $container
            ->rule(\Garden\EventManager::class)
            ->addAlias(\Vanilla\Contracts\Addons\EventListenerConfigInterface::class)
            ->addAlias(\Psr\EventDispatcher\EventDispatcherInterface::class)
            ->addAlias(\Psr\EventDispatcher\ListenerProviderInterface::class)
            ->addCall("addListenerMethod", [\Vanilla\Logging\ResourceEventLogger::class, "logResourceEvent"])
            ->setShared(true);

        // Locale
        $container
            ->rule(\Gdn_Locale::class)
            ->setShared(true)
            ->setConstructorArgs([new Reference(["Gdn_Configuration", "Garden.Locale"])])
            ->addAlias(Gdn::AliasLocale)
            ->addAlias(LocaleInterface::class)
            ->rule(Contracts\LocaleInterface::class)
            ->setAliasOf(Gdn_Locale::class)
            ->setShared(true);

        // Request
        $container
            ->rule("@baseUrl")
            ->setFactory(function (Gdn_Request $request) {
                return $request->getSimpleUrl("");
            })
            ->rule(\Vanilla\Web\SystemTokenUtils::class)
            ->setConstructorArgs([ContainerUtils::config("Context.Secret", "")])
            ->setShared(true)
            ->rule(\Gdn_Request::class)
            ->setShared(true)
            ->addAlias("Request")
            ->addAlias(RequestInterface::class)
            ->rule(UASnifferInterface::class)
            ->setClass(UASniffer::class);

        //Database
        $container
            ->rule("Gdn_SQLDriver")
            ->setClass("Gdn_MySQLDriver")
            ->setShared(true)
            ->addAlias("Gdn_MySQLDriver")
            ->addAlias("MySQLDriver")
            ->addAlias(Gdn::AliasSqlDriver);

        $container
            ->rule("Gdn_Session")
            ->setShared(true)
            ->addAlias("Session");

        // PluginManager
        $container
            ->rule("Gdn_PluginManager")
            ->setShared(true)
            ->addAlias("PluginManager");

        // Model
        $container
            ->rule("Gdn_Model")
            ->setShared(true)
            ->rule(Model::class)
            ->setShared(true)
            ->rule(UserModel::class)
            ->setClass(UserModel::class)
            ->addAlias(Contracts\Models\UserProviderInterface::class);

        $container
            ->rule(Gdn_Plugin::class)
            ->setShared(true)
            ->addCall("setAddonFromManager");

        // Formatter
        $container
            ->rule("BBCodeFormatter")
            ->setClass("BBCode")
            ->setShared(true)
            ->rule("HtmlFormatter")
            ->setClass(VanillaHtmlFormatter::class)
            ->setShared(true)
            ->rule(\Vanilla\Formatting\Quill\Renderer::class)
            ->setShared(true)
            ->rule(\Vanilla\Formatting\Quill\Parser::class)
            ->addCall("addCoreBlotsAndFormats")
            ->setShared(true)
            ->rule(LegacyEmbedReplacer::class)
            ->setShared(true)
            ->rule(\Vanilla\Formatting\FormatService::class)
            ->addAlias("formatService")
            ->addCall("registerBuiltInFormats")
            ->setInherit(true)
            ->setShared(true);

        $container->setInstance(LoggerInterface::class, new SimpleScriptLogger());
        return $container;
    }

    /**
     * Override to grab verbosity settings.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        parent::configureIO($input, $output);
        if ($output->isVerbose()) {
            SimpleScriptLogger::$isVerbose = true;
        }
    }
}
