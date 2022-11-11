<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli;

use Garden\Cli\Application\CliApplication;
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
use UserModel;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Bootstrap;
use Vanilla\Cli\Commands;
use Vanilla\Cli\Utils\SimpleScriptLogger;
use Vanilla\Contracts;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\LocaleInterface;
use Vanilla\Contracts\Web\UASnifferInterface;
use Vanilla\EmbeddedContent\LegacyEmbedReplacer;
use Vanilla\Models\Model;
use Vanilla\Utility\ContainerUtils;
use Vanilla\Web\UASniffer;
use VanillaHtmlFormatter;

/**
 * Entrypoint for the vanilla-scripts cli.
 */
class VanillaCli extends CliApplication
{
    /**
     * Configure the commands.
     */
    protected function configureCli(): void
    {
        parent::configureCli();

        $this->addMethod(Commands\BackportCommand::class, "backport", [self::OPT_SETTERS => true]);
        $this->addMethod(Commands\InstallCommand::class, "install");
        $this->addMethod(Commands\VanillaCacheCommand::class, "clearCaches", [self::OPT_SETTERS => true]);
        $this->addMethod(Commands\LintCommand::class, "lint", [self::OPT_SETTERS => true]);
        $this->addMethod(Commands\UserMentionIndex\IndexMentionCommand::class, "indexMentions", [
            self::OPT_SETTERS => true,
        ]);

        $addonManager = $this->getContainer()->get(AddonManager::class);
        $addonManager->startAddonsByKey(["dashboard"], Addon::TYPE_ADDON);
    }

    /**
     * @return Container
     */
    protected function createContainer(): Container
    {
        $container = parent::createContainer();
        Gdn::setContainer($container);
        Bootstrap::configureContainer($container);

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

        // Logging
        $container
            ->rule(LoggerAwareInterface::class)
            ->addCall("setLogger")
            ->rule(LoggerInterface::class);
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

        return $container;
    }
}
