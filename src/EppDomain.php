<?php

/**
 * This class handles domains and supports the following operations on them:
 *
 *  - check domain (single and bulk operations supported)
 *  - create domain (EPP create command)
 *  - fetch domain (EPP info command)
 *  - update domain
 *  - update domain registrant
 *  - update domain status
 *  - restore domain
 *  - delete domain
 *
 *  - transferStatus (query) domain
 *  - transfer/transfer-trade domain
 *  - transferApprove domain
 *  - transferReject domain
 *  - transferCancel domain
 *
 *  - storeDB store domain to DB
 *  - loadDB load domain from DB
 *  - updateDB update domain stored in DB
 *
 * @category    EppClient
 * @package     EppDomain
 * @author      STEF@N MCDS S.a.s. <info@stefan-mcds.it>
 * @license     MIT
 *
 */

namespace EppClient;

use EppClient\EppAbstract;
use EppClient\EppConnection;
use EppClient\Traits\EppDomXML;
use Algo26\IdnaConvert\ToIdn;

final class EppDomain extends EppAbstract
{
    private ?string $admininitial;
    private mixed $techinitial;
    private mixed $nsinitial;
    // array for use to XML structure to epp server
    public ?array $domainVars = [
        'roid' => null,
        'name' => null,
        'ns' => null,
        'host' => null,
        'registrant' => null,
        'contact' => null,
        'authInfo' => null,
        'clID' => null,
        'crID' => null,
        'crDate' => null,
        'exDate' => null,
        'status' => null,
        'oldauthInfo' => null,
        'infContacts' => null,
        'upID' => null,
        'upDate' => null,
        'trDate' => null,
        'trnData' => null
    ];

    // use just in case of an updateRegistrant + change of agent
    private ?string $userid;
    private ?string $idn;
    private ?string $domain;
    private mixed $tech;
    private mixed $status;

    /*
     * Class constructor
     *
     */
    function __construct(
        protected ?EppConnection $connection,
        protected ?bool $tostore = true
    ) {
        parent::__construct(connection: $this->connection, tostore: $this->tostore);
        $this->initValues();
        //$this->idn = new ToIdn();
    }

