<?php

/**
 * EppClient class.
 *
 * This class initialize all/single object to operate with Epp Server
 * 
 * The constructor search in exist 
 * 
 * Epp commands are distinguished into three categories
 * - session command (hello,login,logout)
 * - create/update command on contact and domain object
 * - server command that interacts without any update action on contact and domain object such as check,info,poll
 *   
 * So that above command are stored into this Class
 * - EppSession all command session and server not for Contact and domain object
 * - EppContact all command for Contact object 
 * - EppDomain all command for Domain Object
 * 
 * EppConnection are the connection to Epp Server and use Curl or Sock protocol
 * 
 *
 * @category EPPClient
 * @package Epp
 * @author STEF@N MCDS S.a.s. <info@stefan-mcds.it>
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 *
 */

namespace EppClient;

use EppClient\EppConnection;
use EppClient\EppSession;
use EppClient\EppContact;
use EppClient\EppDomain;

class Epp
{

    static public ?array $LogDebug = [
        'LOG_EMERG' => 0, /* system is unusable */
        'LOG_ALERT' => 1, /* action must be taken immediately */
        'LOG_CRIT' => 2, /* critical conditions */
        'LOG_ERR' => 3, /* error conditions */
        'LOG_WARNING' => 4, /* warning conditions */
        'LOG_NOTICE' => 5,  /* normal but significant condition */
        'LOG_INFO' => 6,  /* informational */
        'LOG_DEBUG' => 7, /* debug-level messages */
        0 => 'LOG_EMERG',
        1 => 'LOG_ALERT',
        2 => 'LOG_CRIT',
        3 => 'LOG_ERR',
        4 => 'LOG_WARNING',
        5 => 'LOG_NOTICE', // for backward compatibility with old versions of PHP
        6 => 'LOG_INFO',
        7 => 'LOG_DEBUG', //default level for logMessage() method
    ];

    //protected mixed $client;

    /**
     * Class Constructor
     *
     * - $config:
     *  [
     *  'server'=>'',          // epp server
     *  'port'=>'',            // port connect to can be null
     *  'username'=>'',        // username connect to server es registrar
     *  'password'=>'',        // password
     *  'clTRIDprefix'=>'',    // the clTRID prefix registrar sigle
     *  'handleprefix'=>'',    // the handle/id prefix for create contact id
     *  'timezone'=>'',        // the timezone of epp server
     *  'lang'=>'',            // the language used by epp server can be null default en
     *  'debugfile'=>'',       // the debugfile can be null
     *  'certificatefile'=>'', // the certificate file of epp server can be null
     *  'interface'=>''        // the interface can be null
     *  'secdns'=>true/false   // if the registrar use secDNS
     * ]
     * 
     * @param mixed|null $config
     */
    public function __construct(
        protected mixed $config,
        protected EppConnection $connection
    ) {
    }

    /**
     * Connect to Epp Server
     *
     * @return $nic istance
     */
    static public function goCLIENT(?array $config = null)
    {
        // pass $config as array
        return new EppConnection(...$config);
    }

    /**
     * Initialize Session Istance
     *
     * @param mixed $connection istance
     * @param bool $tostore
     * @return object $session istance
     */
    static public function goSESSION(
        $connection,
        ?bool $tostore = true
    ) {
        $session = new EppSession(connection: $connection, tostore: $tostore);
        $session->debug = self::$LogDebug['LOG_DEBUG'];
        // uncomment if Epp use secDNS
        //$session->dnssec = true;
        return $session;
    }

    /**
     * Initialize Contact Istance
     *
     * @param mixed $connection istance
     * @param bool $tostore
     * @return object $contact istance
     */
    static public function goCONTACT(
        $connection,
        ?bool $tostore = true
    ) {
        $contact = new EppContact(connection: $connection, tostore: $tostore);
        $contact->debug = self::$LogDebug['LOG_DEBUG'];
        return $contact;
    }

    /**
     * Initialize Domain Istance
     *
     * @param mixed $connection istance
     * @param bool $tostore
     * @return object $domain istance
     */
    static public function goDOMAIN(
        $connection,
        ?bool $tostore = true
    ) {
        $domain = new EppDomain(connection: $connection, tostore: $tostore);
        $domain->debug = self::$LogDebug['LOG_DEBUG'];
        return $domain;
    }
}
