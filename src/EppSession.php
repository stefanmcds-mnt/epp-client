<?php

/**
 * A simple class handling EPP sessions.
 *
 * Available methods:
 * - hello
 * - login
 * - keepalive
 * - logout
 * - pollID
 * - pollMessageCount
 * - poll
 * - showCredit
 *
 *
 * @category EppClient
 * @package EppSession
 * @author STEF@N MCDS S.a.s. <info@stefan-mcds.it>
 * @license MIT
 *
 */

namespace EppClient;

use EppClient\EppAbstract;
use EppClient\EppConnection;
use EppClient\EppException;

class EppSession extends EppAbstract
{

    // array for use to XML structure to epp server
    public ?array $sessionVars = [
        'clID' => null,
        'pw' => null,
        'newpw' => null,
        'msgID' => null,
        'msgIDold' => null,
        'credit' => null,
        'msgTOT' => null,
        'msgTitle' => null,
        'msgDate' => null,
        'type' => null
    ];

    /**
     * Class constructor
     *
     * (initializes authinfo)
     *
     * @param EppConnection connection class
     * @param $storage to db store
     */
    public function __construct(
        protected ?EppConnection $connection,
        protected ?bool $tostore = true
    ) {
        parent::__construct(connection: $this->connection, tostore: $this->tostore);
        $this->initValues();
        $this->sessionVars['clID'] = $this->connection->username;
        $this->sessionVars['pw'] = $this->connection->password;
    }

    /**
     * Set Registry
     * 
     * set the Registry object
     * 
     * return an array such us
     * 
     *  [
     *      'svID' => NIC-IT EPP Registry,
     *      'scDate' => 2013-02-22,
     *      'version' => 1.0,
     *      'lang' => [
     *          0 => 'en',
     *          1 => 'it',
     *      ],
     *      'objURI' => [
     *          0 => urn:ietf:params:xml:ns:contact-1.0
     *          1 => urn:ietf:params:xml:ns:domain-1.0
     *      ]
     *      'extURI' => [
     *          0 => http://www.nic.it/ITNIC-EPP/extepp-2.0
     *          1 => http://www.nic.it/ITNIC-EPP/extcon-1.0
     *          2 => http://www.nic.it/ITNIC-EPP/extdom-2.0
     *          3 => urn:ietf:params:xml:ns:rgp-1.0
     *      ],
     *      'contact' =>[
     *         'schema' => [
     *             'xmlns:contact' => urn:ietf:params:xml:ns:contact-1.0
     *             'xsi:schemaLocation' => urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd
     *         ],
     *         'extcon' => [
     *            'xmlns:extcon' => http://www.nic.it/ITNIC-EPP/extcon-1.0
     *             'xsi:schemaLocation' => http://www.nic.it/ITNIC-EPP/extcon-1.0 extcon-1.0.xsd
     *         ],
     *         'rgp' => [
     *             'xmlns:rgp' => urn:ietf:params:xml:ns:rgp-1.0
     *             'xsi:schemaLocation' => urn:ietf:params:xml:ns:rgp-1.0 rgp-1.0.xsd
     *         ],
     *      ],
     *      'domain' => [
     *          'schema' => [
     *          'xmlns:domain' => urn:ietf:params:xml:ns:domain-1.0
     *          'xsi:schemaLocation' => urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd
     *      ],
     *      'extdom' => [
     *          'xmlns:extdom' => http://www.nic.it/ITNIC-EPP/extdom-2.0
     *          'xsi:schemaLocation' => http://www.nic.it/ITNIC-EPP/extdom-2.0 extdom-2.0.xsd
     *      ],
     *      'rgp' => [
     *          'xmlns:rgp' => urn:ietf:params:xml:ns:rgp-1.0
     *          'xsi:schemaLocation' => urn:ietf:params:xml:ns:rgp-1.0 rgp-1.0.xsd
     *      ]
     * ]
     * 
     * @param array|null $registry
     * @return mixed
     */
    public function setRegistry(?array $registry = [])
    {
        $this->registry = $registry;
        foreach ($this->registry['objURI'] as $objURI) {
            $a = explode(':', $objURI);
            $b = explode('-', end($a));
            $this->registry[reset($b)] = [
                'xmlns:' . reset($b) => $objURI,
                'xsi:schemaLocation' => $objURI . ' ' . end($a) . '.xsd',
            ];
            foreach ($this->registry['extURI'] as $extURI) {
                if (stristr($extURI, 'http')) {
                    $c = explode('/', $extURI);
                } else if (stristr($extURI, ':')) {
                    $c = explode(':', $extURI);
                }
                $d = explode('-', end($c));
                if (stristr(reset($d), substr(reset($b), 0, 3)) || in_array(reset($d), ['rgp'])) {
                    $this->registry[reset($b)][reset($d)] = [
                        'xmlns:' . reset($d) => $extURI,
                        'xsi:schemaLocation' => $extURI . ' ' . end($c) . '.xsd',
                    ];
                }
            }
        }
        unset($this->registry['greeting']);
        return $this->registry;
    }

