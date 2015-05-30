<?php
/**
 * Handles 301 redirecting urls from forums that were imported from phpBB or
 * vBulletin into their Vanilla equivalent pages.
 */
$Redirects = array(
    'post' => 'discussion/comment/%1$d#Comment_%1$d',
    'thread' => 'discussion/%d/redirect/%s',
    'category' => 'categories/%d',
    'user' => 'dashboard/profile/%d/x'
);

$Type = NULL;
$Index = 0;
if (array_key_exists('p', $_GET) && $Index = $_GET['p'])
    $Type = 'post';
elseif (array_key_exists('t', $_GET) && $Index = $_GET['t'])
    $Type = 'thread';
elseif (array_key_exists('f', $_GET) && $Index = $_GET['f'])
    $Type = 'category';
elseif (array_key_exists('u', $_GET) && $Index = $_GET['u'])
    $Type = 'user';

switch ($Type) {
    case 'user':
    case 'category':
    case 'post':
        $Redirect = sprintf($Redirects[$Type], $Index);
        break;
    case 'thread':
        $HasPage = array_key_exists('page', $_GET);
        $PageNumber = 1;
        if ($HasPage) {
            $OldPageNumber = $_GET['page'];
            $Comments = 25 * $OldPageNumber;
            $PageNumber = ceil($Comments / 40);
        }

        $PageKey = "p{$PageNumber}";
        $Redirect = sprintf($Redirects[$Type], $Index, $PageKey);
        break;
    default:
        $Type = 'home';
        $Redirect = '/';
        break;
}

if (!is_null($Type)) {
    header("Location: {$Redirect}", TRUE, 301);
    exit();
}

header("Location: index.php", TRUE, 301);
exit();

//$t = $_GET['t'];
//$p = $_GET['p'];
//
//if ($p) {
//   header("Location: discussion/comment/$p", TRUE, 301);
//} else {
//   header("Location: discussion/$t/x", TRUE, 301);
//}
