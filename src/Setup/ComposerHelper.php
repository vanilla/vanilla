<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
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
    * Merge repositories and requirements from a separate composer-local.json.
    *
    * This allows static development dependencies to be shipped with Vanilla, but can be customized with a
    * composer-local.json file that specifies additional dependencies such as plugins or compatibility libraries.
    *
    * @param Event $event The event being fired.
    */
   public static function preUpdate(Event $event) {
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
      $repositories = array_merge($composer->getPackage()->getRepositories(), $localComposer->getPackage()->getRepositories());
      if (method_exists($composer->getPackage(), 'setRepositories')) {
         $composer->getPackage()->setRepositories($repositories);
      }

      // Merge requirements.
      $requires = array_merge($composer->getPackage()->getRequires(), $localComposer->getPackage()->getRequires());
      $composer->getPackage()->setRequires($requires);

      $devRequires = array_merge($composer->getPackage()->getDevRequires(), $localComposer->getPackage()->getDevRequires());
      $composer->getPackage()->setDevRequires($devRequires);
   }
}
