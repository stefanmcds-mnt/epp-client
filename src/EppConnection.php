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
    private ?object $connection = null;
    private mixed $response = null;
    private ?string $clTRID = null;

    /**
     * Procotcol communication with Epp Server
     *
     * @param string|null $action
     * @param string|null $protocol
     * @return string
     */
    public static function _setProtocol(?string $action, ?string $protocol = 'curl')
    {
        if ($action === 'set') {
            self::$protocol = $protocol;
        }
        return self::$protocol;
    }

    /**
     * Class constructor
     *
     * Params can be passed as array or single es:
     * [
     *  'server'=>'',      // epp server
     *  'port'=>'',        // port connect to can be null
     *  'username',        // username connect to server es registrar
     *  'password',        // password
     *  'clTRIDprefix',    // the clTRID prefix registrar sigle
     *  'ContactPrefix',   // the handle/id prefix for create contact id
     *  'timezone',        // the timezone of epp server
     *  'lang',            // the language used by epp server can be null default en
     *  'debugfile',       // the debugfile can be null
     *  'certificatefile', // the certificate file of epp server can be null
     *  'interface'        // the interface
     *  'protocol'         // the type of connection to epp server curl|sock
     * ]
     *
     *  @param string|null $server          epp server
     *  @param int|null    $port            epp server port to can be null
     *  @param string|null $username        epp username
     *  @param string|null $password        epp password
     *  @param string|null $clTRIDprefix    the clTRID prefix registrar sigle
     *  @param string|null $ContactPrefix   the handle/id prefix used for create contact id
     *  @param string|null $timezone        epp server timezone
     *  @param string|null $lang,           epp server language can be null default en
     *  @param string|null $debugfile,      the debugfile can be null
     *  @param string|null $certificatefile epp server certificate file can be null
     *  @param string|null $interface       epp interface
     *  @param string|null $protocol        type of connection to epp server curl|sock
     *  @param string|null $cookie_dir      coocker folder
     *
     * @return EppConnection
     */
    public function __construct(
        public ?string $server          = null,
        public ?int    $port            = null,
        public ?string $username        = null,
        public ?string $password        = null,
        public ?string $clTRIDprefix    = null,
        public ?string $ContactPrefix   = null,
        public ?string $timezone        = null,
        public ?string $lang            = null,
        public ?string $debugfile       = null,
        public ?string $certificatefile = null,
        public ?string $interface       = null,
        public ?string $protocol        = null,
        public ?string $cookie_dir      = null
    ) {
        $status = true;
        if ($this->server === null) {
            $status = false;
            throw new EppException(message: 'The EPP Server is not defined.');
        } else if ($this->username === null) {
            $status = false;
            throw new EppException(message: 'The EPP Username is not defined.');
        } else if ($this->password === null) {
            $status = false;
            throw new EppException(message: "The EPP Password is not defined.");
        } else if ($this->protocol === null) {
            $status = false;
            throw new EppException(message: 'The Connection Protocol is not defined (curl or sock).');
        } else if ($this->clTRIDprefix === null) {
            $this->setPrefix('clTRID');
        } else if ($this->ContactPrefix === null) {
            $this->setPrefix('handle');
        }
        if (true === $status) {
            // setup default time zone
            if (isset($this->$timezone)) {
                date_default_timezone_set($this->timezone);
            } else {
                date_default_timezone_set("Europe/Rome");
            }
            // configure temporary folder for storing cookies
            $this->cookie_dir = '/tmp';
            // initialize connection
            if ($this->protocol === 'curl') {
                $this->connection = new EppCurl(server: $this->server, port: $this->port, username: $this->username, password: $this->password);
            } elseif ($this->protocol === 'sock') {
                $this->connection = new EppSock(_url: $this->server, _authName: $this->username, _authPass: $this->password);
            }
            if ($this->connection !== null) {
                $this->connection->Headers(action: 'set', headers: $this->headers);
                // set server port
                if (!empty($this->port)) {
                    $this->connection->Port(action: 'set', port: (int) $this->port);
                }
                // set debug filename
                if (!empty($this->debugfile)) {
                    $this->connection->DebugFile(action: 'set', file: $this->debugfile);
                }
                // setup client certificate
                if (!empty($this->certificatefile)) {
                    if (is_readable($this->certificatefile)) {
                        $this->connection->ClientCert(action: 'set', certFile: $this->certificatefile);
                    } else if (is_readable(realpath(dirname(__FILE__) . '/../../' . $this->certificatefile))) {
                        $this->connection->ClientCert(action: 'set', certFile: realpath(dirname(__FILE__) . '/../../' . $this->certificatefile));
                    }
                }
                // setup leaving interface
                if (!empty($this->interface)) {
                    $this->connection->Interface(action: 'set', interface: $this->interface);
                }
            } else {
                throw new EppException('EPP Connection not success');
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
     * Set ClTRID Prefix
     *
     * @param string|null $prefix
     * @return mixed
     */
    public function setPrefix(?string $prefix = null)
    {
        if ($prefix !== null) {
            $prefix = $prefix . 'prefix';
            $this->$prefix = $prefix;
            return true;
        } else {
            return false;
        }
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
            $this->clTRID = $this->clTRIDprefix . "-" . time() . "-" . substr(md5(rand()), 0, 5);
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
