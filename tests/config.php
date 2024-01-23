<?php

/**
 * File delle configurazioni Server-Side
 */

// array associativo delle configurazioni
$config = [
    'server' => [
        'server' => 'https://epp.nic.it',      // epp server
        'port' => null,        // port connect to can be null
        'username' => 'STEFANMCDS-REG',        // username connect to server es registrar
        'password' => 'S18ef112-12',        // password
        'clTRIDprefix' => 'STEREG',    // the clTRID prefix registrar sigle
        'handleprefix' => 'STEREG-',    // the handle/id prefix for create contact id
        'timezone' => 'Europe/Rome',        // the timezone of epp server
        'lang' => 'en',            // the language used by epp server can be null default en
        'debugfile' => null,       // the debugfile can be null
        'certificatefile' => null, // the certificate file of epp server can be null
        'interface' => null,        // the interface can be null
        'protocol' => 'curl'
    ],
    'include' => [
        'src' => "../src",
        'vendor' => '../vendor/autoload.php',
    ]
];
// dichiarazioni delle variabili globali
// l'array associativo dichiarato on la variabile $GLOBALS
foreach ($config as $key => $value) {
    $GLOBALS["$key"] = $value;
}

// Caricamento delle classi
require_once("autoload.php");
