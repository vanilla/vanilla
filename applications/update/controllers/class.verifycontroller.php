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
class VerifyController extends UpdateController {
   
   public function Initialize() {
      parent::Initialize();
   }

   public function Index() {
      $this->Render();
   }
   
   public function Extract() {
      $RenderController = 'verify';
      
      $RequestType = $this->RequestType();
      switch ($RequestType) {
         case 'ui':
            $this->UpdaterTitle = T('Extracting downloaded updates...');
            $this->UpdaterTasks = array(
               'update/verify/extract'   => $this->UpdaterTitle
            );
            $RenderView = 'extract';
            break;
            
         case 'check':
         case 'perform':

            $RenderView = 'blank';
            $RenderController = 'update';

            if ($RequestType == 'perform') {
               // Don't interrupt if another process is already doing this.
               if ($this->Update->Progress('verify','extract')) {
                  exit();
               }
               
               // Unknown task length. Deploy spinner
               $DownloadModel = new DownloadModel();
               $AddonFile = $DownloadModel->GetAddonArchive('vanilla-core');
               
               if ($AddonFile == FALSE) {
                  $this->Fail('Could not get the downloaded archive path. Did the download fail?');
                  break;
               }
               
               $ZipModel = new ZipModel();
               $OpenStatus = $ZipModel->open($AddonFile);
               if (!$OpenStatus === TRUE) {
                  $this->Fail("Could not open the downloaded archive '%s'.", $AddoonFile);
                  break;
               }
               
               $this->Update->Progress('verify', 'extract', 0, TRUE);
               $LastProgress = 0; $NumFiles = $ZipModel->numFiles;
               echo "Files: {$NumFiles}\n";
               for ($i = 0; $i < $NumFiles; $i++) {
                  $FileName = $ZipModel->getNameIndex($i);
                  //$ZipModel->extractTo(,$FileName);
                  $Progress = round((($i+1) / $NumFiles) * 100,2);
                  if (floor($Progress) > floor($LastProgress)) {
                     echo "({$Progress}%) @ {$i} / {$NumFiles}\n";
                     $this->Update->Progress('verify','extract',$Progress,TRUE);
                     $LastProgress = $Progress;
                  }
               }
               exit();
            }
            
            if ($RequestType == 'check') {
               $ThisAction = $this->Update->GetTask('verify','extract');
               $this->SetData('Completion', GetValue('Completion',$ThisAction,NULL));
               $this->SetData('Message', $this->Update->GetMeta('verify/message'));
               $this->Update->SetMeta('backup/message');
               $this->SetData('Menu', $this->UpdateModule->ToString());
            }
            
            break;
      }
      $this->Render($RenderView,$RenderController);
   }
   
   public function Changes() {
      $RenderController = 'verify';
      
      $RequestType = $this->RequestType();
      switch ($RequestType) {
         case 'ui':
            $this->UpdaterTitle = T('Check for modified core files...');
            $this->UpdaterTasks = array(
               'update/verify/changes'   => $this->UpdaterTitle
            );
            $RenderView = 'changes';
            break;
            
         case 'check':
         case 'perform':

            $RenderView = 'blank';
            $RenderController = 'update';

            if ($RequestType == 'perform') {
               // Don't interrupt if another process is already doing this.
               if ($this->Update->Progress('verify','changes')) {
                  exit();
               }
               
               // Unknown task length. Deploy spinner
               $DownloadModel = new DownloadModel();
               $AddonFile = $DownloadModel->GetAddonArchive('vanilla-core');
               
               if ($AddonFile == FALSE) {
                  $this->Fail('Could not get the downloaded archive path. Did the download fail?');
                  break;
               }
               
               $ZipModel = new ZipModel();
               $OpenStatus = $ZipModel->open($AddonFile);
               if (!$OpenStatus === TRUE) {
                  $this->Fail("Could not open the downloaded archive '%s'.", $AddoonFile);
                  break;
               }
               
               $this->Update->Progress('verify', 'changes', 0, TRUE);
               $LastProgress = 0; $NumFiles = $ZipModel->numFiles;
               for ($i = 0; $i < $NumFiles; $i++) {
                  $FileName = $ZipModel->getNameIndex($i);
                  //$ZipModel->extractTo(,$FileName);
                  $Progress = round(($i / $NumFiles) * 100,2);
                  if (floor($Progress) > floor($LastProgress))
                     $this->Update->Progress('verify','extract',$Progress,TRUE);
               }
               
               

            }
            
            if ($RequestType == 'check') {
               $ThisAction = $this->Update->GetTask('verify','changes');
               $this->SetData('Completion', GetValue('Completion',$ThisAction,NULL));
               $this->SetData('Message', $this->Update->GetMeta('verify/message'));
               $this->Update->SetMeta('backup/message');
               $this->SetData('Menu', $this->UpdateModule->ToString());
            }
            
            break;
      }
      $this->Render($RenderView,$RenderController);
   }
   
   private function Fail($Message) {
      $Args = func_get_args();
      array_shift($Args);
      $MessageFormed = vsprintf(T($Message), $Args);
      $this->Update->SetMeta('verify/message', $MessageFormed);
      $this->Update->Progress('verify','extract', -2, TRUE);
   }
   
}