<?php if (!defined('APPLICATION')) exit();

$PageInfo = $this->Data('PageInfo');
$Url = $PageInfo['Url'];

$Title = GetValue('Title', $PageInfo, '');
if ($Title == '')
   $Title = FormatString(T('Undefined discussion subject.'), array('Url' => $Url));
else {
   if ($Strip = C('Vanilla.Embed.StripPrefix'))
      $Title = StringBeginsWith($Title, $Strip, TRUE, TRUE);

   if ($Strip = C('Vanilla.Embed.StripSuffix'))
      $Title = StringEndsWith($Title, $Strip, TRUE, TRUE);
}
$Title = trim($Title);

$Description = GetValue('Description', $PageInfo, '');
$Images = GetValue('Images', $PageInfo, array());
$Body = FormatString(T('EmbeddedDiscussionFormat'), array(
    'Title' => $Title,
    'Excerpt' => $Description,
    'Image' => (count($Images) > 0 ? Img(GetValue(0, $Images), array('class' => 'LeftAlign')) : ''),
    'Url' => $Url
));
if ($Body == '')
   $Body = $ForeignUrl;
if ($Body == '')
   $Body = FormatString(T('EmbeddedNoBodyFormat.'), array('Url' => $Url));


echo '<h1>'.Gdn_Format::Text($Title).'</h1>';
echo '<div class="Wrap">';
echo $Body;

if (count($Images) > 1) {
   echo '<h2>Other Images</h2>';
   
   array_shift($Images);
   foreach ($Images as $Src) {
      echo Img($Src);
   }  
}

echo '</div>';