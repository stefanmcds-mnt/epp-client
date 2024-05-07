<?php

/**
 * An abstract class for EPP Class.
 *
 * @category EPPClient
 * @package EPPAbstract
 * @author STEF@N MCDS S.a.s. <info@stefan-mcds.it>
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 * @version 1.0
 */

namespace EppClient;

use EppClient\EppException;
use EppClient\Traits\EppDomXML;
use EppClient\Traits\EppTree;
use EppClient\Traits\Parse\Response;

abstract class EppAbstract
{
    // Traits to use
    use EppDomXML;
    use Response;
    use EppTree;

    /**
     * Log Level
     *
     * @var array|null
     */
    public array $LogDebug = [
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

    /**
     * Trues Case
     *
     * @var array|null
     */
    public ?array $trues = ["true", "true", 1];

    /**
     * Falses Case
     *
     * @var array|null
     */
    public ?array $falses = ["false", "false", 0, null];

    /**
     * WrongValue returned by ParseResponseBody
     *
     * @var array|null
     */
    public ?array $wrongValue = null; /* Epp Response Result on element value extcon*/

    /**
     * Command Accepted
     *
     * @var array|null
     */
    public ?array $command = ['hello', 'check', 'info', 'create', 'update', 'transfer', 'fetch'];

    /**
     * Global Var where are stored element for
     * populate database
     *
     * @var array|null
     */
    public ?array $storage = null;

    /**
     * Called Class
     *
     * @var string|null
     */
    public ?string $class = null; /* string report the called class */

    /**
     * Default debug level
     *
     * @var string|null
     */
    public ?string $debug = '4'; /* default log level LOG_WARNING */

    /**
     * Epp Response Result
     *
     * @var string|null
     */
    public ?string $svCode = null; /* Epp Response Result Code */
    public ?string $svMsg = null;  /* Epp Response Result MSG */

    /**
     * Epp Response svTRID
     *
     * @var string|null
     */
    public ?string $svTRID = null;
    public ?array $registry = [];

    /**
     * XML Vars returned by $thic->connection
     *
     * @var mixed|null
     */
    public ?string $xmlQuery = null; /* xmlQuery to send Epp Server */
    public mixed $xmlResult = null; /* the ParseResponseBody() result of $xmlResponse['body'] */
    public mixed $xmlResponse = null; /* the xmlResopnse of $xmlQuery */

    /**
     * Use SECDNS
     *      *
     * @var bool|null
     */
    public ?bool $dnssec = false;

    /**
     * Constructor
     *
     * @param object|null $client
     * @param boolean|null $storage
     */
    public function __construct(
        protected ?EppConnection $connection,
        protected ?bool $tostore = true
    ) {
    }

    /**
     * Magic __get
     *
     * @param mixed $name
     * @return void
     */
    public function __get(mixed $name)
    {
        if (method_exists($this, ($method = 'get_' . $name))) {
            return $this->$method();
        } else {
            return;
        }
    }

    /**
     * Magic __isset
     *
     * @param mixed $name
     * @return boolean
     */
    public function __isset(mixed $name)
    {
        if (method_exists($this, ($method = 'isset_' . $name))) {
            return $this->$method();
        } else return;
    }

    /**
     * Magic __set
     *
     * @param mixed $name
     * @param mixed $value
     */
    public function __set(mixed $name, mixed $value)
    {
        if (method_exists($this, ($method = 'set_' . $name))) {
            $this->$method($value);
        }
    }

    /**
     * Magic __unset
     *
     * @param mixed $name
     */
    public function __unset(mixed $name)
    {
        if (method_exists($this, ($method = 'unset_' . $name))) {
            $this->$method();
        }
    }

    /**
     * Parse $this->xmlQuery result
     *
     * XML2Array class transform DOMXML Object into an array
     *
     * The result is parsed and return an array for purpose
     * into EppContact EppDomain and EppSession class
     *
     * @param string|null $element needed for dns check
     * @return array
     */
    private function ParseResponseBody(?string $xml, ?string $element = null)
    {
        // Set $xmlResult
        return $this->_Tree($this->_ParseResponseBody(xml: $xml, element: $element));
    }

    /**
     * initialize ClassVars values
     *
     * @param array|object|null $fields
     */
    public function initValues()
    {
        $class = explode('\\', get_class($this));
        $class = strtolower(str_replace(['EPP', 'CLASS'], '', strtoupper(end($class))));
        $classVars = $class . 'Vars';
        if (isset($this->$classVars) && is_array($this->$classVars)) {
            foreach ($this->$classVars as $key => $value) {
                $this->$classVars[$key] = null;
            }
        }
    }

    /**
     * restrict access to variables, so we can keep track of changes to them
     *
     * @access public
     * @param string $var variable name
     * @param mixed $val value to be set
     *
     * @return mixed value set or false if variable name does not exist
     */
    public function set(?string $var, mixed $val)
    {
        $class = explode('\\', get_class($this));
        $class = strtolower(str_replace(['EPP', 'CLASS', 'epp', 'class'], '', strtoupper(end($class))));
        $classVars = $class . 'Vars';
        // in version 5.2.3 the 4th parameter "double_encode" was added
        if (null !== $val) {
            if (PHP_VERSION_ID < 50203) {
                $val = htmlspecialchars($val, ENT_COMPAT, 'UTF-8');
            } else {
                $val = htmlspecialchars($val, ENT_COMPAT, 'UTF-8', false);
            }
        }
        if (isset($this->$classVars[$var])) {
            if ($this->$classVars[$var] !== $val) {
                $this->$classVars[$var] = $val;
            }
        } else {
            $this->$classVars[$var] = $val;
        }
        print_r($this->$classVars[$var]);
    }

    /**
     * get a single variable/setting from class
     *
     * @access public
     * @param string variable name
     * @return mix value of variable
     */
    public function get(?string $var)
    {
        $class = explode('\\', get_class($this));
        $class = strtolower(str_replace(['EPP', 'CLASS'], '', strtoupper(end($class))));
        $classVars = $class . 'Vars';
        return $this->$classVars[$var];
    }

    /**
     * authinfo generator
     *
     * @access public
     * @return string[16] random authinfo code
     */
    public function authinfo(?int $length = 16)
    {
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $authinfo = '';
        for ($i = 0; $i <= $length; $i++) {
            $authinfo .= substr($str, rand(0, strlen($str) - 1), 1);
        }
        return $authinfo;
    }

    /**
     * handle id generator
     *
     * @param $handleprefix
     * @return handle
     */
    public function createHandle(?string $handleprefix)
    {
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $lun = (is_null($handleprefix)) ? 16 : (16 - strlen($handleprefix));
        // attrib a random number to handle
        $re_handle = '';
        for ($i = 0; $i < $lun; $i++) {
            $re_handle .= substr($str, rand(0, strlen($str) - 1), 1);
        }
        return $handleprefix . $re_handle;
    }

    /**
     * set error code and message
     *
     * @access protected
     * @param string error message
     * @param string 4-digit error code
     */
    public function setError(?string $msg, ?string $code = "0000")
    {
        $this->svMsg = $msg;
        $this->svCode = $code;
    }

    /**
     * get error message
     *
     * @access public
     * @return string error message
     */
    public function getError()
    {
        $msg = "";
        // only try to set a message text if we got a EPP error message
        if (!empty($this->svCode)) {
            $msg = " EPP code '" . $this->svCode . "': " . $this->svMsg;
            if (!empty($this->wrongValue)) {
                $msg .= " / extended reason '" . implode(' ', array_values($this->wrongValue));
            }
        }
        if ($this->debug == LOG_DEBUG) {
            $msg = "Generic error (if set):\n -----------------------\n" .
                $msg . "\n\n Query sent to server:\n ---------------------\n" .
                $this->xmlQuery .
                "\n\n Response received from server:\n ------------------------------\n" .
                $this->xmlResponse['body'] . "\n";
        }
        return $msg;
    }

    /**
     * Execute query to EPP server
     *
     * @param mixed|null $clTRType client transaction type
     * @param mixed|null $clTRObject client transaction object
     * @param boolean|null $storage store transaction and response
     * @return boolean status
     */
    public function ExecuteQuery(mixed $clTRType = null, mixed $clTRObject = null, ?bool $storage = null)
    {
        $this->debug >= $this->LogDebug['LOG_DEBUG'];
        $return_code = false;
        try {
            if (is_array($clTRObject)) {
                $clTRObject = implode(';', $clTRObject);
            }
            // send request + parse response
            // the $connection must be exist and $xmlQuery is isset
            // the result query to Epp Server are saved to $xmlResponse
            if ($this->xmlResponse = $this->connection->sendRequest($this->xmlQuery)) {
                if (!empty($this->xmlResponse['error'])) {
                    $return_code = false;
                    throw new EppException(message: $this->xmlResponse['error']);
                }
                if (!empty($this->xmlResponse['body'])) {
                    $this->xmlResult = $this->ParseResponseBody($this->xmlResponse['body']);
                    // look for a server response code
                    // look for a server message
                    if (isset($this->xmlResult['result']['msg'])) {
                        $this->svMsg = $this->xmlResult['result']['msg'];
                    } else {
                        $this->svMsg = null;
                    }
                    // look for a server message code
                    if (isset($this->xmlResult['result']['code'])) {
                        $this->svCode = $this->xmlResult['result']['code'];
                        switch (substr($this->svCode, 0, 1)) {
                            case "1":
                                $return_code = true;
                                break;
                            case "2":
                            default:
                                $return_code = false;
                                break;
                        }
                    }
                    // look for an extended server error message and code
                    if (isset($this->xmlResult['result']['wrongValue'])) {
                        $this->wrongValue = implode(' ', array_values($this->xmlResult['result']['wrongValue']));
                    }
                } else {
                    $this->setError("Unexpected result (no xml response body).");
                    $return_code = false;
                    throw new EppException(message: "Unexpected result (no xml response body).");
                }
                // look for a server transaction ID
                if (isset($this->xmlResult['svTRID'])) {
                    $this->svTRID = $this->xmlResult['svTRID'];
                }
            }
            // store request to array $tostore for purpose as database support
            if ($this->tostore) {
                // store request
                $this->storage[] = [
                    'table' => 'requests', // Model Class
                    'action' => 'create',
                    'data' => [
                        'clTRID' => $this->connection->_clTRID(),
                        'clTRType' => $clTRType,
                        'clTRObject' => $clTRObject,
                        'clTRData' => $this->xmlQuery,
                        'createdTime' => null
                    ]
                ];
                // store response
                $this->storage[] = [
                    'table' => 'responses', // Model Class
                    'action' => 'create',
                    'data' => [
                        'clTRID' => $this->connection->_clTRID(),
                        'svTRID' => $this->svTRID,
                        'svCode' => $this->svCode,
                        'status' => 0,
                        'svHTTPCode' => $this->xmlResponse['status'],
                        'svHTTPHeaders' => $this->xmlResponse['headers'],
                        'svHTTPData' => $this->xmlResponse['body'],
                        'extValueReasonCode' => (null !== $this->wrongValue) ? $this->wrongValue['code'] : null,
                        'extValueReason' => (null !== $this->wrongValue) ? $this->wrongValue['reason'] : null,
                        'createdTime' => null
                    ],
                ];
            }
            return $return_code;
        } catch (EppException $e) {
            throw new EppException($e->getMessage());
        }
    }

    /**
     * check for possible values of true
     */
    public function isTrue(mixed $val)
    {
        if ($val === true) {
            return true;
        }
        if ((string) $val == "1") {
            return true;
        }
        if (strtoupper($val) === "true") {
            return true;
        }
        return false;
    }
}
