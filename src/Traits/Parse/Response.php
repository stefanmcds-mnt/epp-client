<?php

/**
 * Parse the XML Response from Epp server
 *
 * Every response contains:
 * - intital standard element
 *   - <?xml version="" encoding="UTF-8">
 *   - <epp xmlns:contact="urn:ietf:params:xml:ns:contact-1.0"
 *          xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"
 *          xmlns:extepp="http://www.nic.it/ITNIC-EPP/extepp-2.0" (attribute for ITNIC)
 *          xmlns:extdom="http://www.nic.it/ITNIC-EPP/extdom-2.0" (attribute for ITNIC)
 *          xmlns:extcon="http://www.nic.it/ITNIC-EPP/extcon-1.0" (attribute for ITNIC)
 *          xmlns:rgp="urn:ietf:params:xml:ns:rgp-1.0"
 *          xmlns="urn:ietf:params:xml:ns:epp-1.0" >
 * - one or more elements
 *   - <greeteg> only on hello
 *   - <result>  on command
 *   - <msgQ>    only on command Poll
 *   - <resData> data elements related command
 *   - <extension> extension elements related command
 *   - <trID> transcation identifer
 * - final standard element
 *   - </epp>
 *
 *
 *
 *
 * @package EPPClient
 * @author STEF@N MCDS S.a.s. <info@stefan-mcds.it>
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 *
 */

namespace EppClient\Traits\Parse;

use EppClient\Traits\Parse\Command;
use EppClient\Traits\Parse\Result;
use EppClient\Traits\Parse\ResData;
use EppClient\Traits\Parse\Extension;
use EppClient\EppException;
use Utilita\XML2Array;

