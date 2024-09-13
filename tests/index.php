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

try {
    //$session->dnssec = true; // Registrar use secDNS
    $session->Hello();
    $session->Login();
    //$session->Poll();
    //$domain->Check('stedns.it');
    $domain->domainVars = array_merge($domain->domainVars, ['name' => 'stedns.it', 'authInfo' => "STE20100201-REG-001"]);
    //$domain->set('name','stedns.it');
    //$domain->set('authInfo',"STE20100201-REG-001");
    $domain->Fetch();
    $session->Logout();
    //print_r($session->xmlResponse['body']);
    //print_r($session->xmlResult);
    //print_r($domain->xmlQuery);
    //print_r($domain->xmlResult);
    print_r($session->registry);
    //print_r($session->sessionVars);
    print_r($domain->domainVars);
    //print_r($domain->xmlResult);
} catch (Exception $err) {
    print_r(get_class($err));
    print_r("Message: " . $err->getMessage() . "\n");
    print_r("Code: " . $err->getCode() . "\n");
    print_r("Line: " . $err->getLine() . "\n");
    if ($message == false) {
        $message = $err->getMessage();
    } elseif ($message != $err->getMessage()) {
        echo "Additional message: " . $err->getMessage() . "<br /><br />";
    }
}
