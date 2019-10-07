<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Setup;

use Composer\Script\Event;
use Composer\Factory;

/**
 * Contains helper methods for Vanilla's composer integration.
 */
class ComposerHelper {

    const NODE_ARGS_ENV = "VANILLA_BUILD_NODE_ARGS";
    const DISABLE_VALIDATION_ENV = "VANILLA_BUILD_DISABLE_CODE_VALIDATION";
    const LOW_MEMORY_ENV = "VANILLA_BUILD_LOW_MEMORY";
    const DISABLE_AUTO_BUILD = "VANILLA_BUILD_DISABLE_AUTO_BUILD";

    /**
     * Clear the addon manager cache.
     */
    private static function clearAddonManagerCache() {
        $cacheDir = realpath(__DIR__.'/../../cache');

        $paths = array_merge(
            [$cacheDir.'/addon.php', $cacheDir.'/openapi.php'],
            glob($cacheDir.'/locale/*.php'),
            glob($cacheDir.'/theme/*.php'),
            glob($cacheDir.'/*-index.php')
        );
        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Clear the twig cache.
     */
    private static function clearTwigCache() {
        $cacheDir = realpath(__DIR__.'/../../cache');

        // Clear twig cache if it exists.
        $twigCache = $cacheDir . '/twig';
        if (file_exists($twigCache)) {
            self::deleteRecursively($twigCache);
        }

        // Due to a previous bug, the twig cache may have lived in the conf directory.
        if (file_exists($twigCache)) {
            self::deleteRecursively($twigCache);
        }
    }

    /**
     * Recursively delete a directory.
     *
     * @param string $root
     */
    private static function deleteRecursively(string $root) {
        $dir_iterator = new \RecursiveDirectoryIterator($root);
        $iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
            unlink($file);
        }
    }

    /**
     * Trigger builds of frontend assets after a composer install.
     *
     * - Installs node_modules
     * - Builds frontend assets
     *
     * There are some environmental variables that can alter the way this command run.
     *
     * - VANILLA_BUILD_NODE_ARGS - Arguments to pass to the node process.
     * "--max-old-space-size" in particular can be used to set a memory limit.
     * - VANILLA_BUILD_DISABLE_CODE_VALIDATION - Disables type-checking and linting. This speeds up the build and
     * reduces memory usage.
     * - VANILLA_BUILD_DISABLE_AUTO_BUILD - Prevent the build from running on composer install.
     */
    public static function postUpdate() {
        $skipBuild = getenv(self::DISABLE_AUTO_BUILD) ? true : false;
        if ($skipBuild) {
            printf("\nSkipping automatic JS build because " . self::DISABLE_AUTO_BUILD . " env variable is set.\n");
            return;
        }

        printf("\nInstalling core node_modules\n");
        passthru('yarn install --pure-lockfile', $installReturn);

        if ($installReturn !== 0) {
            printf("Installing core node_modules failed\n");
            exit($installReturn);
        }

        $vanillaRoot = realpath(__DIR__ . "/../../");
        $buildScript = realpath($vanillaRoot . "/build/scripts/build.ts");
        $tsNodeRegister = realpath($vanillaRoot . "/node_modules/ts-node/register");
        $tsConfig = realpath($vanillaRoot . "/build/tsconfig.json");


        // Build bootstrap can be used to configure this build if env variables are not available.
        $buildBootstrap = realpath($vanillaRoot . "/conf/build-bootstrap.php");
        if (file_exists($buildBootstrap)) {
            include $buildBootstrap;
        }

        $nodeArgs = getenv(self::NODE_ARGS_ENV) ?: "";

        // The disable validation flag was used to enable low memory optimizations.
        // The build no longer does any validation, however, so a new env variable has been added.
        // So, we check for both.
        $lowMemoryFlag = getenv(self::DISABLE_VALIDATION_ENV) || getenv(self::LOW_MEMORY_ENV) ? "--low-memory" : "";
        $buildCommand = "TS_NODE_PROJECT=$tsConfig node $nodeArgs -r $tsNodeRegister $buildScript -i $lowMemoryFlag";

        printf("\nBuilding frontend assets\n");
        printf("\n$buildCommand\n");
        system($buildCommand, $buildResult);

        if ($buildResult !== 0) {
            printf("The build failed with code $buildResult");
            exit($buildResult);
        }
    }

    /**
     * Merge repositories and requirements from a separate composer-local.json.
     *
     * This allows static development dependencies to be shipped with Vanilla, but can be customized with a
     * composer-local.json file that specifies additional dependencies such as plugins or compatibility libraries.
     *
     * @param Event $event The event being fired.
     */
    public static function preUpdate(Event $event) {
        self::clearAddonManagerCache();
        self::clearTwigCache();

        // Check for a composer-local.json.
        $composerLocalPath = './composer-local.json';

        if (!file_exists($composerLocalPath)) {
            return;
        }

        $composer = $event->getComposer();
        $factory = new Factory();

        $localComposer = $factory->createComposer(
            $event->getIO(),
            $composerLocalPath,
            true,
            null,
            false
        );

        // Merge repositories.
        $localRepositories = $localComposer->getRepositoryManager()->getRepositories();
        foreach ($localRepositories as $repository) {
            /* @var \Composer\Repository\RepositoryInterface $repository */

            if (method_exists($repository, 'getRepoConfig')) {
                $config = $repository->getRepoConfig();
            } else {
                $config = ['url' => ''];
            }
            // Skip the packagist repo.
            if (strpos($config['url'], 'packagist.org') !== false) {
                continue;
            }
            $composer->getRepositoryManager()->addRepository($repository);
        }

        // Merge requirements.
        $requires = array_merge($composer->getPackage()->getRequires(), $localComposer->getPackage()->getRequires());
        $composer->getPackage()->setRequires($requires);

        $devRequires = array_merge($composer->getPackage()->getDevRequires(), $localComposer->getPackage()->getDevRequires());
        $composer->getPackage()->setDevRequires($devRequires);
    }
}
