<?php

/**
 * A simple class handling the EPP communication through $protocol to use.
 *
 *
 * @package EPPClient
 * @author STEF@N MCDS S.a.s. <info@stefan-mcds.it>
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 *
 */

namespace EppClient;

use EppClient\EppCurl;
use EppClient\EppSock;

class EppConnection
{

    /* Header */
    protected array $headers = [
        'content-type' => 'text/xml; charset=UTF-8',
    ];

    /**
     * Class constructor
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
     *  'interface'        // the interface 
     *  'protocol'         // the type of connection to epp server curl|sock
     * ]
     * - initialize View parent class and settings
     * - initialize HTTP Client
     *
     * @param array $config
     *
     */
    public function __construct(
        public mixed $EPPCfg = null,
        protected ?object $connection = null,
        protected mixed $response = null,
        protected ?string $clTRID = null,
        public ?string $protocol = null,
        protected ?string $cookie_dir = null
    ) {
        if ($this->EPPCfg === null) {
            exit("CLASS: EPPClientClass form Client.php\n FATAL ERROR: config file not proper initialized\n");
        } else if (!is_array($this->EPPCfg) && !is_object($this->EPPCfg)) {
            exit('CLASS: EPPClientClass from Client.php\n FATAL ERROR: config file must be an array or object');
        } else if (is_object($this->EPPCfg)) {
            $this->EPPCfg = (array)$this->EPPCfg;
        } else {
            if (isset($this->EPPCfg['protocol'])) {
                $this->protocol = $this->Protocol(action: "set", protocol: $this->EPPCfg['protocol']);
            } else {
                throw new EppException(message: 'Communication protocol can be set use EppConnection->Protocol(action:"set",protocol:"curl|sock")');
            }
            // setup default time zone
            if (isset($this->EPPCfg['timezone'])) {
                date_default_timezone_set($this->EPPCfg['timezone']);
            } else {
                date_default_timezone_set("Europe/Rome");
            }
            // configure temporary folder for storing cookies
            $this->cookie_dir = '/tmp';
            // initialize connection
            $this->protocol = '\\EppClient\\Epp' . ucwords(strtolower($this->protocol));
            if ($this->connection = new $this->protocol(server: $this->EPPCfg['server'])) {
                if ($this->connection !== null) {
                    $this->connection->Headers(action: 'set', headers: $this->headers);
                    // set server port
                    if (!empty($this->EPPCfg['port'])) {
                        $this->connection->Port(action: 'set', port: (int) $this->EPPCfg['port']);
                    }
                    // set debug filename
                    if (!empty($this->EPPCfg['debugfile'])) {
                        $this->connection->DebugFile(action: 'set', file: $this->EPPCfg['debugfile']);
                    }
                    // setup client certificate
                    if (!empty($this->EPPCfg['certificatefile'])) {
                        if (is_readable($this->EPPCfg['certificatefile'])) {
                            $this->connection->ClientCert(action: 'set', certFile: $this->EPPCfg['certificatefile']);
                        } else if (is_readable(realpath(dirname(__FILE__) . '/../../' . $this->EPPCfg['certificatefile']))) {
                            $this->connection->ClientCert(action: 'set', certFile: realpath(dirname(__FILE__) . '/../../' . $this->EPPCfg['certificatefile']));
                        }
                    }
                    // setup leaving interface
                    if (!empty($this->EPPCfg['interface'])) {
                        $this->connection->Interface(action: 'set', interface: $this->EPPCfg['interface']);
                    }
                } else {
                    throw new EppException('EPP Connection not success');
                }
            } else {
                throw new EppException('EPP Connection');
            }
            // set client transaction ID
            $this->_clTRID(action: 'set');
            return $this->connection;
        }
    }

    /**
     * class destructor
     *
     * @access public
     */
    public function __destruct()
    {
        $this->resetHttpClientCookie();
    }

    /**
     * Procotcol communication with Epp Server
     *
     * @param string|null $action
     * @param string|null $protocol
     * @return string
     */
    protected function Protocol(?string $action, ?string $protocol = 'curl')
    {
        if ($action === 'set') {
            $this->protocol = $protocol;
        }
        return $this->protocol;
    }

    /**
     * reset connection by removing the cookie file
     *
     * @access public
     * @return boolean
     */
    public function resetHttpClientCookie()
    {
        return unlink($this->connection->Cookie());
    }

    /**
     * initialize the client transaction ID
     *
     * @access public
     * @return string a random transaction ID, also stored to $clTRID
     */
    public function _clTRID(?string $action = null)
    {
        if ($action === 'set') {
            $this->clTRID = $this->EPPCfg['clTRIDprefix'] . "-" . time() . "-" . substr(md5(rand()), 0, 5);
            if (strlen($this->clTRID) > 32) {
                $this->clTRID = substr($this->clTRID, -32);
            }
        }
        return $this->clTRID;
    }

    /**
     * Send Request to Epp Server
     *
     * @param mixed $data
     * @return array with response key connect=boolean, status=string, headers=string, body=string, error=string 
     */
    public function sendRequest(mixed $data)
    {
        $this->response = $this->connection->query($data);
        return $this->fetchResponse();
    }

    /**
     * fetch the latest response from the EPP server
     *
     * @access public
     * @return array the latest response: (int) status, (array) headers, (string) body
     */
    public function fetchResponse()
    {
        return $this->response;
    }

    /**
     * convert an xml response to an object
     *
     * @param string|null option xml string to be parsed
     * @return string Epp Response
     */
    public function parseResponse(?string $xml = null)
    {
        if ($xml === null) {
            $response = $this->fetchResponse();
            return $response['body'];
        } else {
            return $xml;
        }
    }
}