    /**
     * session start
     *
     * @access public
     * @return boolean status
     */
    public function Hello()
    {
        $this->xmlQuery = EppDomXML::_Hello();
        // query server (will return false)
        $this->ExecuteQuery(clTRType: "hello", storage: true);
        print_r($this->xmlResult);
        // Set de EppDomXML $registro var
        EppDomXML::_setRegistry($this->setRegistry($this->xmlResult));
        $this->sessionVars = array_merge($this->sessionVars, $this->xmlResult);
        // this is the only query with no result code
        /*
        if ((substr($this->xmlResult['code'], 0, 1) === "2")) {
            return true;
        } else {
            return false;
        }
        */
    }

    /**
     * session login/logout background method
     *
     * @access private
     * @param string login/logout
     * @return mix status (false or EPP status code)
     */
    private function loginout($which)
    {
        // query server
        //if ($this->ExecuteQuery(clTRType: $which, clTRObject: $which, storage: true)) {
        // see if we got the expected information
        $this->ExecuteQuery(clTRType: $which, clTRObject: $which, storage: true);
        if (isset($this->xmlResult['creditMsgData'])) {
            $this->sessionVars['credit'] = $this->xmlResult['creditMsgData'];
        }
        $this->sessionVars = array_merge($this->sessionVars, $this->xmlResult);
        return true;
        //} else {
        //    $this->logout();
        //    return false;
        //}
    }

    /**
     * session login
     *
     * @access public
     * @param string optional new password
     * @return mix status (false or EPP status code)
     */
    public function Login($newPW = null)
    {
        $this->sessionVars['newpw'] = (null !== $newPW) ? $newPW : null;
        $this->xmlQuery = EppDomXML::_Login($this->sessionVars);
        if (!empty($this->xmlQuery)) {
            return $this->loginout("login");
        } else {
            return false;
        }
    }

    /**
     * session keepalive
     *
     * @access public
     * @return boolean status
     */
    public function KeepAlive()
    {
        return $this->hello();
    }

    /**
     * session logout
     *
     * @access public
     * @return mix status (false or EPP status code)
     */
    public function Logout()
    {
        $this->xmlQuery = EppDomXML::_Logout();
        return $this->loginout("logout");
    }

    /**
     * return current message ID
     * if queue has not yet been looked at, we are going to poll it once
     *
     * @access public
     * @return integer message ID on top of message stack
     */
    public function PollID()
    {
        $this->Poll(false, 'req', null);
        $this->sessionVars = array_merge($this->sessionVars, $this->xmlResult);
        return (int) $this->sessionVars['msgID'];
    }

    /**
     * check number of messages in polling queue
     * if queue has not yet been looked at, we are going to poll it once
     *
     * @access public
     * @return integer amount of messages in queue
     */
    public function PollMessageCount()
    {
        $this->Poll(false, 'req', null);
        $this->sessionVars = array_merge($this->sessionVars, $this->xmlResult);
        return (int) $this->sessionVars['msgTOT'];
    }

