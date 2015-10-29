<?php if (!defined('APPLICATION')) exit();

$PageInfo = $this->data('PageInfo');
$Url = $PageInfo['Url'];

$Title = val('Title', $PageInfo, '');
if ($Title == '') {
    $Title = formatString(t('Undefined discussion subject.'), array('Url' => $Url));
} else {
    if ($Strip = c('Vanilla.Embed.StripPrefix')) {
        $Title = stringBeginsWith($Title, $Strip, true, true);
    }

    if ($Strip = c('Vanilla.Embed.StripSuffix')) {
        $Title = StringEndsWith($Title, $Strip, true, true);
    }
}
$Title = trim($Title);

$Description = val('Description', $PageInfo, '');
$Images = val('Images', $PageInfo, array());
$Body = formatString(t('EmbeddedDiscussionFormat'), array(
    'Title' => htmlspecialchars($Title),
    'Excerpt' => htmlspecialchars($Description),
    'Image' => (count($Images) > 0 ? img(val(0, $Images), array('class' => 'LeftAlign')) : ''),
    'Url' => htmlspecialchars($Url)
));
if ($Body == '') {
    $Body = $ForeignUrl;
}
if ($Body == '') {
    $Body = formatString(t('EmbeddedNoBodyFormat.'), array('Url' => $Url));
}

echo '<h1>'.Gdn_Format::text($Title).'</h1>';
echo '<div class="Wrap">';
echo $Body;

if (count($Images) > 1) {
    echo '<h2>Other Images</h2>';

    array_shift($Images);
    foreach ($Images as $Src) {
        echo img($Src);
    }
}

echo '</div>';
