<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class ZipModel extends ZipArchive {

   protected $ArchiveName = NULL;
   protected $IncrementCount = 0;
   protected $MaxIncrement = 100;
   
   public function __construct() {
      echo "Opening new zipmodel\n";
   }
   
   public function open($ArchiveName, $Flags) {
      $Open = parent::open($ArchiveName, $Flags);
      if ($Open === TRUE) {
         $this->clear();
         $this->ArchiveName = $ArchiveName;
         echo "Opened archive: {$ArchiveName}\n";
      }
      return $Open;
   }
   
   public function setIncrement($Increment) {
      $this->MaxIncrement = $Increment;
   }
   
   public function addFile() {
      if ($this->IncrementCount >= $this->MaxIncrement)
         $this->reopen();
      
      $Filename = func_get_arg(0);
      echo "Adding file ({$this->IncrementCount}) - {$Filename}... ";
      
      if (func_num_args() == 2)
         $Success = parent::addFile($Filename, func_get_arg(1));
      
      elseif (func_num_args() == 1)
         $Success = parent::addFile($Filename);
         
      if ($Success) {
         echo "ok\n";
         $this->IncrementCount++;
      } else {
         echo "failed\n";
      }
         
      return $Success;
   }
   
   protected function clear() {
      $this->IncrementCount = 0;
   }
   
   protected function reopen() {
      if (!$this->close(FALSE)) return FALSE;
      $this->open($this->ArchiveName, ZipArchive::CREATE);
   }
   
   public function close($Hard = TRUE) {
      if ($Hard)
         $this->ArchiveName = NULL;
         
      $this->clear();
      return parent::close();
   }

}