<?php
/**
 * Handles 301 redirecting urls from forums that were imported from phpBB or
 * vBulletin into their Vanilla equivalent pages.
 */
$redirects = array(
    'post' => 'discussion/comment/%1$d#Comment_%1$d',
    'thread' => 'discussion/%d/redirect/%s',
    'category' => 'categories/%d',
    'user' => 'dashboard/profile/%d/x'
);

$type = null;
$index = 0;
if (array_key_exists('p', $_GET) && $index = $_GET['p']) {
    $type = 'post';
} elseif (array_key_exists('t', $_GET) && $index = $_GET['t']) {
    $type = 'thread';
} elseif (array_key_exists('f', $_GET) && $index = $_GET['f']) {
    $type = 'category';
} elseif (array_key_exists('u', $_GET) && $index = $_GET['u']) {
    $type = 'user';
}

switch ($type) {
        case 'user':
        case 'category':
        case 'post':
            $redirect = sprintf($redirects[$type], $index);
            break;
        case 'thread':
            $hasPage = array_key_exists('page', $_GET);
            $pageNumber = 1;
            if ($hasPage) {
                $oldPageNumber = $_GET['page'];
                $comments = 25 * $oldPageNumber;
                $pageNumber = ceil($comments / 40);
            }

            $pageKey = "p{$pageNumber}";
            $redirect = sprintf($redirects[$type], $index, $pageKey);
            break;
        default:
            $type = 'home';
            $redirect = '/';
            break;
}

if (!is_null($type)) {
    header("Location: {$redirect}", true, 301);
    exit();
}

header("Location: index.php", true, 301);
exit();

//$t = $_GET['t'];
//$p = $_GET['p'];
//
//if ($p) {
//   header("Location: discussion/comment/$p", TRUE, 301);
//} else {
//   header("Location: discussion/$t/x", TRUE, 301);
//}
