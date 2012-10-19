<?php if (!defined('APPLICATION')) exit();

/**
 * Social Controller
 *
 * Manages the social plugins.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @since 2.0.18
 * @package Dashboard
 */

class SocialController extends DashboardController {
   
   public function Initialize() {
      parent::Initialize();
      Gdn_Theme::Section('Dashboard');
   }
   
   public function Index() {
      Redirect('social/manage');
   }
   
   public function Manage() {
      $this->Permission('Garden.Settings.Manage');
      $this->Title("Social Integration");
      $this->AddSideMenu('dashboard/social');
      
      $Connections = $this->GetConnections();
      $this->SetData('Connections', $Connections);
      
      $this->Render();
   }
   
   protected function GetConnections() {
      DebugMethod(__METHOD__);
      $Connections = array();
      
      $this->FireEvent('GetConnections');
      $Connections = $this->Data('Connections');
      
      foreach (Gdn::PluginManager()->AvailablePlugins() as $PluginKey => $PluginInfo) {
         if (!array_key_exists('SocialConnect', $PluginInfo)) continue;
         
         if (!array_key_exists($PluginKey, $Connections))
            $Connections[$PluginKey] = array();
         
         $Connections[$PluginKey] = array_merge(array(
            'Icon'         => sprintf("/plugins/%s/icon.png", $PluginInfo['Folder'])
         ), $Connections[$PluginKey], $PluginInfo, array(
            'Enabled'      => Gdn::PluginManager()->CheckPlugin($PluginKey)
         ));
      }
      
      return $Connections;
   }
   
}