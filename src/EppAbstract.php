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
use DOMDocument;
use Utilita\XML2Array;

abstract class EppAbstract
{
    /* LOG LEVEL */
    public ?array $LogDebug = [
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
    public ?array $trues = ["true", "true", 1];
    public ?array $falses = ["false", "false", 0, null];
    public ?array $wrongValue = null; /* Epp Response Result on element value extcon*/
    public ?array $command = ['hello', 'check', 'info', 'create', 'update', 'transfer', 'fetch'];
    public ?array $storage = null;
    public ?EppDomXML $dom; /* DOMDocument */
    public ?string $class; /* string report the called class */
    public ?string $debug = '4'; /* default log level LOG_WARNING */
    public ?string $xmlQuery = null; /* xmlQuery to send Epp Server */
    public ?string $svCode = null; /* Epp Response Result Code */
    public ?string $svMsg = null;  /* Epp Response Result MSG */
    public ?string $svTRID = null; /* Epp Response svTRID */
    //public ?bool $tostore = true; /* boolean for return array to store into database */
    public mixed $registry = null;
    public mixed $EPPcfg = null;
    public mixed $xmlResult = null; /* xml response object */
    public mixed $response = null;
    public mixed $ResultResponse = null;

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
     * Tree array or object
     *
     * @param mixed $var
     * @return array
     */
    public function tree(mixed $var)
    {
        if (is_object($var)) {
            $var = json_decode(json_encode($var), TRUE);
        }
        $array = [];
        foreach ($var as $key => $value) {
            if (is_array($value) && is_object($value)) {
                $this->tree($value);
            }
            if (stristr($key, ':')) {
                $key = explode(':', $key);
                $key = end($key);
            }
            $array[$key] = $value;
        }
        return $array;
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
    public function ParseResponseBody(?string $xml, ?string $element = null)
    {
        if ($element === 'dns') {
            $ext = 'extdom';
        } else {
            // get element by called class
            $element = explode('\\', get_class($this));
            $element = str_replace('epp', '', strtolower(end($element)));
            //$this->class = get_class($this) . ' ' . $element;
            if ($element === 'domain') {
                $ext = 'extdom';
            }
            if ($element === 'contact') {
                $ext = 'extcon';
            }
            if ($element === 'session') {
                $ext = '';
            }
        }
        // parse into array Epp XML response
        if ($res = XML2Array::createArray($xml)) {
            // set the object properties with the values from the xml response
            if (isset($res['epp']['response'])) {
                $res = $res['epp']['response'];
            } else {
                $res = $res['epp'];
            }
            if (isset($res['result'])) {
                $res['result']['code'] = $res['result']['@attributes']['code'];
                $res['result']['msg'] = $res['result']['msg']['@value'];
                unset($res['result']['@attributes']);
                //$res['result'] = $res['result']['@attributes']['code'] . '/' . $res['result']['msg']['@value'];
                if (isset($res['result']['value'])) {
                    $res['result']['wrongValue']['element'] = $res['result']['value']['extepp:wrongValue']['extepp:element'];
                    $res['result']['wrongValue']['value'] = $res['result']['value']['extepp:wrongValue']['extepp:value'];
                    $res['result']['wrongValue']['code'] = $res['result']['extValue']['value']['extepp:reasonCode'];
                    $res['result']['wrongValue']['reason'] = $res['result']['extValue']['reason']['@value'];
                    unset($res['result']['value']);
                    unset($res['result']['extValue']);
                }
            }

            if (isset($res['msgQ'])) {
                $res['msgQ'] = [
                    'date' => $res['msgQ']['qDate'],
                    'msg' => $res['msgQ']['msg']['@value'],
                    'id' => $res['msgQ']['@attributes']['id'],
                    'count' => $res['msgQ']['@attributes']['count'],
                ];
            }
            if (isset($res['greeting'])) {
                unset($res['greeting']['dcp']);
                $res = array_merge([], $res['greeting'], $res['greeting']['svcMenu'], $res['greeting']['svcMenu']['svcExtension']);
                unset($res['svcMenu']);
                unset($res['svcExtension']);
            }

            if (isset($res['resData'])) {
                //tranfer domain
                if (isset($res['resData'][$element . ':trnData'])) {
                    $res['trnData'] = [
                        'trStatus' => $res['resData'][$element . ':trnData'][$element . ':trStatus'],
                        'reID' => $res['resData'][$element . ':trnData'][$element . ':reID'],
                        'reDate' => $res['resData'][$element . ':trnData'][$element . ':reDate'],
                        'acID' => $res['resData'][$element . ':trnData'][$element . ':acID'],
                        'acDate' => $res['resData'][$element . ':trnData'][$element . ':acDate'],
                    ];
                    unset($res['resData'][$element . ':trnData']);
                }
                // infdata
                if (isset($res['resData'][$element . ':infData'])) {
                    $res['resData'] = $res['resData'][$element . ':infData'];
                    if (isset($res['resData'][$element . ':status'])) {
                        $res['resData'][$element . ':status'] = $res['resData'][$element . ':status']['@attributes']['s'];
                    }
                    if (isset($res['resData'][$element . ':contact'])) {
                        $p = null;
                        foreach ($res['resData'][$element . ':contact'] as $key => $item) {
                            if ($item['@attributes']['type'] === 'tech') {
                                $p[$item['@attributes']['type']][] = $item['@value'];
                            } else {
                                $p[$item['@attributes']['type']] = $item['@value'];
                            }
                            unset($res['resData'][$element . ':contact'][$key]);
                        }
                        $res['resData'][$element . ':contact'] = $p;
                    }
                    if (isset($res['resData'][$element . ':ns'])) {
                        $res['resData'][$element . ':ns'] = $res['resData'][$element . ':ns'][$element . ':hostAttr'];
                        $p = null;
                        foreach ($res['resData'][$element . ':ns'] as $key => $item) {
                            $p[] = [
                                'hostName' => $item[$element . ':hostName'],
                                'hostAddr' => $item[$element . ':hostAddr']['@value'],
                                'ip' => $item[$element . ':hostAddr']['@attributes']['ip']
                            ];
                        }
                        $res['resData'][$element . ':ns'] = $p;
                    }
                    if (isset($res['resData'][$element . ':authInfo'])) {
                        $res['resData'][$element . ':authInfo'] = $res['resData'][$element . ':authInfo'][$element . ':pw'];
                    }
                    if (isset($res['resData'][$element . ':postalInfo'])) {
                        unset($res['resData'][$element . ':postalInfo']['@attributes']);
                        $p = array_merge(
                            $res['resData'][$element . ':postalInfo'],
                            $res['resData'][$element . ':postalInfo'][$element . ':addr']
                        );
                        unset($p[$element . ':addr']);
                        unset($res['resData'][$element . ':postalInfo']);
                        $res = array_merge($res, $res['resData'], $p);
                    }
                    if (isset($res['resData'][$element . ':voice'])) {
                        $res['resData']['voice'] = (null !== $res['resData'][$element . ':voice']['@attributes']['x'])
                            ? $res['resData'][$element . ':voice']['@value'] . ' int. ' . $res['resData'][$element . ':voice']['@attributes']['x']
                            : $res['resData'][$element . ':voice']['@value'];
                        unset($res['resData'][$element . ':voice']);
                        unset($res[$element . ':voice']);
                    }
                    if (isset($res['resData'][$element . ':fax'])) {
                        $res['resData']['fax'] = (null !== $res['resData'][$element . ':fax']['@attributes']['x'])
                            ? $res['resData'][$element . ':fax']['@value'] . ' int. ' . $res['resData'][$element . ':fax']['@attributes']['x']
                            : $res['resData'][$element . ':fax']['@value'];
                        unset($res['resData'][$element . ':fax']);
                        unset($res[$element . ':fax']);
                    }
                    if (isset($res['resData'][$element . ':chkData'])) {
                        foreach ($res['resData'][$element . ':chkData'][$element . ':cd'] as $aa) {
                            // contact
                            if (isset($aa[$element . ':id'])) {
                                $p[] = [
                                    'id' => $aa[$element . ':id']['@value'],
                                    'avail' => $aa[$element . ':id']['@attributes']['avail'],
                                ];
                            }
                            // domain
                            if (isset($aa[$element . ':name'])) {
                                if (isset($aa[$element . ':reason'])) {
                                    $p[] = [
                                        'name' => $aa[$element . ':name']['@value'],
                                        'avail' => $aa[$element . ':name']['@attributes']['avail'],
                                        'reason' => $aa[$element . ':reason']['@value'],
                                    ];
                                } else {
                                    $p[] = [
                                        'name' => $aa[$element . ':name']['@value'],
                                        'avail' => $aa[$element . ':name']['@attributes']['avail'],
                                    ];
                                }
                            }
                        }
                        $res['resData'][$element] = $p;
                        unset($res['resData'][$element . ':chkData']);
                    }
                    // create
                    if (isset($res['resData'][$element . ':creData'])) {
                        if (isset($res['resData'][$element . ':creData']['id'])) {
                            $res['resData']['id'] = $res['resData'][$element . ':creData']['id'];
                        }
                        if (isset($res['resData'][$element . ':creData']['name'])) {
                            $res['resData']['name'] = $res['resData'][$element . ':creData']['name'];
                        }
                        if (isset($res['resData'][$element . ':creData']['exDate'])) {
                            $res['resData']['exDate'] = $res['resData'][$element . ':creData']['exDate'];
                        }
                        $res['resData']['crData'] = $res['resData'][$element . ':creData']['crDate'];
                        unset($res['resData'][$element . ':creData']);
                    }
                }
                $res = array_merge($res, $res['resData']);
                unset($res['resData']);
                // create
            }


            if (isset($res['extension'])) {
                if (isset($res['extension'][$ext . '::creditMsgData'])) {
                    $res['credit'] = $res['extension'][$ext . '::creditMsgData'][$ext . ':credit'];
                }
                if (isset($res['extension'][$ext . ':trade'])) {
                    $res['trnData']['newRegistrant'] = $res['extension'][$ext . ':trade'][$ext . ':transferTrade'][$ext . ':newRegistrant'];
                    if (isset($res['extension'][$ext . ':trade'][$ext . ':transferTrade'][$ext . ':newAuthInfo'])) {
                        $res['trnData']['newAuthInfo'] = $res['extension'][$ext . ':trade'][$ext . ':transferTrade'][$ext . ':newAuthInfo'][$ext . ':pw'];
                    }
                    unset($res['extension'][$ext . ':trade']);
                }
                if (isset($res['extension'][$ext . ':infContactsData'])) {
                    $p = null;
                    if ($res['extension'][$ext . ':infContactsData'][$ext . ':registrant']) {
                        $p['registrant'] = array_merge(
                            $res['extension'][$ext . ':infContactsData'][$ext . ':registrant'][$ext . ':infContact'],
                            $res['extension'][$ext . ':infContactsData'][$ext . ':registrant'][$ext . ':extInfo']
                        );
                        $a = null;
                        foreach ($p['registrant']['contact:status'] as $item) {
                            $a[] = $item['@attributes']['s'];
                        }
                        $p['registrant']['contact:status'] = implode('/', $a);
                        $p['registrant']['contact:voice'] = (null !== $p['registrant']['contact:voice']['@attributes']['x'])
                            ? $p['registrant']['contact:voice']['@value'] . ' int. ' . $p['registrant']['contact:voice']['@attributes']['x']
                            : $p['registrant']['contact:voice']['@value'];
                        $p['registrant']['contact:fax'] =  (null !== $p['registrant']['contact:fax']['@attributes']['x'])
                            ? $p['registrant']['contact:fax']['@value'] . ' int. ' . $p['registrant']['contact:fax']['@attributes']['x']
                            : $p['registrant']['contact:fax']['@value'];
                        unset($p['registrant']['contact:postalInfo']['@attributes']);
                        $p['registrant'] = array_merge(
                            $p['registrant'],
                            $p['registrant']['contact:postalInfo'],
                            $p['registrant']['contact:postalInfo']['contact:addr'],
                            $p['registrant']['extcon:registrant']
                        );
                        unset($p['registrant']['contact:addr']);
                        unset($p['registrant']['contact:postalInfo']);
                        unset($p['registrant']['extcon:registrant']);
                        $p['registrant'] = $this->tree($p['registrant']);
                    }
                    if ($res['extension'][$ext . ':infContactsData'][$ext . ':contact']) {
                        $z = 0;
                        foreach ($res['extension'][$ext . ':infContactsData'][$ext . ':contact'] as $key => $item) {
                            if ($item['@attributes']['type'] === 'tech') {
                                $p['tech'][] = array_merge(
                                    $item[$ext . ':infContact'],
                                    $item[$ext . ':extInfo']
                                );
                                $a = null;
                                foreach ($p['tech'][$z]['contact:status'] as $aa) {
                                    $a[] = $aa['@attributes']['s'];
                                }
                                $p['tech'][$z]['contact:status'] = implode('/', $a);
                                $p['tech'][$z]['contact:voice'] = (null !== $p['tech'][$z]['contact:voice']['@attributes']['x'])
                                    ? $p['tech'][$z]['contact:voice']['@value'] . ' int. ' . $p['tech'][$z]['contact:voice']['@attributes']['x']
                                    : $p['tech'][$z]['contact:voice']['@value'];
                                $p['tech'][$z]['contact:fax'] = (null !== $p['tech'][$z]['contact:fax']['@attributes']['x'])
                                    ? $p['tech'][$z]['contact:fax']['@value'] . ' int. ' . $p['tech'][$z]['contact:fax']['@attributes']['x']
                                    : $p['tech'][$z]['contact:fax']['@value'];
                                unset($p['tech'][$z]['contact:postalInfo']['@attributes']);
                                $p['tech'][$z] = array_merge(
                                    $p['tech'][$z],
                                    $p['tech'][$z]['contact:postalInfo'],
                                    $p['tech'][$z]['contact:postalInfo']['contact:addr'],
                                    //$p['tech'][$z]['extcon:registrant']
                                );
                                unset($p['tech'][$z]['contact:addr']);
                                unset($p['tech'][$z]['contact:postalInfo']);
                                unset($p['tech'][$z]['extcon:registrant']);
                                $p['tech'][$z] = $this->tree($p['tech'][$z]);
                                $z++;
                            } else if ($item['@attributes']['type'] === 'admin') {
                                $p['admin'] = array_merge(
                                    $item[$ext . ':infContact'],
                                    $item[$ext . ':extInfo']
                                );
                                $a = null;
                                foreach ($p['admin']['contact:status'] as $aa) {
                                    $a[] = $aa['@attributes']['s'];
                                }
                                $p['admin']['contact:status'] = implode('/', $a);
                                $p['admin']['contact:voice'] = (null !== $p['admin']['contact:voice']['@attributes']['x'])
                                    ? $p['admin']['contact:voice']['@value'] . ' int. ' . $p['admin']['contact:voice']['@attributes']['x']
                                    : $p['admin']['contact:voice']['@value'];
                                $p['admin']['contact:fax'] = (null !== $p['admin']['contact:fax']['@attributes']['x'])
                                    ? $p['admin']['contact:fax']['@value'] . ' int. ' . $p['admin']['contact:fax']['@attributes']['x']
                                    : $p['admin']['contact:fax']['@value'];
                                unset($p['admin']['contact:postalInfo']['@attributes']);
                                $p['admin'] = array_merge(
                                    $p['admin'],
                                    $p['admin']['contact:postalInfo'],
                                    $p['admin']['contact:postalInfo']['contact:addr'],
                                    $p['admin']['extcon:registrant']
                                );
                                unset($p['admin']['contact:addr']);
                                unset($p['admin']['contact:postalInfo']);
                                unset($p['admin']['extcon:registrant']);
                                $p['admin'] = $this->tree($p['admin']);
                            }
                        }
                    }
                    $res[$element . ':infContacts'] = $p;
                    unset($res['extension'][$ext . ':infContactsData']);
                    if (isset($res['extension'][$ext . ':infData'])) {
                        if (isset($res['extension'][$ext . ':infData'][$ext . ':ownStatus'])) {
                            $res[$element . ':ownStatus'] = $res['extension'][$ext . ':infData'][$ext . ':ownStatus']['@attributes']['s'];
                            unset($res['extension'][$ext . ':infData'][$ext . ':ownStatus']);
                        }
                        if (isset($res['extension'][$ext . ':infData'][$ext . ':consentForPublishing'])) {
                            $res[$element . ':consentForPublishing'] = $res['extension'][$ext . ':infData'][$ext . ':consentForPublishing'];
                            unset($res['extension'][$ext . ':infData'][$ext . ':consentForPublishing']);
                        }
                        if (isset($res['extension'][$ext . ':infData'][$ext . ':registrant'])) {
                            $p = $res['extension'][$ext . ':infData'][$ext . ':registrant'];
                            $res = array_merge($res, $p);
                            unset($res['extension'][$ext . ':infData'][$ext . ':registrant']);
                        }
                        unset($res['extension'][$ext . ':infData']);
                    }
                    if (isset($res['extension'][$ext . ':infNsToValidateData'])) {
                        $res[$element . ':nsToValidate'] = $res['extension'][$ext . ':infNsToValidateData'][$ext . ':nsToValidate'][$element . ':hostAttr'];
                        foreach ($res[$element . ':nsToValidate'] as $aa) {
                            $bb[] = $aa[$element . ':hostName'];
                        }
                        $res[$element . ':nsToValidate'] = $bb;
                        unset($res['extension'][$ext . ':infNsToValidateData']);
                    }
                    if (isset($res['extension'][$ext . ':chgStatusMsgData'])) {
                        $res[$element . ':chgStatusMsgData'] = [
                            'name' => $res['extension'][$ext . ':chgStatusMsgData'][$ext . ':name'],
                            'targetStatus' => [
                                'status' => $res['extension'][$ext . ':chgStatusMsgData'][$ext . ':targetStatus'][$element . ':status']['@attributes']['s'],
                                'rgpStatus' => $res['extension'][$ext . ':chgStatusMsgData'][$ext . ':targetStatus']['rgp:rgpStatus']['@attributes']['s'],
                            ]
                        ];
                        unset($res['extension'][$ext . ':chgStatusMsgData']);
                    }
                    if (isset($res['extension'][$ext . ':dnsErrorMsgData'])) {
                        $ele = $res['extension'][$ext . ':dnsErrorMsgData'];
                        $ns = $ele[$ext . ':nameservers'][$ext . ':nameserver'];
                        $test = $ele[$ext . ':tests'][$ext . ':test'];
                        $qry = $ele[$ext . ':queries'][$ext . ':query'];
                        foreach ($ns as $aa) {
                            $p[$aa['@attributes']['name']] = [
                                'ip' => $aa[$ext . ':address']['@value'],
                                'type' => $aa[$ext . ':address']['@attributes']['type'],
                            ];
                            foreach ($test as $ts) {
                                if (isset($ts[$ext . ':nameserver'])) {
                                    foreach ($ts[$ext . ':nameserver'] as $ts1) {
                                        if ((isset($ts1[$ext . ':detail']))) {
                                            $dtv = (array) $ts1[$ext . ':detail'];
                                        }
                                        $p[$ts1['@attributes']['name']][$ts['@attributes']['name']] = (isset($dtv))
                                            ? $ts1['@attributes']['status'] . ' ' . $dtv['@value']
                                            : $ts1['@attributes']['status'];
                                    }
                                }
                            }
                        }
                        $ele = [
                            'domain' => $ele[$ext . ':domain'],
                            'status' => $ele[$ext . ':status'],
                            'id' => $ele[$ext . ':validationId'],
                            'data' => $ele[$ext . ':validationDate'],
                            'nameservers' => $p,
                        ];
                        $res = array_merge($res, $ele);
                    }
                    unset($res['extension']);
                }
            }
            if (isset($res['trID'])) {
                $res = array_merge($res, $res['trID']);
                unset($res['trID']);
            }            //$this->ResultResponse = $this->tree($res);
            $this->xmlResult = $this->tree($res);
        } else {
            throw new EppException('Invalid response from server');
        }
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
        $class = strtolower(str_replace(['EPP', 'CLASS'], '', strtoupper(end($class))));
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
                $this->response['body'] . "\n";
        }
        return $msg;
    }

    /**
     * Execute query to EPP server
     *
     * @param mixed|null $clTRType client transaction type
     * @param string|null $clTRObject client transaction object
     * @param boolean|null $storage store transaction and response
     * @return boolean status
     */
    public function ExecuteQuery(mixed $clTRType = null, ?string $clTRObject = null, ?bool $storage = null)
    {
        $this->debug >= $this->LogDebug['LOG_DEBUG'];
        $return_code = false;
        try {
            if (is_array($clTRObject)) {
                $clTRObject = implode(';', $clTRObject);
            }
            // send request + parse response
            if ($this->response = $this->connection->sendRequest($this->xmlQuery)) {
                //if ($this->xmlResult = $this->ParseResponseBody($this->connection->parseResponse())) {
                if ($this->ParseResponseBody($this->response['body'])) {
                    // look for a server response code
                    //if (isset($this->xmlResult['result'])) {
                    // look for a server message
                    if (isset($this->xmlResult['result']['msg'])) {
                        $this->svMsg = $this->xmlResult['result']['msg'];
                    } else {
                        $this->svMsg = null;
                    }
                    // look for a server message code
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
                    // look for an extended server error message and code
                    if (isset($this->xmlResult['result']['wrongValue'])) {
                        $this->wrongValue = implode(' ', array_values($this->xmlResult['result']['wrongValue']));
                    }
                } else {
                    $this->setError("Unexpected result (no xml response code).");
                    $return_code = false;
                }
                // look for a server transaction ID
                if (isset($this->xmlResult['svTRID'])) {
                    $this->svTRID = $this->xmlResult['svTRID'];
                }
            }
            // store request
            if ($this->tostore) {
                $this->storage[] = [
                    'table' => 'EppRequests', // Model Class
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
                    'table' => 'EppResponses', // Model Class
                    'action' => 'create',
                    'data' => [
                        'clTRID' => $this->connection->_clTRID(),
                        'svTRID' => $this->svTRID,
                        'svCode' => $this->svCode,
                        'status' => 0,
                        'svHTTPCode' => $this->response['status'],
                        'svHTTPHeaders' => $this->response['headers'],
                        'svHTTPData' => $this->response['body'],
                        'extValueReasonCode' => (null !== $this->wrongValue) ? $this->wrongValue['code'] : null,
                        'extValueReason' => (null !== $this->wrongValue) ? $this->wrongValue['reason'] : null,
                        'createdTime' => null
                    ],
                ];
            }
            return $return_code;
        } catch (EppException $e) {
            throw new EppException($e);
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
