<?php if (!defined('APPLICATION')) exit();

/*
  TODO: REMOVE - OBSELETE - 2009-02-07

/// <namespace>
/// Garden.UI
/// </namespace>

if (!class_exists('ToolbarModule', FALSE)) {
   /// <summary>
   /// Renders a toolbar object.
   /// </summary>
   class Toolbar implements Gdn_IModule {
      
      /// <prop type="array">
      /// An array of toolbar items.
      /// </prop>
      public $Items;
      
      /// <prop type="string">
      /// The html id attribute to be applied to the root element of the
      /// toolbar. Default is empty.
      /// </prop>
      public $HtmlId;
      
      /// <prop type="string">
      /// The class attribute to be applied to the root element of the
      /// toolbar. Default is empty.
      /// </prop>
      public $CssClass;
      
      public function __construct($Sender) {
         // $this->CssClass = $CssClass;
         // $this->HtmlId = $HtmlId;
         $this->Items = array();
      }

      public function AddItem($Code, $Url = '', $CssClass = '') {
         if (!is_array($this->Items))
            $this->Items = array();

         $this->Items[] = array('Code' => $Code, 'Url' => $Url, 'CssClass' => $CssClass);
      }
      
      public function AssetTarget() {
         return 'Content';
      }
      
      public function ClearItems() {
         $this->Items = array();
      }
      
      public function Name() {
         return 'Toolbar';
      }
   
      public function ToString() {
         $Username = '';
         $UserID = '';
         $Session = Gdn::Session();
         if ($Session->IsValid() === TRUE) {
            $UserID = $Session->User->UserID;
            $Username = $Session->User->Name;
         }
         
         $Toolbar = '';
         $ItemCount = count($this->Items);
         $Count = 1;
         if ($ItemCount > 0) {
            foreach ($this->Items as $Key => $Item) {
               // Make sure the first & last items are marked.
               $CssClass = ArrayValue('CssClass', $Item, '');
               if ($Count == 1)
                  $CssClass .= ' First';
               else if ($Count == $ItemCount)
                  $CssClass .= ' Last';
                  
               if ($CssClass != '')
                  $CssClass = ' class="'.$CssClass.'"';
                  
               $Url = StringIsNullOrEmpty($Item['Url']) ? FALSE : $Item['Url'];
               $Text = str_replace('{Username}', $Username, Translate($Item['Code']));
               if ($Url !== FALSE) {
                  $Url = str_replace(array('{Username}', '{UserID}'), array($Username, $UserID), $Url);
                  if (substr($Url, 0, 5) != 'http:')
                     $Url = Url($Url);
                     
                  $Toolbar .= '<li'.$CssClass.'><a href="'.$Url.'">'.$Text.'</a></li>';
               } else {
                  $Toolbar .= '<li'.$CssClass.'>'.$Text.'</li>';
               }
               ++$Count;
            }
            $Toolbar = '<ul'.Attribute('id', $this->HtmlId).Attribute('class', $this->CssClass).'>'.$Toolbar."</ul>\r\n";
         }
         return $Toolbar;
      }
   }
}
*/