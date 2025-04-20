<?php
include 'connect.php';
//Routes
$tmp = 'includes/tmps/'; //Template dir
$css = 'layout/css/'; //css dir
$js = 'layout/js/'; //js dir
$lang = 'includes/langs/'; //lang dir
$func = 'includes/func/'; //func dir


//include the important files

// Include functions.php before other files
include $tmp . 'pages.php';
include $func . 'functions.php';
include $func . 'validationFunc.php';

include $lang . 'en.php';
// include $lang . 'ar.php';
include $tmp . 'header.php';

if (!isset($noNavbar)) {
    include $tmp . 'navbar.php';
}
