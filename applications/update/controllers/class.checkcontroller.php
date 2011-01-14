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
 * Update Controller
 */
class CheckController extends UpdateController {
   
   protected $UpdateStatus;
   
   public function Initialize() {
      parent::Initialize();
   }

   public function Index() {
      $this->UpdateStatus = $this->CheckStatus();
      
      // Too old, cannot update automatically
      if ($this->UpdateStatus === FALSE)
         return $this->TooOld();
         
      // Up to date, don't update
      //if ($VersionDiff >= 0) return $this->NoUpdate();
      
      // Out of date, prompt for update now
      return $this->Prompt();
      
      $this->Render();
   }
   
   public function NoUpdate() {
      $this->View = 'noupdate';
      $this->Render();
   }
   
   public function TooOld() {
      $this->View = 'tooold';
      $this->Render();
   }
   
   public function Prompt() {
      $this->Form = new Gdn_Form();
      
      if ($this->Form->AuthenticatedPostBack()) {
         return $this->Update();
      }
      
      $this->View = 'prompt';
      $this->SetData('UpdateStatus', $this->UpdateStatus);
      $this->SetData('CurrentVersion', APPLICATION_VERSION);
      $this->SetData('LatestVersion', $this->LatestVersion());
      
      $this->Render();
   }
   
   public function Update() {
      $this->Update->Fresh();
      Redirect('update/check');
   }
   
   public function CheckController_Render_Before($Sender) {
      //$Sender->AddModule($Sender->UpdateModule);
   }
   
}