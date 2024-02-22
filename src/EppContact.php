<?php

/**
 * This class handles contacts and supports the following operations on them:
 *
 * - check contact (single and bulk operations supported)
 * - create contact (EPP create command)
 * - fetch contact (EPP info command)
 * - update contact
 * - update contact status
 * - update contact registrant fields
 * - delete contact
 *
 * - storeDB array to store contact to DB
 *
 *
 * @category EppClient
 * @package EppContact
 * @author STEF@N MCDS S.a.s. <info@stefan-mcds.it>
 * @license MIT
 * @version 1.0
 */

namespace EppClient;

use EppClient\EppAbstract;
use EppClient\EppConnection;
use EppClient\Traits\EppDomXML;

class EppContact extends EppAbstract
{
    // array for use to XML structure to epp server
    public ?array $contactVars = [
        'id',
        'name',
        'org',
        'street',
        'street2',
        'street3',
        'city',
        'province',
        'postalCode',
        'countryCode',
        'voice',
        'fax',
        'email',
        'clID',
        'crID',
        'crDate',
        'upID',
        'upDate',
        'authinfo',
        'consentForPublishing',
        'nationalityCode',
        'entityType',
        'regCode',
        'status'
    ];

    // max check contacts
    private $max_check;
    private $id;


    /**
     * Class constructor
     *
     */
    public function __construct(
        protected ?EppConnection $connection,
        protected ?bool $tostore = true
    ) {
        parent::__construct(connection: $this->connection, tostore: $this->tostore);
        $this->initValues();
    }

    /**
     * check contact
     *
     * $contact can be null if isset $contactVars['id']
     * 
     * @param mixed optional contact to check (set handle id!)
     * @return array|boolean (array epp response or boolean -1 on error)
     */
    public function check(mixed $contact = null)
    {
        if (is_null($contact)) {
            $contact = $this->contactVars['id'];
        }
        if (empty($contact)) {
            $this->setError("Operation not allowed, set a handle id!");
            return -2;
        }
        // set the xmlQuery 
        $this->xmlQuery = EppDomXML::Check(vars: ['contact' => $contact, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // query server
        if ($this->ExecuteQuery(clTRType: "check-contact", clTRObject: $contact, storage: true)) {
            return $this->contactVars = array_merge($this->contactVars, $this->xmlResult);
        } else {
            // distinguish between errors and boolean states...
            return -1;
        }
    }

    /**
     * create contact
     *
     * @access public
     * @return boolean status
     */
    public function create()
    {
        $this->xmlQuery = EppDomXML::Create(vars: ['contact' => $this->contactVars, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // query server and return answer (no handling of special return values)
        if ($this->ExecuteQuery(clTRType: "create-contact", clTRObject: $this->contactVars['id'], storage: true)) {
            return $this->contactVars = array_merge($this->contactVars, $this->xmlResult);
        } else {
            return FALSE;
        }
    }

    /**
     * update contact
     *
     * @access public
     * @param boolean execute internal sanity checks
     * @return boolean status
     */
    public function update()
    {
        if (empty($this->contactVars['id'])) {
            $this->setError("Operation not allowed, fetch a handle id first!");
            return FALSE;
        }
        $this->xmlQuery = EppDomXML::Update(vars: ['contact' => $this->contactVars, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // query server
        if ($this->ExecuteQuery(clTRType: "update-contact", clTRObject: $this->contactVars['id'], storage: true)) {
            return $this->contactVars = array_merge($this->contactVars, $this->xmlResult);
        } else {
            return FALSE;
        }
    }

    /**
     * fetch (info) contact through EPP
     *
     * @access public
     * @set $contcatVars
     * @return TRUE or FALSE
     */
    public function fetch(?string $contact = null)
    {
        if (is_null($contact)) {
            $contact = $this->contactVars['id'];
        }
        if (empty($contact)) {
            $this->setError("Operation not allowed, set a handle id!");
            return FALSE;
        }
        $this->xmlQuery = EppDomXML::Info(vars: ['contact' => $contact, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // re-initialize object data
        $this->initValues($this->contactVars);
        // query server
        if ($this->ExecuteQuery(clTRType: "info-contact", clTRObject: $contact, storage: true)) {
            return $this->contactVars = array_merge($this->contactVars, $this->xmlResult);
        } else {
            return FALSE;
        }
    }

    /**
     * delete contact
     *
     * @access public
     * @return boolean status
     */
    public function delete(?string $contact = null)
    {
        if ($contact === null) {
            $contact = $this->contactVars['id'];
        }
        if ($contact == "") {
            $this->setError("Operation not allowed, set a handle!");
            return FALSE;
        }
        $this->xmlQuery = EppDomXML::Delete(vars: ['contact' => $contact, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // query server
        if ($this->ExecuteQuery(clTRType: "delete-contact", clTRObject: $contact, storage: true)) {
            return $this->contactVars = array_merge($this->contactVars, $this->xmlResult);
        } else {
            return FALSE;
        }
    }

    /**
     * update contact status
     *
     * @access public
     * @param string clientDeleteProhibited, clientUpdateProhibited
     * @param string add, rem (optional, defaults to add)
     * @return boolean status
     */
    public function updateStatus(?string $state, ?string $adddel = "add")
    {
        if ($this->id == "") {
            $this->setError("Operation not allowed, fetch a handle id first!");
            return FALSE;
        }
        switch ($state) {
            case "clientDeleteProhibited":
            case "clientUpdateProhibited":
                break;
            default:
                $this->setError("State '" . $state . "' not allowed, expecting one of 'clientDeleteProhibited' or 'clientUpdateProhibited'.");
                return FALSE;
        }
        switch ($adddel) {
            case "add":
                $this->contactVars['status'] = array_merge($this->contactVars['status'], [$state]);
                break;
            case "rem":
                $this->contactVars['status'] = array_diff($this->contactVars['status'], [$state]);
                break;
            default:
                $this->setError("Function '" . $adddel . "' not allowed, expecting either 'add' or 'rem'.");
                return FALSE;
                break;
        }
        $this->xmlQuery = EppDomXML::Update(vars: ['contact' => $this->contactVars, 'clTRID' => $this->connection->_clTRID(action: 'set')]);
        // query server
        if ($this->ExecuteQuery(clTRType: "update-contact-status", clTRObject: $this->contactVars['id'], storage: true)) {
            return $this->contactVars = array_merge($this->contactVars, $this->xmlResult);
        } else {
            return FALSE;
        }
    }
}
