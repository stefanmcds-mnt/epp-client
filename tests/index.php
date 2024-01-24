<?php

require_once('config.php');

use EppClient\Epp;

$message = false;
$epp = new Epp($GLOBALS['server']);

$connection = $epp->goCLIENT();
$session = $epp->goSESSION($connection, true);
$contact = $epp->goCONTACT($connection, true);
$domain = $epp->goDOMAIN($connection, true);

$session->Hello();
print_r($session::$sessionVars);
