<?php if (!defined('APPLICATION')) exit();
echo '<h1 class="H HomepageTitle">'.$this->Data('Title').'</h1>';
$ViewLocation = $this->FetchViewLocation('discussions', 'discussions');
?>
<div class="Categories">
   <?php foreach ($this->CategoryData->Result() as $Category) :
      if ($Category->CategoryID <= 0)
         continue;

      $this->Category = $Category;
      $this->DiscussionData = $this->CategoryDiscussionData[$Category->CategoryID];
      
      if ($this->DiscussionData->NumRows() > 0) : ?>
      
   <div class="CategoryBox Category-<?php echo $Category->UrlCode; ?>">      
      <h2 class="H"><?php
            echo Anchor(htmlspecialchars($Category->Name), CategoryUrl($Category));
            Gdn::Controller()->EventArguments['Category'] = $Category;
            Gdn::Controller()->FireEvent('AfterCategoryTitle'); 
      ?></h2>
      
      <ul class="DataList Discussions">
         <?php include($this->FetchViewLocation('discussions', 'discussions')); ?>
      </ul>
      
      <?php if ($this->DiscussionData->NumRows() == $this->DiscussionsPerCategory) : ?>
      <div class="MorePager">
         <?php echo Anchor(T('More Discussions'), '/categories/'.$Category->UrlCode); ?>
      </div>
      <?php endif; ?>
      
   </div>
   
      <?php endif; ?>
      
   <?php endforeach; ?>
</div>