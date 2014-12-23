<?php

/**
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 */

$PluginInfo['Mustache'] = array(
    'Name' => 'Mustache Renderer Support',
    'Description' => "Add mustache view support.",
    'Version' => '1.0',
    'RequiredApplications' => array(
        'Vanilla' => '2.1a'
    ),
    'MobileFriendly' => true,
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com'
);

/**
 * Mustache Render Plugin
 *
 * This plugin adds methods and libraries required for mustache render support.
 *
 * Changes:
 *  1.0     Release
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package addons
 */
class MustachePlugin extends Gdn_Plugin {

    /**
     * Register mustache view handler
     *
     * @param Gdn_Dispatcher $sender
     */
    public function Gdn_Dispatcher_AppStartup_Handler($sender) {
        $pfolder = $this->getPluginFolder(true);

        // Register mustache layer as singleton
        $mustachePath = paths($pfolder, 'library/class.mustache.php');
        Gdn::factoryInstall('Mustache', 'Mustache', $mustachePath, Gdn::FactorySingleton);

        // Register mustache engine
        $enginePath = paths($pfolder, 'library/class.mustache_engine.php');
        Gdn::factoryInstall('Mustache_Engine', 'Mustache_Engine', $enginePath);

        // Register mustache view handler
        Gdn::factoryInstall('ViewHandler.mustache', 'Mustache');
    }

    public function Gdn_Controller__Handler($sender) {

        /**
         * Mustache Files
         *
         * Resolve and add Mustache template files to the output.
         */

        $TemplateFiles = self::ResolveStaticResources($this->_TemplateFiles, 'views', array(
           'StripRoot'    => false
        ));

        if (sizeof($TemplateFiles)) {
           ksort($TemplateFiles);

           $TemplateDeliveryMode = C('Garden.Template.DeliveryMethod', 'defer');
           $ScriptHint = false;

           switch ($TemplateDeliveryMode) {

              // Consolidated asynchronous or Inline synchronous loading serves the template content directly
              case 'consolidate':
              case 'inline':

                 $HashTag = AssetModel::HashTag($TemplateFiles);
                 $TemplateFile = CombinePaths(array(PATH_CACHE, "stache-{$HashTag}.js"));
                 if ($TemplateDeliveryMode == 'inline')
                    $ScriptHint = 'inline';

                 if (!file_exists($TemplateFile)) {
                    $TemplateArchiveContents = array();
                    foreach ($TemplateFiles as $TemplateSrcFile => $TemplateSrcOptions) {
                       $TemplateName = GetValueR('options.name', $TemplateSrcOptions);

                       $TemplateRelativeSrc = str_replace(
                          array(PATH_ROOT, DS),
                          array('', '/'),
                          $TemplateSrcFile
                       );

                       $TemplateArchiveContents[] = array(
                          'Name'      => $TemplateName,
                          'URL'       => Gdn::Request()->Url($TemplateRelativeSrc, '//'),
                          'Contents'  => file_get_contents($TemplateSrcFile),
                          'Type'      => 'inline'
                       );
                    }
                    $TemplateArchiveContents = json_encode($TemplateArchiveContents);

                    $TemplateTempFile = "{$TemplateFile}.tmp";
                    file_put_contents($TemplateTempFile, "gdn.Template.Register({$TemplateArchiveContents});");
                    rename($TemplateTempFile, $TemplateFile);
                 }

                 break;

              // Deferred loading, just registers the templates and allows lazyloading on the client
              case 'defer':

                 $HashTag = AssetModel::HashTag($TemplateFiles);
                 $TemplateFile = CombinePaths(array(PATH_CACHE, "stache-defer-{$HashTag}.js"));
                 $ScriptHint = 'inline';

                 if (!file_exists($TemplateFile)) {
                    $TemplateDeferredContents = array();

                    $TemplateDeferredContents = array();
                    foreach ($TemplateFiles as $TemplateSrcFile => $TemplateSrcOptions) {
                       $TemplateName = GetValueR('options.name', $TemplateSrcOptions);

                       $TemplateRelativeSrc = str_replace(
                          array(PATH_ROOT, DS),
                          array('', '/'),
                          $TemplateSrcFile
                       );

                       $TemplateDeferredContents[] = array(
                          'Name'      => $TemplateName,
                          'URL'       => Gdn::Request()->Url($TemplateRelativeSrc, '//'),
                          'Type'      => 'defer'
                       );
                    }
                    $TemplateDeferredContents = json_encode($TemplateDeferredContents);

                    $TemplateTempFile = "{$TemplateFile}.tmp";
                    file_put_contents($TemplateTempFile, "gdn.Template.Register({$TemplateDeferredContents});");
                    rename($TemplateTempFile, $TemplateFile);
                 }

                 break;
           }

           if ($TemplateFile && file_exists($TemplateFile)) {
              $TemplateSrc = str_replace(
                 array(PATH_ROOT, DS),
                 array('', '/'),
                 $TemplateFile
              );

              $TemplateOptions = array('path' => $TemplateFile);
              if ($ScriptHint == 'inline')
                 $TemplateOptions['hint'] = 'inline';

              $this->Head->AddScript($TemplateSrc, 'text/javascript', $TemplateOptions);
           }

        }
    }

}