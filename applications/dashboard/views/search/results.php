<?php if (!defined('APPLICATION')) exit(); ?>
<?php echo "<h1 class='sr-only'>" . t('Search') . "</h1>" ?>
<?php if (!count($this->data('SearchResults')) && $this->data('SearchTerm'))
    echo '<p class="NoResults">', sprintf(t('No results for %s.', 'No results for <b>%s</b>.'), $this->data('SearchTerm')), '</p>';
?>
    <ol id="search-results" class="DataList DataList-Search" start="<?php echo $this->data('From'); ?>">
        <?php foreach ($this->data('SearchResults') as $Row): ?>
            <li class="Item Item-Search">
                <h3 aria-level="2"><?php echo anchor(htmlspecialchars($Row['Title']), $Row['Url']); ?></h3>

                <div class="Item-Body Media">
                    <?php
                    $Photo = userPhoto($Row, ['LinkClass' => 'Img']);
                    if ($Photo) {
                        echo $Photo;
                    }
                    ?>
                    <div class="Media-Body">
                        <div class="Meta">
                            <?php
                            echo ' <span class="MItem-Author">'.
                                sprintf(t('by %s'), userAnchor($Row)).
                                '</span>';

                            echo bullet(' ');
                            echo ' <span class="MItem-DateInserted">'.
                                Gdn_Format::date($Row['DateInserted'], 'html').
                                '</span> ';


                            if (isset($Row['Breadcrumbs'])) {
                                echo bullet(' ');
                                echo ' <span class="MItem-Location">'.Gdn_Theme::breadcrumbs($Row['Breadcrumbs'], false).'</span> ';
                            }

                            if (isset($Row['Notes'])) {
                                echo ' <span class="Aside Debug">debug('.$Row['Notes'].')</span>';
                            }
                            ?>
                        </div>
                        <div class="Summary">
                            <?php echo htmlspecialchars($Row['Summary']); ?>
                        </div>
                        <?php
                        $Count = val('Count', $Row);
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
                        //                     anchor($child['Summary'], $child['Url']);
                        //                     '</li>';
                        //
                        //                  if ($i >= 3)
                        //                     break;
                        //               }
                        //               echo '</ul>';
                        //            }

                        if (($Count) > 1) {
                            $url = $this->data('SearchUrl').'&discussionid='.urlencode($Row['DiscussionID']).'#search-results';
                            echo '<div>'.anchor(plural($Count, '%s result', '%s results'), $url).'</div>';
                        }
                        ?>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>

<?php
echo '<div class="PageControls Bottom">';

$RecordCount = $this->data('RecordCount');
if ($RecordCount) {
    echo '<span class="Gloss">'.plural($RecordCount, '%s result', '%s results').'</span>';
}

PagerModule::write(['Wrapper' => '<div %1$s>%2$s</div>']);

echo '</div>';
