<?php if (!defined('APPLICATION')) exit();

if (!function_exists('CategoryString')):
   
function CategoryString($Rows) {
   $Result = '';
   foreach ($Rows as $Row) {
      if ($Result)
         $Result .= ', ';
      $Result .= Anchor($Row['Name'], $Row['Url']);
   }
   return $Result;
}
endif;

if (!function_exists('GetOptions')):
/**
 * Render options that the user has for this discussion.
 */
function GetOptions($Category) {
   if (!Gdn::Session()->IsValid())
      return;
   
   $Sender = Gdn::Controller();
   
   
   $Result = '';
   $Options = '';
   $CategoryID = GetValue('CategoryID', $Category);

   $Result = '<div class="Options">';
   $TKey = urlencode(Gdn::Session()->TransientKey());

   // Mark category read.
   $Options .= '<li>'.Anchor(T('Mark Read'), "/vanilla/category/markread?categoryid=$CategoryID&tkey=$TKey").'</li>';

   // Follow/Unfollow category.
   if (!GetValue('Following', $Category))
      $Options .= '<li>'.Anchor(T('Unhide'), "/vanilla/category/follow?categoryid=$CategoryID&value=1&tkey=$TKey").'</li>';
   else
      $Options .= '<li>'.Anchor(T('Hide'), "/vanilla/category/follow?categoryid=$CategoryID&value=0&tkey=$TKey").'</li>';

   // Allow plugins to add options
   $Sender->FireEvent('DiscussionOptions');

   if ($Options != '') {
         $Result .= '<span class="ToggleFlyout OptionsMenu">';
            $Result .= '<span class="OptionsTitle">'.T('Options').'</span>';
            $Result .= '<span class="SpFlyoutHandle"></span>';
            $Result .= '<ul class="Flyout MenuItems">'.$Options.'</ul>';
         $Result .= '</span>';
      $Result .= '</div>';
      return $Result;
   }
}
endif;

if (!function_exists('MostRecentString')):
   function MostRecentString($Row) {
      if (!$Row['LastTitle'])
         return '';
   
      $R = '';
   
      $R .= '<span class="MostRecent">';
      $R .= '<span class="MLabel">'.T('Most recent:').'</span> ';
      $R .= Anchor(
         SliceString(Gdn_Format::Text($Row['LastTitle']), 150),
         $Row['LastUrl'].'#latest',
         'LatestPostTitle');

      if (GetValue('LastName', $Row)) {
         $R .= ' ';

         $R .= '<span class="MostRecentBy">'.T('by').' ';
         $R .= UserAnchor($Row, 'UserLink', 'Last');
         $R .= '</span>';
      }

      if (GetValue('LastDateInserted', $Row)) {
         $R .= ' ';

         $R .= '<span class="MostRecentOn">';
         $R .= T('on').' ';
         $R .= Anchor(
            Gdn_Format::Date($Row['LastDateInserted'], 'html'),
            $Row['LastUrl'],
            'CommentDate');
         $R .= '</span>';
      }
      
      $R .= '</span>';
      
      return $R;
   }
endif;

if (!function_exists('WriteListItem')):
   
function WriteListItem($Row, $Depth = 1) {
   $Children = $Row['Children'];
   $WriteChildren = FALSE;
   if (!empty($Children)) {
      if (($Depth + 1) >= C('Vanilla.Categories.MaxDisplayDepth')) {
         $WriteChildren = 'list';
      } else {
         $WriteChildren = 'items';
      }
   }
   
   $H = 'h'.($Depth + 1);
   ?>
   <li id="Category_<?php echo $Row['CategoryID']; ?>" class="<?php echo CssClass($Row); ?>">
      <div class="ItemContent Category">
         <?php echo GetOptions($Row); ?>
         
         <?php echo Wrap(Anchor($Row['Name'], $Row['Url'], 'Title'), $H, array('class' => 'CategoryName TitleWrap')); ?>
         
         <div class="CategoryDescription">
            <?php echo $Row['Description']; ?>
         </div>
         
         <?php if ($WriteChildren === 'list'): ?>
         <div class="ChildCategories">
            <?php
            echo Wrap(T('Child Categories').': ', 'b');
            echo CategoryString($Children, $Depth + 1);
            ?>
         </div>
         <?php endif; ?>
         
         <div class="Meta">
            <span class="MItem RSS"><?php
               echo Anchor(' ', '/categories/'.rawurlencode($Row['UrlCode']).'/feed.rss', 'SpRSS');
            ?></span>
            
            <span class="MItem MItem-Count DiscussionCount"><?php
               echo Plural(
                  $Row['CountDiscussions'],
                  '%s discussion',
                  '%s discussions',
                  Gdn_Format::BigNumber($Row['CountDiscussions'], 'html'));
            ?></span>
            
            <span class="MItem MItem-Count CommentCount"><?php
               echo Plural(
                  $Row['CountDiscussions'],
                  '%s comment',
                  '%s comments',
                  Gdn_Format::BigNumber($Row['CountComments'], 'html'));
            ?></span>
            
            <span class="MItem LastestPost LastDiscussionTitle"><?php
               echo MostRecentString($Row);
            ?></span>
         </div>
      </div>
   </li>
   <?php
   if ($WriteChildren === 'items') {
      foreach ($Children as $ChildRow) {
         WriteListItem($ChildRow, $Depth + 1);
      }
   }
}
endif;

