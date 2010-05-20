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
 * A simple timer class that can be used to time longer running processes.
 */
class Gdn_Timer {
	public $StartTime;
	public $FinishTime;
	public $SplitTime;
	
	public function ElapsedTime() {
		if(is_null($this->FinishTime))
			$Result = microtime(TRUE) - $this->StartTime;
		else
			$Result = $this->FinishTime - $this->StartTime;
		return $Result;
	}
	
	public function Finish($Message = '') {
		$this->FinishTime = microtime(TRUE);
		if($Message)
			$this->Write($Message, $this->FinishTime, $this->StartTime);
	}
   
   public static function FormatElapsed($Span) {
      $m = floor($Span / 60);
      $s = $Span - $m * 60;
      return sprintf('%d:%05.2f', $m, $s);
   }
	
	public function Start($Message = '') {
		$this->StartTime = microtime(TRUE);
		$this->SplitTime = $this->StartTime;
		$this->FinishTime = NULL;
		
		if($Message)
			$this->Write($Message, $this->StartTime);
	}
	
	public function Split($Message = '') {
		$PrevSplit = $this->SplitTime;
		$this->SplitTime = microtime(TRUE);
		if($Message);
			$this->Write($Message, $this->SplitTime, $PrevSplit);
	}
	
	public function Write($Message, $Time = NULL, $PrevTime = NULL) {
		if($Message)
			echo $Message;
		if(!is_null($Time)) {
			if($Message)
				echo ': ';
			echo date('Y-m-d H:i:s', $Time);
			if(!is_null($PrevTime)) {
				$Span = $Time - $PrevTime;
				$m = floor($Span / 60);
				$s = $Span - $m * 60;
				echo sprintf(' (%d:%05.2f)', $m, $s);
			}
		}
		echo "\n";
	}

}