trait Response
{

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
    public function _ParseResponseBody(?string $xml, ?string $element = null)
    {
        if ($element === 'dns') {
            $ext = 'extdom';
        } else {
            // get element by called class
            //$element = explode('\\', get_class($this));
            //$element = str_replace('epp', '', strtolower(end($element)));
            $element = str_replace(['epp', 'Epp', 'EPP'], '', strtolower($this->GetClassName($this)));
            //$this->class = get_class($this) . ' ' . $element;
            if ($element === 'domain') {
                $ext = 'extdom';
            }
            if ($element === 'contact') {
                $ext = 'extcon';
            }
            if ($element === 'session') {
                $ext = ['extepp', 'extdom', 'extcon', 'extsecDNS'];
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

            // Greeting only on Hello Command
            if (isset($res['greeting'])) {
                $this->registry = Command::_Greeting(parse: $res['greeting']);
            }

            // Epp msgQ
            if (isset($res['msgQ'])) {
                // store msgQ
                $res['msgQ'] = Command::_msgQ(parse: $res['msgQ']);
            }

            // The Epp Result Element
            if (isset($res['result'])) {
                // store result
                $res['result'] = Result::_Result(parse: $res['result']);
            }

            // resData Elements
            if (isset($res['resData'])) {
                //tranfer domain
                if (isset($res['resData'][$element . ':trnData'])) {
                    // store new item key
                    $res['trnData'] = ResData::_trnData(parse: $res['resData'][$element . ':trnData'], element: $element);
                    // remove old
                    unset($res['resData'][$element . ':trnData']);
                }
                // infdata
                if (isset($res['resData'][$element . ':infData'])) {
                    // store to resData key children element
                    $res['resData'] = ResData::_infData(parse: $res['resData'][$element . ':infData'], element: $element);
                    // Status
                    if (isset($res['resData'][$element . ':status'])) {
                        // store status
                        $res['resData'][$element . ':status'] = ResData::_infData_status(parse: $res['resData'][$element . ':status'], element: $element);
                    }
                    // Contact
                    if (isset($res['resData'][$element . ':contact'])) {
                        // store to element contact
                        $res['resData'][$element . ':contact'] = ResData::_infData_contact(parse: $res['resData'][$element . ':contact'], element: $element);
                    }
                    // NS on command Domain
                    if (isset($res['resData'][$element . ':ns'])) {
                        // store element ns
                        $res['resData'][$element . ':ns'] = ResData::_infData_ns(parse: $res['resData'][$element . ':ns'], element: $element);
                    }
                    // Authinfo
                    if (isset($res['resData'][$element . ':authInfo'])) {
                        // store element authInfo
                        $res['resData'][$element . ':authInfo'] = ResData::_infData_authInfo(parse: $res['resData'][$element . ':authInfo'], element: $element);
                    }
                    // PostalInfo on command Contact
                    if (isset($res['resData'][$element . ':postalInfo'])) {
                        $res['resData'][$element . ':postalInfo'] = ResData::_infData_postalInfo(parse: $res['resData'][$element . ':postalInfo'], element: $element);
                    }
                    // voice on Contact Command
                    if (isset($res['resData'][$element . ':voice'])) {
                        // store voice key
                        $res['resData']['voice'] = ResData::_infData_Tel(parse: $res['resData']['voice'], element: $element);
                        // delete original item
                        unset($res['resData'][$element . ':voice']);
                        unset($res[$element . ':voice']);
                    }
                    // Fax on Contacnt Command
                    if (isset($res['resData'][$element . ':fax'])) {
                        // store fax key
                        $res['resData']['fax'] = ResData::_infData_Tel(parse: $res['resData']['fax'], element: $element);
                        // delete original element
                        unset($res['resData'][$element . ':fax']);
                        unset($res[$element . ':fax']);
                    }
                    // chkData on Check Command
                    if (isset($res['resData'][$element . ':chkData'])) {
                        // store chkData on element key
                        $res['resData'][$element] = ResData::_chkData(parse: $res['resData'][$element . ':chkData'], element: $element);
                        // delete original
                        unset($res['resData'][$element . ':chkData']);
                    }
                    // creData on Create Command
                    if (isset($res['resData'][$element . ':creData'])) {
                        // merge into new item resData
                        $res['resData'] = array_merge($res['resData'], ResData::_creData(parse: $res['resData'][$element . ':creData']));
                        // delete original
                        unset($res['resData'][$element . ':creData']);
                    }
                }
                // merge into new array
                $res = array_merge($res, $res['resData']);
                unset($res['resData']);
            }

            // Extension Elements
            if (isset($res['extension'])) {
                // Only on Poll by Session
                if ($element === 'session') {
                    foreach ($ext as $e) {
                        //creditMsgData on Logof command
                        if (isset($res['extension'][$e . ':creditMsgData'])) {
                            $res['creditMsgData'] =  Extension::_creditMsgData(parse: $res['extension'][$e . ':creditMsgData'], ext: $e);
                            unset($res['extension'][$e . ':creditMsgData']);
                        }
                        // Password Reminder
                        if (isset($res['extension'][$e . ':passwordReminder'])) {
                            $res['passwordReminder'] = Extension::_passwordReminder(parse: $res['extension'][$e . ':passwordReminder']);
                            unset($res['extension'][$e . ':passwordReminder']);
                        }
                        //delayedDebitAndRefundMsgData
                        if (isset($res['extension'][$e . ':delayedDebitAndRefundMsgData'])) {
                            $res['delayedDebitAndRefundMsgData'] = Extension::_delayedDebitAndRefundMsgData(parse: $res['extension'][$e . ':delayedDebitAndRefundMsgData']);
                            unset($res['extension'][$e . ':delayedDebitAndRefundMsgData']);
                        }
                        // simpleMsgData
                        if (isset($res['extension'][$e . ':simpleMsgData'])) {
                            $res['simpleMsgData'] = Extension::_simpleMsgData(parse: $res['extension'][$e . ':simpleMsgData']);
                            unset($res['extension'][$e . ':simpleMsgData']);
                        }
                        //wrongNamespaceReminder
                        if (isset($res['extension'][$e . ':wrongNamespaceReminder'])) {
                            $res['wrongNamespaceReminder'] = Extension::_wrongNamespaceReminder(parse: $res['extension'][$e . ':wrongNamespaceReminder'], ext: $e);
                            unset($res['extension'][$e . ':wrongNamespaceReminder']);
                        }
                        // dnsErrorMsgData
                        if (isset($res['extension'][$e . ':dnsErrorMsgData'])) {
                            $res['dnsErrorMsgData'] = Extension::_dnsErrorMsgData(parse: $res['extension'][$e . ':dnsErrorMsgData'], ext: $e);
                        }
                        // chgStatusMsgData
                        if (isset($res['extension'][$e . ':chgStatusMsgData'])) {
                            $res[$element . ':chgStatusMsgData'] = Extension::_chgStatusMsgData(parse: $res['extension'][$e . ':chgStatusMsgData'], ext: $e);
                            unset($res['extension'][$e . ':chgStatusMsgData']);
                        }
                        // dlgMsgData
                        if (isset($res['extension'][$e . ':dlgMsgData'])) {
                            $res['dlgMsgData'] = Extension::_dlgMsgData(parse: $res['extension'][$e . ':dlgMsgData'], ext: $e);
                            unset($res['extension'][$e . ':dlgMsgData']);
                        }
                    }
                } else {
                    // trade on transfer Command on domain
                    if (isset($res['extension'][$ext . ':trade'])) {
                        $res['trnData'] = Extension::_trade(parse: $res['extension'][$ext . ':trade'], ext: $ext);
                        unset($res['extension'][$ext . ':trade']);
                    }
                    // infContactsData on Domain Command with infContactsData
                    if (isset($res['extension'][$ext . ':infContactsData'])) {
                        $res[$element . ':infContacts'] = Extension::_infContactsData(parse: $res['extension'][$ext . ':infContactsData'], ext: $ext);
                        unset($res['extension'][$ext . ':infContactsData']);
                    }
                    // ownStatus On Domain Command
                    if (isset($res['extension'][$ext . ':infData'])) {
                        // ownStatus
                        if (isset($res['extension'][$ext . ':infData'][$ext . ':ownStatus'])) {
                            $res[$element . ':ownStatus'] = Extension::_infData(parse: $res['extension'][$ext . ':infData'], ext: $ext);
                            unset($res['extension'][$ext . ':infData'][$ext . ':ownStatus']);
                        }
                        // Contact consentForPublishing
                        if (isset($res['extension'][$ext . ':infData'][$ext . ':consentForPublishing'])) {
                            $res[$element . ':consentForPublishing'] = Extension::_infData(parse: $res['extension'][$ext . ':infData'], ext: $ext);
                            unset($res['extension'][$ext . ':infData'][$ext . ':consentForPublishing']);
                        }
                        // Contact Registrant
                        if (isset($res['extension'][$ext . ':infData'][$ext . ':registrant'])) {
                            $res = array_merge($res, Extension::_infData(parse: $res['extension'][$ext . ':infData'], ext: $ext));
                            unset($res['extension'][$ext . ':infData'][$ext . ':registrant']);
                        }
                        unset($res['extension'][$ext . ':infData']);
                    }
                    // infNsToValidateData on Domain Command
                    if (isset($res['extension'][$ext . ':infNsToValidateData'])) {
                        $res[$element . ':nsToValidate'] = Extension::_infNsToValidateData(parse: $res['extension'][$ext . ':infNsToValidateData'], ext: $ext);
                        unset($res['extension'][$ext . ':infNsToValidateData']);
                    }
                    // SecDNS
                    if (isset($res['extension']['secDNS:infData'])) {
                        $res['secDNS'] = Extension::_secDNS_infData(parse: $res['extension']['secDNS:infData']);
                        unset($res['extension']['secDNS:infData']);
                    }
                    if (isset($res['extension']['extsecDNS:infDsOrKeyToValidateData'])) {
                        if (isset($res['extension']['extsecDNS:infDsOrKeyToValidateData']['dsOrKeysToValidate'])) {
                            $res['secDNS']['dsOrKeysToValidate'] = Extension::_extsecDNS_infDsOrKeyToValidateData(parse: $res['extension']['extsecDNS:infDsOrKeyToValidateData']['dsOrKeysToValidate']);
                        } else {
                            $res['secDNS']['remAll'] = Extension::_extsecDNS_infDsOrKeyToValidateData(parse: $res['extension']['extsecDNS:infDsOrKeyToValidateData']);
                        }
                        unset($res['extension']['extsecDNS:infDsOrKeyToValidateData']);
                    }
                }
                unset($res['extension']);
            }
            if (isset($res['trID'])) {
                $res = array_merge($res, Command::_trID(parse: $res['trID']));
                unset($res['trID']);
            }
            // Set $xmlResult
            return $this->tree($res);
        } else {
            throw new EppException('Invalid response from server');
        }
    }
}