if (!function_exists('WriteTableHead')):
   
function WriteTableHead() {
   ?>
   <tr>
      <td class="CategoryName"><?php echo T('Category'); ?></td>
      <td class="BigCount CountDiscussions"><?php echo T('Discussions'); ?></td>
      <td class="BigCount CountComments"><?php echo T('Comments'); ?></td>
      <td class="BlockColumn LatestPost"><?php echo T('Latest Post'); ?></td>
   </tr>
   <?php
}
endif;

if (!function_exists('WriteTableRow')):
   
function WriteTableRow($Row, $Depth = 1) {
   $Children = $Row['Children'];
   $WriteChildren = FALSE;
   if (!empty($Children)) {
      if (($Depth + 1) >= C('Vanilla.Categories.MaxDisplayDepth')) {
         $WriteChildren = 'list';
      } else {
         $WriteChildren = 'rows';
      }
   }
   
   $H = 'h'.($Depth + 1);
   ?>
   <tr class="<?php echo CssClass($Row); ?>">
      <td class="CategoryName">
         <?php 
         echo Wrap(
            Anchor($Row['Name'], $Row['Url']),
            $H);
         ?>
         <div class="CategoryDescription">
            <?php echo $Row['Description']; ?>
         </div>
         <?php if ($WriteChildren === 'list'): ?>
         <div class="ChildCategories">
            <?php
            echo Wrap(T('Child Categories').': ', 'b');
            echo CategoryString($Children, $Depth + 1);
            ?>
         </div>
         <?php endif; ?>
      </td>
      <td class="BigCount CountDiscussions">
         <div class="Wrap">
            <?php
//            echo "({$Row['CountDiscussions']})";
            echo BigPlural($Row['CountAllDiscussions'], '%s discussion');
            ?>
         </div>
      </td>
      <td class="BigCount CountComments">
         <div class="Wrap">
            <?php
//            echo "({$Row['CountComments']})";
            echo BigPlural($Row['CountAllComments'], '%s discussion');
            ?>
         </div>
      </td>
      <td class="BlockColumn LatestPost">
         <div class="Block Wrap">
            <?php if ($Row['LastTitle']): ?>
            <?php 
            echo UserPhoto($Row, array('ImageClass' => 'PhotoLink', 'Px' => 'Last'));
            echo Anchor(
               SliceString(Gdn_Format::Text($Row['LastTitle']), 100),
               $Row['LastUrl'].'#latest',
               'BlockTitle LatestPostTitle');
            ?>
            <div class="Meta">
               <?php
               echo UserAnchor($Row, 'UserLink MItem', 'Last');
               ?>
               <span class="Bullet">•</span>
               <?php 
               echo Anchor(
                  Gdn_Format::Date($Row['LastDateInserted'], 'html'),
                  $Row['LastUrl'],
                  'CommentDate MItem');
               ?>
            </div>
            <?php endif; ?>
         </div>
      </td>
   </tr>
   <?php
   if ($WriteChildren === 'rows') {
      foreach ($Children as $ChildRow) {
         WriteTableRow($ChildRow, $Depth + 1);
      }
   }
}
endif;

if (!function_exists('WriteCategoryList')):
   
function WriteCategoryList($Categories, $Depth = 1) {
   ?>
   <div class="DataListWrap">
   <ul class="DataList CategoryList">
      <?php
      foreach ($Categories as $Category) {
         WriteListItem($Category, $Depth);
      }
      ?>
   </ul>
   </div>
   <?php
}
endif;

if (!function_exists('WriteCategoryTable')):
   
function WriteCategoryTable($Categories, $Depth = 1) {
   ?>
   <div class="DataTableWrap">
   <table class="DataTable CategoryTable">
      <thead>
         <?php
            WriteTableHead();
         ?>
      </thead>
      <tbody>
         <?php
         foreach ($Categories as $Category) {
            WriteTableRow($Category, $Depth);
         }
         ?>
      </tbody>
   </table>
   </div>
   <?php
}
endif;