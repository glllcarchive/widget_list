<?php

session_start();
$G_DEBUG  = true;
define('DB_SERVER', 'localhost');
define('DB_SERVER_USERNAME', 'root');
define('DB_SERVER_PASSWORD', 'root');
define('DB_DATABASE', 'development');

require('libs/db/mysql.php');
require('libs/widgets/tpl.php');
require('libs/widgets/WidgetList.php');
require('libs/widgets/WidgetReport.php');
require('libs/widgets/widgets.php');
require('libs/functions.php');

/*
 * example list calling pages
 */

$listPieces = array();

$listPieces['<!--CONTENT-->'] = '<!--LIST1--><!--LIST2-->';

//Simple version
require('examples/information_schema.tables.php');

//Advanced versions
require('examples/advanced.information_schema.columns.php');

echo Fill($listPieces, file_get_contents('index.tpl'));

?>