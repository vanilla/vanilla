<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('track_errors', 1);

$path = rtrim($argv[1], '/');

$manifest = array();
$emoji_paths = glob("$path/*.*");
$emoji = array();

$validExtensions = array('gif', 'png', 'jpeg', 'jpg', 'bmp', 'tif', 'tiff', 'svg');

$manifest['format'] = '<img class="emoji" src="%1$s" title="%2$s" alt="%2$s"/>';

foreach ($emoji_paths as $emoji_path) {
    $fileInfo = pathinfo($emoji_path);
    if (in_array(strtolower($fileInfo['extension']), $validExtensions)) {
        $basename = basename($emoji_path, '.'.$fileInfo['extension']);
        if (strtolower($basename) === 'icon') {
            $manifest['icon'] = $basename.'.'.$fileInfo['extension'];
        } else {
            $emoji[$basename] = $basename.'.'.$fileInfo['extension'];
        }
    }
}
if (!empty($emoji)) {
    $manifest['emoji'] = $emoji;
    $manifest['aliases'] = array(':)' => 'smile',
        ':D' => 'smiley',
        ':(' => 'disappointed',
        ';)' => 'wink',
        ':\\' => 'confused',
        ':o' => 'open_mouth',
        ':s' => 'confounded',
        ':p' => 'stuck_out_tongue',
        ":'(" => 'cry',
        ':|' => 'neutral_face',
        'B)' => 'sunglasses',
        ':#' => 'grin',
        'o:)' => 'innocent',
        '<3' => 'heart',
        '(*)' => 'star',
        '>:)' => 'smiling_imp',
        'D:' => 'anguished'
    );

    $manifest['editor'] = array('smile',
        'smiley',
        'disappointed',
        'wink',
        'confused',
        'open_mouth',
        'confounded',
        'stuck_out_tongue',
        'cry',
        'neutral_face',
        'sunglasses',
        'grin',
        'innocent',
        'heart',
        'star',
        'smiling_imp');
}

$fp = fopen($path.'/manifest.json', 'w');
fwrite($fp, json_encode($manifest, JSON_PRETTY_PRINT));
fclose($fp);

echo "Successfully wrote manifest.json to ".$path."/manifest.json.";
