<?php if (!defined('APPLICATION')) exit(); ?>

<ul class="DataList Compact BlogList">
   <?php
   foreach ($this->data('Data', []) as $Row):
      $this->setData('Record', $Row);
   ?>
   <li id="<?php echo "{$Row['RecordType']}_{$Row['RecordID']}" ?>" class="Item">
      <?php
      if ($Name = getValue('Name', $Row)) {
         echo wrap(
            anchor(Gdn_Format::text($Name), $Row['Url']),
            'h3', ['class' => 'Title']);
      }
      ?>
      <div class="Item-Header">
         <div class="AuthorWrap">
            <span class="Author">
               <?php
               echo userPhoto($Row, ['Px' => 'Insert']);
               echo userAnchor($Row, ['Px' => 'Insert']);
               ?>
            </span>
<!--            <span class="AuthorInfo">
               <?php
               //echo wrapIf(GetValue('Title', $Author), 'span', array('class' => 'MItem AuthorTitle'));
               $this->fireEvent('AuthorInfo');
               ?>
            </span>-->
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
      </div>

      <div class="Item-BodyWrap">
         <div class="Item-Body">
            <div class="Message">
               <?php
               $linkContent = ' ('.t("View Post").')';
               $moreLink = anchor($linkContent, $Row['Url']);
               $bodyContent = Gdn_Format::excerpt($Row['Body'], $Row['Format']);
               $trimmedContent = sliceString($bodyContent, 200);

               echo $trimmedContent;
               echo $moreLink;
               ?>
            </div>
         </div>
      </div>

      <?php
      $RowObject = (object)$Row;
      Gdn::controller()->EventArguments['Object'] = $RowObject;
      Gdn::controller()->EventArguments[$Row['RecordType']] = $RowObject;
      Gdn::controller()->fireAs('DiscussionController')->fireEvent("After{$Row['RecordType']}Body");

      writeReactions($Row);
      ?>
   </li>
   <?php endforeach; ?>
</ul>
<?php
echo PagerModule::write();
?>
