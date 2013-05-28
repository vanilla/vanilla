<?php if (!defined('APPLICATION')) exit(); ?>

<ol id="search-results" class="DataList DataList-Search" start="<?php echo $this->Data('From'); ?>">
   <?php foreach ($this->Data('SearchResults') as $Row): ?>
   <li class="Item Item-Search">
      <h3><?php echo Anchor($Row['Title'], $Row['Url']); ?></h3>
      <div class="Item-Body Media">
         <?php
         $Photo = UserPhoto($Row, array('LinkClass' => 'Img'));
         if ($Photo) {
            echo $Photo;
         }
         ?>
         <div class="Media-Body">
            <div class="Meta">
            <?php
               echo ' <span class="MItem-Author">'.
                  sprintf(T('by %s'), UserAnchor($Row)).
                  '</span>';

               echo Bullet(' ');
               echo ' <span clsss="MItem-DateInserted">'.
                  Gdn_Format::Date($Row['DateInserted'], 'html').
                  '</span> '; 


               if (isset($Row['Breadcrumbs'])) {
                  echo Bullet(' ');
                  echo ' <span class="MItem-Location">'.Gdn_Theme::Breadcrumbs($Row['Breadcrumbs'], FALSE).'</span> ';
               }

               if (isset($Row['Notes'])) {
                  echo ' <span class="Aside Debug">debug('.$Row['Notes'].')</span>';
               }
            ?>
            </div>
            <div class="Summary">
               <?php echo $Row['Summary']; ?>
            </div>
            <?php
            $Count = GetValue('Count', $Row);
//            $i = 0;
//            if (isset($Row['Children'])) {
//               echo '<ul>';
//               
//               foreach($Row['Children'] as $child) {
//                  if ($child['PrimaryID'] == $Row['PrimaryID'])
//                     continue;
//                  
//                  $i++;
//                  $Count--;
//                  
//                  echo "\n<li>".
//                     Anchor($child['Summary'], $child['Url']);
//                     '</li>';
//
//                  if ($i >= 3)
//                     break;
//               }
//               echo '</ul>';
//            }
            
            if (($Count) > 1) {
               $url = $this->Data('SearchUrl').'&discussionid='.urlencode($Row['DiscussionID']).'#search-results';
               echo '<div>'.Anchor(Plural($Count, '%s result', '%s results'), $url).'</div>';
            }
            ?>
         </div>
      </div>
   </li>
   <?php endforeach; ?>
</ol>

<?php
echo '<div class="PageControls Bottom">';

$RecordCount = $this->Data('RecordCount');
if ($RecordCount)
   echo '<span class="Gloss">'.Plural($RecordCount, '%s result', '%s results').'</span>';

PagerModule::Write(array('Wrapper' => '<div %1$s>%2$s</div>'));

echo '</div>';