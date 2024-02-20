<?php

include 'config.php';

$included = get_included_files();
foreach ($included as $item) {
    print_r('Loaded Class ' . $item . "\n");
}

use EppClient\Epp;

$message = false;
//$epp = new Epp($GLOBALS['server']);

$connection = Epp::goCLIENT($GLOBALS['server']);
$session = Epp::goSESSION($connection, true);
$contact = Epp::goCONTACT($connection, true);
$domain = Epp::goDOMAIN($connection, true);

$session->Hello();
$session->Login();
$session->Poll();
//$domain->Check('stedns.it');
$session->Logout();

print_r($domain->xmlQuery);
print_r($domain->xmlResult);
print_r($session->xmlQuery);
print_r($session->sessionVars);
//print_r($session->xmlResponse['body']);
//print_r($session->xmlResult);
