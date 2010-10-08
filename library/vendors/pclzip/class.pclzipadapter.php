<?php
require_once dirname(__FILE__).'/pclzip.lib.php';

class PclZipAdapter {
   /**
    * @var PclZip
    */
   public $PclZip = NULL;

   public $numFiles = NULL;

   protected $_Contents = NULL;
   protected $_Names = NULL;

   public function close() {
      if ($this->PclZip) {
         $this->PclZip = 0;
      }
   }

   public function deleteName($Name) {
      $Index = $this->_Names[$Name]['index'];
      $this->PclZip->deleteByIndex($Index);
   }

   public function extractTo($Path, $Names) {
      $Indexes = array();
      // Convert the name(s) to indexes.
      foreach ((array)$Names as $Name) {
         if (!isset($this->_Names[$Name]))
            continue;
         $Indexes[] = $this->_Names[$Name]['index'];
      }
      $IndexesStr = implode(',', $Indexes);

      $Result = $this->PclZip->extractByIndex($IndexesStr, $Path);
      return $Result != 0;
   }

   public function open($Path) {
      $this->PclZip = new PclZip($Path);
      $Result = $this->_Contents = $this->PclZip->listContent();
      if (!$Result) {
         return ZipArchive::ER_READ;
      }

      $this->_Names = array();
      foreach ($this->_Contents as $Content) {
         $this->_Names[$Content['filename']] = $Content;
      }

      $this->numFiles = count($this->_Contents);
      return TRUE;
   }

   public function statIndex($Index) {
      $Content = $this->_Contents[$Index];
      $Result = array(
          'name' => $Content['filename'],
          'index' => $Content['index'],
          'crc' => $Content['crc'],
          'size' => $Content['size'],
          'mtime' => $Content['mtime'],
          'comp_size' => $Content['compressed_size'],
          'comp_method' => FALSE
      );
      return $Result;
   }
}