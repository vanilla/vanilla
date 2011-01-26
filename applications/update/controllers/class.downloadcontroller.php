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
class DownloadController extends UpdateController {
   
   public function Initialize() {
      parent::Initialize();
      
      $this->DownloadModel = new DownloadModel($this->Update);
   }

   public function Index() {
      $this->Render();
   }
   
   public function Get() {
      $RenderController = 'download';
      
      $RequestType = $this->RequestType();
      switch ($RequestType) {
         case 'ui':
            $this->UpdaterTitle = T('Downloading updates...');
            $this->UpdaterTasks = array(
               'update/download/get'   => $this->UpdaterTitle
            );
            $RenderView = 'get';
            break;
            
         case 'check':
         case 'perform':

            $RenderView = 'blank';
            $RenderController = 'update';

            if ($RequestType == 'perform') {
               // Don't interrupt if another process is already doing this.
               if ($this->Update->Progress('download','get')) {
                  exit();
               }
               
               $Results = $this->DownloadModel->GetAddonArchive('vanilla-core', NULL, TRUE);
               $this->Update->SetMeta('download/message', T('Download complete'));
            }
            
            if ($RequestType == 'check') {
               $ThisAction = $this->Update->GetTask('download','get');
               $this->SetData('Completion', GetValue('Completion',$ThisAction,NULL));
               $this->SetData('Message', $this->Update->GetMeta('download/message'));
               $this->Update->SetMeta('backup/message');
               $this->SetData('Menu', $this->UpdateModule->ToString());
            }
            
            break;
      }
      $this->Render($RenderView,$RenderController);
   }
   
}