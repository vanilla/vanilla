<?php

class Gdn_SliceProvider {

   public function Slice($SliceName, $Arguments = array()) {
      $CurrentPath = Gdn::Request()->Path();
      $ExplodedPath = explode('/',$CurrentPath);
      switch ($this instanceof Gdn_IPlugin) {
         case TRUE:
            $ExplodedPath[2] = $SliceName;
         break;
         
         case FALSE:
            $ExplodedPath[1] = $SliceName;
         break;
      }
      
      return Gdn::Slice(implode('/',$ExplodedPath));
   }
   
   public function EnableSlicing(&$Sender) {
      $Sender->AddJsFile('/js/library/jquery.class.js');
      $Sender->AddJsFile('/js/slice.js');
   }

}