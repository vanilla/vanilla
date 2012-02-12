<?php if (!defined('APPLICATION')) exit();

/**
 * A simple timer class that can be used to time longer running processes.
 *
 * @author Todd Burry <todd@vanillaforums.com> 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
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