    /**
     * check domain
     *
     * @param    mixed $element  optional domain to check (set domain!)
     * @return   mixed|boolean   array([name] => esempio1.it, [avail] => false, [reason] => Domain is registered) or boolean -1 error
     */
    public function Check(mixed $element = null)
    {
        if ($element === null) {
            $element = $this->domainVars['name'];
        } else if ($element === "") {
            $this->setError("Operation not allowed, set a domain name first!");
            return -2;
        }
        // fetch xml template
        $this->xmlQuery = EppDomXML::_Check(vars: ['domain' => $element, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // query server
        if ($this->ExecuteQuery(clTRType: "check-domain", clTRObject: $element, storage: true)) {
            return $this->domainVars = array_merge($this->domainVars, $this->xmlResult);
        } else {
            // distinguish between errors and boolean states...
            return -1;
        }
    }

    /**
     * create domain
     *
     * @access   public
     * @param    boolean execute internal sanity checks
     * @return   boolean status
     */
    public function Create()
    {
        $this->xmlQuery = EppDomXML::_Create(vars: ['domain' => $this->domainVars, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // query server and return answer (no handling of special return values)
        if ($this->ExecuteQuery(clTRType: "create-domain", clTRObject: $this->domainVars['name'], storage: true)) {
            $this->nsinitial = $this->domainVars['ns'];
            $this->admininitial = $this->domainVars['contact']['admin'];
            $this->techinitial = $this->domainVars['contact']['tech'];
            return $this->domainVars = array_merge($this->domainVars, $this->xmlResult);
        } else {
            return false;
        }
    }

    /**
     * fetch domain through EPP
     *
     * @access   public
     * @param    string  domain to load
     * @param    string  authinfo string (domain sponsored by other registrar)
     * @param    string
     * @return   boolean status
     */
    public function Fetch(?string $element = null, ?string $authinfo = null, ?string $infContacts = 'all')
    {
        $infContacts = strtolower($infContacts);
        if (!in_array($infContacts, ["all", "registrant", "admin", "tech"])) {
            $infContacts = 'all';
        }
        // if domain and authinfo was not given as an argument, but has been set
        $element = [
            'name' => ($element === null) ? $this->domainVars['name'] : $element,
            'authInfo' => ($authinfo === null) ? $this->domainVars['authInfo'] : $authinfo,
        ];
        $this->xmlQuery = EppDomXML::_Info(vars: ['domain' => $element, 'infContacts' => $infContacts, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // query server
        if ($this->ExecuteQuery(clTRType: "info-domain", clTRObject: $element, storage: true)) {
            // reset changes at the bottom
            $this->domainVars = array_merge($this->domainVars, $this->xmlResult);
            $this->nsinitial = $this->domainVars['ns'];
            $this->admininitial = $this->domainVars['contact']['admin'];
            $this->techinitial = $this->domainVars['contact']['tech'];
            return $this->domainVars;
        } else {
            return false;
        }
    }

    /**
     * print domain status - the states will be set after a call to fetch()
     *
     * @access   public
     * @return   mixed     server side state (text-string or false)
     */
    public function State()
    {
        if ($this->domainVars['status'] !== null) {
            return $this->domainVars['status'];
        } else {
            return false;
        }
    }

    /**
     * delete domain
     *
     * @access   public
     * @param    string  domain name to delete
     * @return   boolean status
     */
    public function Delete(?string $element = null)
    {
        if ($element === null)
            $element = $this->domainVars['name'];
        if ($element == "") {
            $this->setError("Operation not allowed, set a domain name!");
            return false;
        }
        // fetch xml template
        $this->xmlQuery = EppDomXML::_Delete(vars: ['domain' => $element, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // query server
        if ($this->ExecuteQuery(clTRType: "delete-domain", clTRObject: $element, storage: true)) {
            return $this->domainVars = array_merge($this->domainVars, $this->xmlResult);
        } else {
            return false;
        }
    }

    /**
     * update domain
     *
     * @access   public
     * @param    $operation mode registrant,admin,ns,tech
     * @param    $old old value
     * @return   boolean status
     */
    public function Update($operation, $old)
    {
        if ($this->domainVars['name'] === "") {
            $this->setError("Operation not allowed, fetch a domain first!");
            return false;
        }
        $APT = false;
        foreach ($operation as $action) {
            $this->xmlQuery = EppDomXML::_Update(vars: ['domain' => $this->domainVars, 'old' => $old, 'clTRID' => $this->connection->_clTRID(action: 'set')], what: strtolower($operation));
            switch (strtolower($action)) {
                case 'ns':
                    // query server
                    if ($this->ExecuteQuery(clTRType: "update-domain", clTRObject: $this->domainVars['name'], storage: true)) {
                        $this->nsinitial = $old['ns'];
                        $APT = true;
                    }
                    break;
                case 'admin':
                    // query server
                    if ($this->ExecuteQuery(clTRType: "update-domain", clTRObject: $this->domainVars['name'], storage: true)) {
                        $this->admininitial = $old['contact']['admin'];
                        $APT = true;
                    }
                    break;
                case 'registrant':
                    // query server
                    if ($this->ExecuteQuery(clTRType: "update-domain", clTRObject: $this->domainVars['name'], storage: true)) {
                        $APT = true;
                    }
                    break;
                case 'tech':
                    // query server
                    if ($this->ExecuteQuery(clTRType: "update-domain", clTRObject: $this->domainVars['name'], storage: true)) {
                        $this->techinitial = $old['contact']['tech'];
                        $APT = true;
                    }
                    break;
                default:
                    // query server
                    if ($this->ExecuteQuery(clTRType: "update-domain", clTRObject: $this->domainVars['name'], storage: true)) {
                        $APT = true;
                    }
                    break;
            }
        }
        return ($APT === true) ? $this->domainVars = array_merge($this->domainVars, $this->xmlResult) : $APT;
    }

    /**
     * update domain status
     *
     * @access   public
     * @param    string  domain
     * @param    string  clientDeleteProhibited, clientUpdateProhibited, clientTransferProhibited, clientHold, clientLock
     * @param    string  add, rem (optional, defaults to add)
     * @return   boolean status
     */
    public function updateStatus(?string $element, ?string $state, ?string $adddel = "add")
    {
        if ($element == "") {
            $this->setError("Operation not allowed, fetch a domain first!");
            return false;
        }
        switch ($state) {
            case "clientDeleteProhibited":
            case "clientUpdateProhibited":
            case "clientTransferProhibited":
            case "clientHold":
            case "clientLock":
                break;
            default;
                $this->setError("State '" . $state . "' not allowed, expecting one of 'clientDeleteProhibited', 'clientUpdateProhibited', 'clientTransferProhibited', 'clientHold', 'clientLock'.");
                return false;
        }

        switch ($adddel) {
            case "add":
                $this->status = array_merge($this->status, array($state));
                break;
            case "rem":
                $this->status = array_diff($this->status, array($state));
                break;
            default:
                $this->setError("Function '" . $adddel . "' not allowed, expecting either 'add' or 'rem'.");
                return false;
                break;
        }
        $clTRID = $this->connection->_clTRID(action: 'set');
        // fetch xml template
        $this->xmlQuery = '';

        // query server
        return $this->ExecuteQuery(clTRType: "update-domain-status", clTRObject: $element, storage: true);
    }

    /**
     * restore domain
     *
     * @access   public
     * @param    string  domain name to restore
     * @return   boolean status
     */
    public function Restore(?string $element = null)
    {
        if ($element === null)
            $element = $this->domainVars['name'];
        if ($element == "") {
            $this->setError("Operation not allowed, set a domain name first!");
            return false;
        }
        $this->xmlQuery = EppDomXML::_Restore(vars: ['domain' => $element, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // query server
        if ($this->ExecuteQuery(clTRType: "update-domain-restore", clTRObject: $element, storage: true)) {
            $this->domainVars = array_merge($this->domainVars, $this->xmlResult);
        }
    }

    /**
     * transfer domain / transfer-trade domain
     *
     * @access   public
     * @param    array   domain to transfer
     * @param    string  operation transfer type
     * @return   boolean status
     */
    public function Transfer(?array $element, ?string $operation = NULL)
    {
        if ($element === null)
            $element = $this->domainVars;
        if ($element == "") {
            $this->setError("Operation not allowed, set a domain name first!");
            return false;
        }
        if ($operation === NULL) {
            $this->setError("Operation not allowed");
            return false;
        }
        $this->xmlQuery = EppDomXML::Tranfer(vars: ['domain' => $element, 'clTRID' => $this->connection->_clTRID(action: 'set')], motive: $operation);
        // query server
        if ($this->ExecuteQuery(clTRType: "transfer-domain-" . $operation, clTRObject: $element, storage: true)) {
            $this->domainVars = array_merge($this->domainVars, $this->xmlResult);
        }
    }
}
