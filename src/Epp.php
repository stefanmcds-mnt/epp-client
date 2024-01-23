<?php

/**
 * EppClient class.
 *
 * This class initialize all/single object to operate with Epp Server
 * 
 * The constructor search in exist 
 * 
 *
 * @category IT EPP Client
 * @package EPPAbstract
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

    protected ?array $LogDebug = [
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
     * array/object [
     *  'server'=>'',      // epp server
     *  'port'=>'',        // port connect to can be null
     *  'username',        // username connect to server es registrar
     *  'password',        // password
     *  'clTRIDprefix',    // the clTRID prefix registrar sigle
     *  'handleprefix',    // the handle/id prefix for create contact id
     *  'timezone',        // the timezone of epp server
     *  'lang',            // the language used by epp server can be null default en
     *  'debugfile',       // the debugfile can be null
     *  'certificatefile', // the certificate file of epp server can be null
     *  'interface'        // the interface can be null
     * ]
     * 
     * @param mixed|null $config
     */
    public function __construct(
        protected mixed $config = null,
        protected ?object $connection = null
    ) {
    }

    /**
     * Connect to Epp Server
     *
     * @return $nic istance
     */
    public function goCLIENT(?array $config = null)
    {
        //EppConnection::Protocol(action: "set", protocol: "curl");
        //self::$client = new EppConnection(EPPCfg: $config);
        $this->connection = new EppConnection(EPPCfg: $this->config);
        return $this->connection;
    }

    /**
     * Initialize Session Istance
     *
     * @param mixed $nic istance
     * @param bool $tostore
     * @return object $session istance
     */
    public function goSESSION(
        ?EppConnection $connection = null,
        ?bool $tostore = true
    ) {
        $session = new EppSession(connection: $this->connection, tostore: $tostore);
        $session->debug = $this->LogDebug['LOG_DEBUG'];
        return $session;
    }

    /**
     * Initialize Contact Istance
     *
     * @param mixed $nic istance
     * @param bool $tostore
     * @return object $contact istance
     */
    public function goCONTACT(
        ?EppConnection $connection = null,
        ?bool $tostore = true
    ) {
        $contact = new EppContact(connection: $this->connection, tostore: $tostore);
        $contact->debug = $this->LogDebug['LOG_DEBUG'];
        return $contact;
    }

    /**
     * Initialize Domain Istance
     *
     * @param mixed $nic istance
     * @param bool $tostore
     * @return object $domain istance
     */
    public function goDOMAIN(
        ?EppConnection $connection = null,
        ?bool $tostore = true
    ) {
        $domain = new EppDomain(connection: $this->connection, tostore: $tostore);
        $domain->debug = $this->LogDebug['LOG_DEBUG'];
        return $domain;
    }
}
