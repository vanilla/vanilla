<?php

$t = $_GET['t'];
$p = $_GET['p'];

if ($p) {
   header("Location: discussion/comment/$p", TRUE, 301);
} else {
   header("Location: discussion/$t/x", TRUE, 301);
}