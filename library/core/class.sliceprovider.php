<?php

class Gdn_SliceProvider {

   public function Slice($SliceName, $Arguments = array()) {
      $CurrentPath = Gdn::Request()->Path();
      $ExplodedPath = explode('/',$CurrentPath);
      switch ($this instanceof Gdn_IPlugin) {
         case TRUE:
            $ReplacementIndex = 2;
         break;
         
         case FALSE:
            $ReplacementIndex = 1;
         break;
      }
      
      if ($ExplodedPath[0] == strtolower(Gdn::Dispatcher()->Application()) && $ExplodedPath[1] == strtolower(Gdn::Dispatcher()->Controller()))
         $ReplacementIndex++;

      $ExplodedPath[$ReplacementIndex] = $SliceName;
      $SlicePath = implode('/',$ExplodedPath);
      return Gdn::Slice($SlicePath);
   }
   
   public function EnableSlicing(&$Sender) {
      $Sender->AddJsFile('/js/library/jquery.class.js');
      $Sender->AddJsFile('/js/slice.js');
   }

}