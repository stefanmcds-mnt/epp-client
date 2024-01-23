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
use EppClient\EppDomXML;
use EppClient\EppConnection;
use EppClient\EppException;

class EppSession extends EppAbstract
{
    // array for use to XML structure to epp server
    private ?array $sessionVars = [
        'clID',
        'pw',
        'newpw',
        'msgID',
        'msgIDold',
        'credit',
        'msgTOT',
        'msgTitle',
        'msgDate',
        'type'
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
        $this->sessionVars['clID'] = $this->connection->EPPCfg['username'];
        $this->sessionVars['pw'] = $this->connection->EPPCfg['password'];
    }

    /**
     * session start
     *
     * @access public
     * @return boolean status
     */
    public function hello()
    {
        if ($this->xmlQuery = EppDomXML::Hello()) {
            // query server (will return false)
            if ($this->xmlREsult = $this->ExecuteQuery(clTRType: "hello", storage: true)) {
                $this->sessionVars = array_merge($this->sessionVars, $this->xmlResult);
                $this->registry = EppDomXML::Registry(registry: $this->xmlResult);
                // this is the only query with no result code
                if ((substr($this->xmlResult['code'], 0, 1) == "2")) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
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
        if ($this->ExecuteQuery(clTRType: $which, clTRObject: $which, storage: true)) {
            // see if we got the expected information
            if (isset($this->xmlResult['credit'])) {
                $this->sessionVars['credit'] = $this->xmlResult['credit'];
            }
            return true;
        } else {
            $this->logout();
            return false;
        }
    }

    /**
     * session login
     *
     * @access public
     * @param string optional new password
     * @return mix status (false or EPP status code)
     */
    public function login($newPW = null)
    {
        $this->sessionVars['newpw'] = (!is_null($newPW)) ? $newPW : null;
        $this->xmlQuery = EppDomXML::Login($this->sessionVars);
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
    public function keepalive()
    {
        return $this->hello();
    }

    /**
     * session logout
     *
     * @access public
     * @return mix status (false or EPP status code)
     */
    public function logout()
    {
        $this->xmlQuery = EppDomXML::Logout();
        return $this->loginout("logout");
    }

    /**
     * return current message ID
     * if queue has not yet been looked at, we are going to poll it once
     *
     * @access public
     * @return integer message ID on top of message stack
     */
    public function pollID()
    {
        $this->poll(false, 'req', null);
        return (int) $this->sessionVars['msgID'];
    }

    /**
     * check number of messages in polling queue
     * if queue has not yet been looked at, we are going to poll it once
     *
     * @access public
     * @return integer amount of messages in queue
     */
    public function pollMessageCount()
    {
        $this->poll(false, 'req', null);
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
    public function poll(?bool $store = true, ?string $type = "req", ?string $msgID = null)
    {
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
        $this->xmlQuery = EppDomXML::Poll(['sessionvar' => $this->sessionVars, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // query server
        $qrs = $this->ExecuteQuery("poll", "poll", ($this->debug >= LOG_DEBUG));
        // look at message counter
        if (is_object($this->xmlResult->response->msgQ[0])) {
            $this->sessionVars['msgTOT'] = (int) $this->xmlResult->response->msgQ->attributes()->count;
            $this->sessionVars['msgID'] = (int) $this->xmlResult->response->msgQ->attributes()->id;
            $this->sessionVars['msgTitle'] = (string) $this->xmlResult->response->msgQ->msg;
            $this->sessionVars['msgDate'] = (string) $this->xmlResult->response->msgQ->qDate;
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
        } else if ($qrs === true) {
            $this->sessionVars['msgTOT'] = 0;
        }
        return $qrs;
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
        $ns = $this->xmlResult->getNamespaces(true);

        // passwdReminder
        if (@is_object($this->xmlResult
            ->response
            ->extension
            ->children($ns['extepp'])
            ->passwdReminder
            ->exDate)) {
            return [
                'type' => 'passwdReminder',
                'domain' => null,
                'data' => (string) $this->xmlResult
                    ->response
                    ->extension
                    ->children($ns['extepp'])
                    ->passwdReminder
                    ->exDate
            ];
        }

        // creditMsgData
        if (@is_object($this->xmlResult
            ->response
            ->extension
            ->children($ns['extepp'])
            ->creditMsgData
            ->credit)) {
            return [
                'type' => 'creditMsgData',
                'domain' => null,
                'data' => (string) $this->xmlResult
                    ->response
                    ->msgQ
                    ->msg . " (" .
                    (string) $this->xmlResult
                        ->response
                        ->extension
                        ->children($ns['extepp'])
                        ->creditMsgData
                        ->credit
                    . ")"
            ];
        }

        // delayedDebitAndRefundMsgData
        if (@is_object($this->xmlResult
            ->response
            ->extension
            ->children($ns['extepp'])
            ->delayedDebitAndRefundMsgData
            ->amount)) {
            return [
                'type' => 'delayedDebitAndRefundMsgData',
                'domain' => null,
                'data' => (string) $this->xmlResult
                    ->response
                    ->msgQ
                    ->msg .
                    " (" .
                    (string) $this->xmlResult
                        ->response
                        ->extension
                        ->children($ns['extepp'])
                        ->delayedDebitAndRefundMsgData
                        ->name .
                    " / " .
                    (string) $this->xmlResult
                        ->response
                        ->extension
                        ->children($ns['extepp'])
                        ->delayedDebitAndRefundMsgData
                        ->amount .
                    ")"
            ];
        }

        // simpleMsgData
        if (@is_object($this->xmlResult
            ->response
            ->extension
            ->children($ns['extdom'])
            ->simpleMsgData
            ->name)) {
            return [
                'type' => 'simpleMsgData',
                'domain' => $this->stripTrailingDots(
                    (string) $this->xmlResult
                        ->response
                        ->extension
                        ->children($ns['extdom'])
                        ->simpleMsgData
                        ->name
                ),
                'data' => (string) $this->xmlResult
                    ->response
                    ->msgQ->msg
            ];
        }

        // dnsErrorMsgData
        if (@is_object($this->xmlResult
            ->response
            ->extension
            ->children($ns['extdom'])
            ->dnsErrorMsgData
            ->report
            ->domain)) {
            $msg = [];
            foreach ($this->xmlResult
                ->response
                ->extension
                ->children($ns['extdom'])
                ->dnsErrorMsgData
                ->report
                ->domain
                ->test as $child) {
                $msg[] = $child->attributes()->name . ": " . $child->attributes()->status;
            }
            return [
                'type' => 'dnsErrorMsgData',
                'domain' => $this->stripTrailingDots(
                    (string) $this->xmlResult
                        ->response
                        ->extension
                        ->children($ns['extdom'])
                        ->dnsErrorMsgData
                        ->report
                        ->domain
                        ->attributes()
                        ->name
                ),
                'data' => (string) $this->xmlResult->response->msgQ->msg . " (" . implode(", ", $msg) . ")"
            ];
        }

        // chgStatusMsgData
        if (@is_object($this->xmlResult
            ->response
            ->extension
            ->children($ns['extdom'])
            ->chgStatusMsgData
            ->name)) {
            $msg = [];
            if (@is_object($this->xmlResult
                ->response
                ->extension
                ->children($ns['extdom'])
                ->chgStatusMsgData
                ->targetStatus)) {
                foreach (@$this->xmlResult
                    ->response
                    ->extension
                    ->children($ns['extdom'])
                    ->chgStatusMsgData
                    ->targetStatus
                    ->children($ns['domain'])
                    ->status as $child) {
                    $msg[] = $child->attributes()->s;
                }
                if (isset($ns['rgp'])) {
                    foreach (@$this->xmlResult
                        ->response
                        ->extension
                        ->children($ns['extdom'])
                        ->chgStatusMsgData
                        ->targetStatus
                        ->children($ns['rgp'])
                        ->rgpStatus as $child) {
                        $msg[] = $child->attributes()->s;
                    }
                }
            }
            return [
                'type' => 'chgStatusMsgData',
                'domain' => $this->stripTrailingDots(
                    (string) $this->xmlResult
                        ->response
                        ->extension
                        ->children($ns['extdom'])
                        ->chgStatusMsgData
                        ->name
                ),
                'data' => (string) $this->xmlResult->response->msgQ->msg . " (" . implode(", ", $msg) . ")"
            ];
        }

        // dlgMsgData
        if (@is_object($this->xmlResult
            ->response
            ->extension
            ->children($ns['extdom'])
            ->dlgMsgData
            ->name)) {
            $msg = [];
            foreach (@$this->xmlResult
                ->response
                ->extension
                ->children($ns['extdom'])
                ->dlgMsgData
                ->ns as $child) {
                $msg[] = (string) $child;
            }
            return [
                'type' => 'dlgMsgData',
                'domain' => $this->stripTrailingDots(
                    (string) $this->xmlResult
                        ->response
                        ->extension
                        ->children($ns['extdom'])
                        ->dlgMsgData
                        ->name
                ),
                'data' => (string) $this->xmlResult->response->msgQ->msg . " (" . implode(", ", $msg) . ")"
            ];
        }

        // domain transfers
        if (@is_object($this->xmlResult
            ->response
            ->resData
            ->children($ns['domain'])
            ->trnData
            ->name)) {
            $domain = (string) $this->xmlResult
                ->response
                ->resData
                ->children($ns['domain'])
                ->trnData
                ->name;
            $type = $this->xmlResult
                ->response
                ->resData
                ->children($ns['domain'])
                ->trnData
                ->trStatus . "Transfer";
            $title = (string) $this->xmlResult
                ->response
                ->msgQ->msg .
                ": from " .
                $this->xmlResult
                ->response
                ->resData
                ->children($ns['domain'])
                ->trnData
                ->acID .
                " (" .
                $this->xmlResult
                ->response
                ->resData
                ->children($ns['domain'])
                ->trnData
                ->acDate .
                ") to " .
                $this->xmlResult
                ->response
                ->resData
                ->children($ns['domain'])
                ->trnData->reID .
                " (" .
                $this->xmlResult
                ->response
                ->resData
                ->children($ns['domain'])
                ->trnData->reDate . ")";
            // the acID field is necessary to compare transfer-out's in case of 'serverApproved' transfers
            return [
                'type' => $type,
                'domain' => $this->stripTrailingDots($domain),
                'data' => $title,
                'acID' => $this->xmlResult->response->resData->children($ns['domain'])->trnData->acID,
                'reID' => $this->xmlResult->response->resData->children($ns['domain'])->trnData->reID
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
