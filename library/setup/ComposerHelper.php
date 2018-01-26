<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Setup;

use Composer\Script\Event;
use Composer\Factory;

/**
 * Contains helper methods for Vanilla's composer integration.
 */
class ComposerHelper {
    /**
     * Clear the addon manager cache.
     */
    private static function clearAddonManagerCache() {
        $cacheDir = realpath(__DIR__.'/../../cache');

        $paths = array_merge(
            [$cacheDir.'/addon.php'],
            glob($cacheDir.'/locale/*.php'),
            glob($cacheDir.'/theme/*.php'),
            glob($cacheDir.'/*-index.php')
        );
        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        static::removeFolder("$cacheDir/ebi");
    }

    /**
     * Remove a folder (and all the sub-folders and files).
     * Taken from http://php.net/manual/en/function.rmdir.php
     *
     * @param string $Dir
     * @return void
     */
    protected static function removeFolder($path) {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path)) {
            unlink($path);
            return;
        }

        $path = rtrim($path, '/').'/';

        // Get all of the files in the directory.
        if ($dh = opendir($path)) {
            while (($file = readdir($dh)) !== false) {
                if (trim($file, '.') == '') {
                    continue;
                }

                $subPath = $path.$file;

                if (is_dir($subPath)) {
                    self::removeFolder($subPath);
                } else {
                    unlink($subPath);
                }
            }
            closedir($dh);
        }
        rmdir($path);
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