    /**
     * poll message queue
     *
     * @access public
     * @param string polling type (defaults to "req")
     * @param string message ID (default to empty)
     * @return boolean status
     */
    public function Poll(
        ?bool $store = true,
        ?string $type = "req",
        ?string $msgID = null
    ) {
        switch (strtolower($type)) {
            case "req":
                break;
            case "ack":
                if (empty($msgID)) {
                    $this->setError("Polling of type 'ack' requires a message ID to be set!");
                    return false;
                }
                break;
            default:
                $this->setError("Polling of type '" . $type . "' not supported, choose one of 'req' or 'ack'.");
                return false;
                break;
        }
        $this->sessionVars['type'] = $type;
        $this->sessionVars['msgID'] = $msgID;
        if ($this->xmlQuery = EppDomXML::_Poll(['sessionvar' => $this->sessionVars, 'clTRID' => $this->connection->_clTRID(action: 'set')])) {
            // query server
            if ($this->ExecuteQuery(clTRType: "poll", clTRObject: "poll", storage: true)) {
                // look at message counter
                if (isset($this->xmlResult['msgQ'])) {
                    $this->sessionVars['msgTOT'] = (int) $this->xmlResult['msgQ']['count'];
                    $this->sessionVars['msgID'] = (int) $this->xmlResult['msgQ']['id'];
                    $this->sessionVars['msgTitle'] = (string) $this->xmlResult['msgQ']['msg'];
                    $this->sessionVars['msgDate'] = (string) $this->xmlResult['msgQ']['date'];
                    // parse message (only in case of a poll "req") and store it
                    if ((strtolower($type) === "req") && ($store === true)) {
                        $tmp = $this->parsePollReq();
                        $this->storage[] = [
                            'table' => 'epp_messages',
                            'data' => [
                                'idmsg' => $this->sessionVars['msgID'],
                                'title' => $this->sessionVars['msgTitle'],
                                'type' => $tmp['type'],
                                'domain' => (is_null($tmp['domain']) || $tmp['domain'] === '') ? null : $tmp['domain'],
                                'data' => $tmp['data'],
                                'clTRID' => $this->connection->_clTRID(),
                                'svTRID' => $this->svTRID,
                                'archivedUserID' => '1',
                                'archived' => '0',
                                'archivedTime' => date("Y-m-d", time()),
                                'createdTime' => substr($this->sessionVars['msgDate'], 0, 10),
                                'clTRID' => $this->connection->_clTRID(),
                                'svTRID' => $this->svTRID,
                                'title' => $this->sessionVars['msgTitle'],
                            ],
                            'action' => 'create'
                        ];
                        $this->storage[] = [
                            'table' => 'epp_msgqueues',
                            'data' => [
                                'clTRID' => $this->connection->_clTRID(),
                                'svTRID' => $this->svTRID,
                                'svCode' => $this->svCode,
                                'status' => '0',
                                'svHTTPCode' => $this->result['code'],
                                'svHTTPHeaders' => $this->result['headers'],
                                'svHTTPData' => $this->result['body'],
                            ],
                            'action' => 'create'
                        ];
                    }
                }
            } else {
                throw new EppException('Execute Query Poll has not been sussess!');
            }
        } else {
            throw new EppException('Can not construct xmlQuery!');
        }
        $this->sessionVars = array_merge($this->sessionVars, $this->xmlResult);
        return $this->xmlResult;
    }

    /**
     * method to remove trailing slashes from domain names (dnsErrorMsgData cases)
     *
     * @access protected
     * @param
     *            string domain name
     * @return string domain name
     */
    protected function stripTrailingDots($domain)
    {
        if (substr($domain, strlen($domain) - 1) == ".")
            return substr($domain, 0, strlen($domain) - 1);
        else
            return $domain;
    }

