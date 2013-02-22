<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * Route Controller
 *
 * @package Dashboard
 */
 
/**
 * Controlling default routes in Garden's MVC dispatcher system.
 *
 * @since 2.0.0
 * @package Dashboard
 */
class RoutesController extends DashboardController {
   /** @var array Models to automatically instantiate. */
   public $Uses = array('Form');
   
   /**
    * Set menu path. Automatically run on every use.
    *
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      parent::Initialize();
      Gdn_Theme::Section('Dashboard');
      if ($this->Menu)
         $this->Menu->HighlightRoute('/dashboard/settings');
   }

   /**
    * Create a route.
    *
    * @since 2.0.0
    * @access public
    */
   public function Add() {
      $this->Permission('Garden.Settings.Manage');
      // Use the edit form with no roleid specified.
      $this->View = 'Edit';
      $this->Edit();
   }
   
   /**
    * Edit a route.
    *
    * @since 2.0.0
    * @access public
    * @param string $RouteIndex Name of route.
    */
   public function Edit($RouteIndex = FALSE) {
      $this->Permission('Garden.Settings.Manage');
      $this->AddSideMenu('dashboard/routes');
      $this->Route = Gdn::Router()->GetRoute($RouteIndex);
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('Route', 'Target', 'Type'));
      
      // Set the model on the form.
      $this->Form->SetModel($ConfigurationModel);
            
      // If seeing the form for the first time...
      if (!$this->Form->AuthenticatedPostBack()) {
      
         // Apply the route info to the form.
         if ($this->Route !== FALSE)
            $this->Form->SetData(array(
               'Route'  => $this->Route['Route'], 
               'Target' => $this->Route['Destination'], 
               'Type'   => $this->Route['Type']
            ));
            
      } else {
         // Define some validation rules for the fields being saved
         $ConfigurationModel->Validation->ApplyRule('Route', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Target', 'Required');
         $ConfigurationModel->Validation->ApplyRule('Type', 'Required');
         
         // Validate & Save
         $FormPostValues = $this->Form->FormValues();
         
         // Dunno.
         if ($this->Route['Reserved'])
            $FormPostValues['Route'] = $this->Route['Route'];
            
         if ($ConfigurationModel->Validate($FormPostValues)) {
            $NewRouteName = ArrayValue('Route', $FormPostValues);

            if ($this->Route !== FALSE && $NewRouteName != $this->Route['Route'])
               Gdn::Router()->DeleteRoute($this->Route['Route']);
         
            Gdn::Router()->SetRoute(
               $NewRouteName,
               ArrayValue('Target', $FormPostValues),
               ArrayValue('Type', $FormPostValues)
            );

            $this->InformMessage(T("The route was saved successfully."));
            $this->RedirectUrl = Url('dashboard/routes');
         } else {
            $this->Form->SetValidationResults($ConfigurationModel->ValidationResults());
         }
      }
      
      $this->Render();
   }
   
   /**
    * Remove a route.
    *
    * @since 2.0.0
    * @access public
    * @param mixed $RouteIndex Name of route.
    * @param string $TransientKey Security token.
    */
   public function Delete($RouteIndex = FALSE, $TransientKey = FALSE) {
      $this->Permission('Garden.Settings.Manage');
      $this->DeliveryType(DELIVERY_TYPE_BOOL);
      $Session = Gdn::Session();
      
      // If seeing the form for the first time...
      if ($TransientKey !== FALSE && $Session->ValidateTransientKey($TransientKey))
         Gdn::Router()->DeleteRoute($RouteIndex);
      
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect('dashboard/routes');

      $this->Render();      
   }
   
   /**
    * Show list of current routes.
    *
    * @since 2.0.0
    * @access public
    */
   public function Index() {
      $this->Permission('Garden.Settings.Manage');
      $this->AddSideMenu('dashboard/routes');
      $this->AddJsFile('routes.js');
      $this->Title(T('Routes'));
      
      $this->MyRoutes = Gdn::Router()->Routes;
      $this->Render();
   }
  
}