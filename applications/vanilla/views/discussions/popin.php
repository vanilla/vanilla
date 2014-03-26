<?php if (!defined('APPLICATION')) exit(); ?>
<ul class="PopList Popin">
<?php
if (count($this->Data('Discussions'))):
?>
      <li class="Item Title">
         <?php echo Wrap($this->Data('Title'), 'strong'); ?>
      </li>
      <?php 
      foreach ($this->Data('Discussions') as $Row):
      ?>
      <li class="Item">
         <div class="Author Photo"><?php echo UserPhoto($Row, array('Px' => 'First')); ?></div>
         <div class="ItemContent">
            <b class="Subject"><?php echo Anchor($Row->Name, $Row->Url.'#latest'); ?></b>
            <div class="Meta">
               <?php 
               echo ' <span class="MItem">'.Plural($Row->CountComments, '%s comment', '%s comments').'</span> ';

               if ($Row->CountUnreadComments === TRUE) {
                  echo ' <strong class="HasNew"> '.T('new').'</strong> ';
               } elseif ($Row->CountUnreadComments > 0) {
                  echo ' <strong class="HasNew"> '.Plural($Row->CountUnreadComments, '%s new', '%s new plural').'</strong> ';
               }

               echo ' <span class="MItem">'.Gdn_Format::Date($Row->DateLastComment).'</span> ';
               ?>
            </div>
         </div>
      </li>
      <?php endforeach; ?>
      <li class="Item Center">
         <?php
         echo Anchor(sprintf(T('All %s'), T('Bookmarks')), '/discussions/bookmarked'); 
         ?>
      </li>
<?php else: ?>
<li class="Item Empty Center"><?php echo sprintf(T('You do not have any %s yet.'), T('bookmarks')); ?></li>
<?php endif; ?>
</ul>