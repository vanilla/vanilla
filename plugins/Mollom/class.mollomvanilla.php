<?php if (!defined('APPLICATION')) exit();

/**
 * Class MollomVanilla
 *
 * Implements abstract methods in class Mollom.
 */
class MollomVanilla extends Mollom {

   public function loadConfiguration($name) {
      return C('Plugins.Mollom.'.$name, NULL);
   }

   public function saveConfiguration($name, $value) {
      SaveToConfig('Plugins.Mollom.'.$name, $value);
   }

   public function deleteConfiguration($name) {
      RemoveFromConfig('Plugins.Mollom.'.$name);
   }

   public function getClientInformation() {
      $PluginInfo = Gdn::PluginManager()->AvailablePlugins();
      return array(
         'platformName' => 'Vanilla',
         'platformVersion' => APPLICATION_VERSION,
         'clientName' => 'Mollom Vanilla',
         'clientVersion' => $PluginInfo['Mollom']['Version']
      );
   }

   protected function request($method, $server, $path, $query = NULL, array $headers = array()) {
      $Request = new ProxyRequest();
      $Request->Request(
         array('Method' => $method,
            'URL' => trim($server,'/').trim($path,'/'),
            //'Debug' => TRUE
         ),
         $query,
         NULL,
         $headers
      );

      $MollomResponse = (object) array(
         'code' => $Request->ResponseStatus,
         'headers' => $Request->ResponseHeaders,
         'body' => $Request->ResponseBody,
      );
      return $MollomResponse;
   }
}