    /**
     * try to parse message received by poll "req"
     *
     *
     * @access protected
     * @param boolean store message to DB (defaults to true)
     * @return array [message type], [domain], [human readable data]
     */
    protected function parsePollReq()
    {
        // passwdReminder
        if (isset($this->xmlResult['passwordReminder'])) {
            return [
                'type' => 'passwdReminder',
                'domain' => null,
                'data' => (string) $this->xmlResult['passwordReminder']
            ];
        }

        // creditMsgData
        if (isset($this->xmlResult['creditMsgData'])) {
            return [
                'type' => 'creditMsgData',
                'domain' => null,
                'data' => (string) $this->xmlResult['creditMsgData']
            ];
        }

        // delayedDebitAndRefundMsgData
        if (isset($this->xmlResult['delayedDebitAndRefundMsgData'])) {
            return [
                'type' => 'delayedDebitAndRefundMsgData',
                'domain' => null,
                'data' => (string) $this->xmlResult['delayedDebitAndRefundMsgData']
            ];
        }

        // simpleMsgData
        if (isset($this->xmlResult['simpleMsgData'])) {
            return [
                'type' => 'simpleMsgData',
                'domain' => $this->xmlResult['simpleMsgData'],
                'data' => (string) $this->xmlResult['msgQ']['msg']
            ];
        }

        // dnsErrorMsgData
        if (isset($this->xmlResult['dnsErrorMsgData'])) {
            $msg = [];
            foreach ($this->xmlResult['dnsErrorMsgData']['nameservers'] as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $item) {
                        $msg[] = "{$key}[{$k}]={$item}";
                    }
                }
            }
            return [
                'type' => 'dnsErrorMsgData',
                'domain' => $this->stripTrailingDots($this->xmlResult['domain']),
                'data' => $this->xmlResult['msgQ']['msg'] . " (" . implode(", ", $msg) . ")"
            ];
        }

        // chgStatusMsgData
        if (isset($this->xmlResult['chgStatusMsgData'])) {
            return [
                'type' => 'chgStatusMsgData',
                'domain' => $this->stripTrailingDots($this->xmlResult['chgStatusMsgData']['name']),
                'data' => $this->xmlResult['msgQ']['msg']
                    . " ("
                    . $this->xmlResult['chgStatusMsgData']['name']['targetStatus']['status']
                    . ','
                    . $this->xmlResult['chgStatusMsgData']['name']['targetStatus']['rgpStatus']
                    . ")"
            ];
        }

        // dlgMsgData
        if (isset($this->xmlResult['dlgMsgData'])) {
            if (is_array($this->xmlResult['dlgMsgData']['ns'])) {
                $ns = implode(',', $this->xmlResult['dlgMsgData']['ns']);
            } else {
                $ns = $this->xmlResult['dlgMsgData']['ns'];
            }
            return [
                'type' => 'dlgMsgData',
                'domain' => $this->xmlResult['dlgMsgData']['name'],
                'data' => (string) $this->xmlResult['msgQ']['msg'] . " (" . $ns . ")"
            ];
        }

        // domain transfers
        if (isset($this->xmlResult['trnData'])) {
            $title = $this->xmlResult['msgQ']['msg']
                . ": from "
                . $this->xmlResult['trnData']['acID']
                . " ("
                . $this->xmlResult['trnData']['acDate']
                . ") to "
                . $this->xmlResult['trnData']['reID']
                . " ("
                . $this->xmlResult['trnData']['reDate']
                . ")";
            // the acID field is necessary to compare transfer-out's in case of 'serverApproved' transfers
            return [
                'type' => $this->xmlResult['trnData']['status'] . 'Transfer',
                'domain' => $this->stripTrailingDots($this->xmlResult['trnData']['name']),
                'data' => $title,
                'acID' => $this->xmlResult['trnData']['acID'],
                'reID' => $this->xmlResult['trnData']['reID']
            ];
        }

        // unknown type
        return [
            'type' => 'unknown',
            'domain' => null,
            'data' => (string) $this->xmlResult->response->msgQ->msg
        ];
    }

    /**
     * show credit
     *
     * @access public
     * @return mix amount or null (if login did not succeed)
     */
    public function showCredit()
    {
        return $this->sessionVars['credit'];
    }
}
