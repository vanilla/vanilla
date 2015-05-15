<h1 class="H"><?php echo $this->Data('Title'); ?></h1>
<?php

if ($data = $this->Data('Content')) {
   if ($view = $this->Data('View') == 'table') {
      writeTable($data);
   } else {
      writeList($data);
   }
} else {
   echo $this->Data('EmptyMessage');
}

function writeList($data) {
?>
    <ul class="PromotedContentList DataList">
       <?php foreach ($data as $row) {
          writeRow($row, 'modern');
       } ?>
    </ul>
<?php
}

function writeTable($data) { ?>
   <div class="DataTableContainer">
      <div class="DataTableWrap">
         <table class="DataTable">
            <thead>
            <tr>
               <td class="DiscussionName">
                  <div class="Wrap"><?php echo T('Subject'); ?></div>
               </td>
               <td class="BlockColumn BlockColumn-User LastUser">
                  <div class="Wrap"><?php echo T('Author'); ?></div>
               </td>
            </tr>
            </thead>
            <?php foreach ($data as $row) {
               writeRow($row, 'table');
            } ?>
         </table>
      </div>
   </div>
   <?php
}

function writeRow($row, $view) {
   $title        =  htmlspecialchars(val('Name', $row));
   $url          = val('Url', $row);
   $body         = Gdn_Format::PlainText(val('Body', $row), val('Format', $row));
   $categoryUrl  = val('CategoryUrl', $row);
   $categoryName = val('CategoryName', $row);
   $date         = val('DateUpdated', $row) ?: val('DateInserted', $row);
   $date         = Gdn_Format::Date($date, 'html');
   $type         = val('RecordType', $row, 'post');
   $id           = val('CommentID', $row, val('DiscussionID', $row, ''));
   $author       = val('Author', $row);
   $username     = val('Name', $author);
   $userUrl      = val('Url', $author);
   $userPhoto    = val('PhotoUrl', $author);
   $cssClass     = val('_CssClass', $author);

   if ($view == 'table') {
   ?>
<tr id="Promoted_<?php echo $type.'_'.$id; ?>" class="Item PromotedContent-Item <?php echo $cssClass; ?>">
   <td class="Name">
      <div class="Wrap">
         <a class="Title" href="<?php echo $url; ?>">
               <?php echo $title; ?>
         </a>
         <span class="MItem Category"><?php echo T('in'); ?> <a href="<?php echo $categoryUrl; ?>" class="MItem-CategoryName"><?php echo $categoryName; ?></a></span>
         <div class="Description"><?php echo $body; ?></div>
      </div>
   </td>
   <td class="BlockColumn BlockColumn-User User">
      <div class="Block Wrap">
         <a class="PhotoWrap PhotoWrapSmall" href="<?php echo $userUrl; ?>">
            <img class="ProfilePhoto ProfilePhotoSmall" src="<?php echo $userPhoto; ?>">
         </a>
         <a class="UserLink BlockTitle" href="<?php echo $userUrl; ?>"><?php echo $username; ?></a>
         <div class="Meta">
            <a class="CommentDate MItem" href="<?php echo $url; ?>"><?php echo $date; ?></a>
         </div>
      </div>
   </td>
</tr>

   <?php } else { ?>

<li id="Promoted_<?php echo $type.'_'.$id; ?>" class="Item PromotedContent-Item <?php echo $cssClass; ?>">
   <?php if (C('EnabledPlugins.IndexPhotos')) { ?>
   <a title="<?php echo $username; ?>" href="<?php echo $userUrl; ?>" class="IndexPhoto PhotoWrap">
      <img src="<?php echo $userPhoto; ?>" alt="<?php echo $username; ?>" class="ProfilePhoto ProfilePhotoMedium">
   </a>
   <?php } ?>
   <div class="ItemContent Discussion">
      <div class="Title">
         <a href="<?php echo $url; ?>">
            <?php echo $title; ?>
         </a>
      </div>
      <div class="Excerpt"><?php echo $body; ?></div>
      <div class="Meta">
         <span class="MItem DiscussionAuthor"><ahref="<?php echo $userUrl; ?>"><?php echo $username; ?></a></span>
         <span class="MItem Category"><?php echo T('in'); ?> <a href="<?php echo $categoryUrl; ?>" class="MItem-CategoryName"><?php echo $categoryName; ?></a></span>
      </div>
   </div>
</li>

   <?php }
}
?>
