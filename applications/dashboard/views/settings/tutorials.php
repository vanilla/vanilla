<?php if (!defined('APPLICATION')) exit();
require_once $this->FetchViewLocation('helper_functions');
$Tutorials = GetTutorials();

// Figure out the current video
$CurrentTutorialCode = $this->Data('CurrentTutorial');
$Keys = ConsolidateArrayValuesByKey($Tutorials, 'Code');
$Index = array_search($CurrentTutorialCode, $Keys);
if (!$Index)
   $Index = 0;
   
$CurrentTutorial = GetValue($Index, $Tutorials);
$CurrentTutorialCode = GetValue('Code', $CurrentTutorial, '');
?>
<style type="text/css">
div.Tutorials {
   padding: 20px;
}
.Video {
   margin-right: 20px;
   float: left;
}
.VideoInfo {
   min-height: 420px;
}
.VideoInfo strong {
   display: block;
   font-size: 15px;
}
.VideoInfo em {
   display: block;
   color: #555;
   font-size: 12px;
}
.Videos h2 {
   font-size: 15px;
}
.Videos a {
   line-height: 1.6;
   margin: 0 10px 10px 0;
   display: inline-block;
   width: 212px;
   vertical-align: top;
   color: #000;
}
.Videos a.Current,
.Videos a:hover {
   background: #eee;
}
.Videos span {
   display: block;
   padding: 0 6px 6px;
}
.Videos img {
   border: 6px solid #eee;
}
.Videos a.Current img,
.Videos a:hover img {
   border: 6px solid #ddd;
}
</style>
<h1><?php echo T('Help &amp; Tutorials'); ?></h1>
<div class="Tutorials">
   <div class="Video">
      <iframe wmode="transparent" src="http://player.vimeo.com/video/<?php echo $CurrentTutorial['VideoID']; ?>?title=0&byline=0&portrait=0&color=D0D9E0" width="700" height="394?wmode=transparent" frameborder="0"></iframe>
   </div>
   <div class="VideoInfo">
      <?php
      echo Wrap($CurrentTutorial['Name'], 'strong');
      echo Wrap($CurrentTutorial['Description'], 'em');
      // echo '<input type="text" value="'.Url('/settings/tutorials/'.$Tutorial['Code'], TRUE).'" />';
      ?>
   </div>
   <div class="Videos">
      <h2><?php echo T('Other Tutorials'); ?></h2>
      <?php
      foreach ($Tutorials as $Tutorial) {
         echo Anchor(
            '<img src="'.$Tutorial['Thumbnail'].'" alt="'.$Tutorial['Name'].'" />'
            .Wrap($Tutorial['Name'], 'span'),
            'settings/tutorials/'.$Tutorial['Code'],
            ($CurrentTutorialCode == $Tutorial['Code'] ? 'Current' : '')
         );
      }
      ?>
   </div>
</div>