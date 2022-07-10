<?php if (!defined('APPLICATION')) exit();
require_once Gdn::controller()->fetchViewLocation('reaction_functions', '', 'plugins/Reactions');
foreach ($this->data('Data', []) as $Row): 
   $this->setData('Record', $Row);
   $Body = Gdn_Format::to($Row['Body'], $Row['Format']);
   $CssClass = 'Item';
?>
<div id="<?php echo "{$Row['RecordType']}_{$Row['RecordID']}" ?>" class="<?php echo $CssClass; ?>">
   <div class="Item-Wrap">
      <div class="Item-Body">
         <div class="BodyWrap">
            <?php
            if ($Name = getValue('Name', $Row)) {
               echo wrap(
                  anchor(Gdn_Format::text($Name), $Row['Url']),
                  'h3', ['class' => 'Title']);
            }
            ?>
            <div class="Body Message">
               <?php
               echo $Body;
               unset($Body);
               ?>
            </div>
         </div>
      </div>
      <div class="Item-Footer">
         <div class="FooterWrap">
            <div class="AuthorWrap">
               <span class="Author">
                  <?php
                  echo userPhoto($Row, ['Px' => 'Insert']);
                  echo userAnchor($Row, ['Px' => 'Insert']);
                  ?>
               </span>
            </div>
            <div class="Meta">
               <span class="MItem DateCreated">
                  <?php
                  echo anchor(
                     Gdn_Format::date($Row['DateInserted'], 'html'),
                     $Row['Url'],
                     'Permalink'
                     );
                  ?>
               </span>
            </div>
            <?php
            $RowObject = (object)$Row;
            writeReactions($Row);
            ?>
         </div>
      </div>
   </div>
</div>
<?php 
endforeach;
