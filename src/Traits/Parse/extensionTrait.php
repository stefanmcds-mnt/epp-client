<?php

namespace EppClient\Traits\Parse;

use EppClient\Traits\EppTree;

trait extensionTrait
{
    use EppTree;

    /**
     * Extension element family on command
     */

    /**
     * Parse extension family
     * 
     * Evalutate creditMsgData children on command login/logout
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _creditMsgData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return $parse[$ext . ':credit'];
    }

    /**
     * Parse extension family
     * 
     * Evalutate passwordReminder children on command Poll
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _passwordReminder(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return $parse['exDate'];
    }

    /**
     * Parse extension family
     * 
     * Evalutate delayedDebitAndRefundMsgData children on command Poll
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _delayedDebitAndRefundMsgData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return $parse['name'] . '/' . $parse['Amount'];
    }

    /**
     * Parse extension family
     * 
     * Evalutate simpleMsgData children on command Poll
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _simpleMsgData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return $parse['name'];
    }

    /**
     * Parse extension family
     * 
     * Evalutate wrongNamespaceReminder children on command Poll
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _wrongNamespaceReminder(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        foreach ($parse[$ext . 'wrongNamespaceInfo'] as $wni) {
            $res[] = $wni[$ext . ':wrongNamespace'];
        }
        return $res;
    }

    /**
     * Parse extension family
     * 
     * Evalutate dnsErrorMsgData children on command Poll
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _dnsErrorMsgData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        $ele = $parse;
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
        return [
            'domain' => $ele[$ext . ':domain'],
            'status' => $ele[$ext . ':status'],
            'id' => $ele[$ext . ':validationId'],
            'data' => $ele[$ext . ':validationDate'],
            'nameservers' => $p,
        ];
    }

    /**
     * Parse extension family
     * 
     * Evalutate chgStatusMsgData children on command Poll
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _chgStatusMsgData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return [
            'name' => $parse[$ext . ':name'],
            'targetStatus' => [
                'status' => $parse[$ext . ':targetStatus']['domain:status']['@attributes']['s'],
                'rgpStatus' => $parse[$ext . ':targetStatus']['rgp:rgpStatus']['@attributes']['s'],
            ]
        ];
    }

    /**
     * Parse extension family
     * 
     * Evalutate dlgMsgData children on command Poll
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _dlgMsgData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return [
            'name' => $ext . ':name',
            'ns' => $ext . ':ns'
        ];
    }

    /**
     * Parse extension family
     * 
     * Evalutate trade children on command Domain transfer
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _trade(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return [
            'newRegistrant' => $parse[$ext . ':transferTrade'][$ext . ':newRegistrant'],
            'newAuthInfo' => (isset($parse[$ext . ':transferTrade'][$ext . ':newAuthInfo']))
                ?  $parse[$ext . ':transferTrade'][$ext . ':newAuthInfo'][$ext . ':pw']
                : null
        ];
    }

    /**
     * Parse extension family
     * 
     * Evalutate infContactsData children on command Domain Info
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _infContactsData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        $p = null;
        // Registrant
        if ($parse[$ext . ':registrant']) {
            $p['registrant'] = array_merge(
                $parse[$ext . ':registrant'][$ext . ':infContact'],
                $parse[$ext . ':registrant'][$ext . ':extInfo']
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
            $p['registrant'] = self::_Tree($p['registrant']);
        }
        // Admin anch Tech
        if ($parse[$ext . ':contact']) {
            $z = 0;
            foreach ($parse[$ext . ':contact'] as $key => $item) {
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
                    //unset($p['tech'][$z]['extcon:registrant']);
                    $p['tech'][$z] = self::_Tree($p['tech'][$z]);
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
                        //$p['admin']['extcon:registrant']
                    );
                    unset($p['admin']['contact:addr']);
                    unset($p['admin']['contact:postalInfo']);
                    //unset($p['admin']['extcon:registrant']);
                    $p['admin'] = self::_Tree($p['admin']);
                }
            }
        }
        return $p;
    }

    /**
     * Parse extension family
     * 
     * Evalutate infData children on command Domain/Contact
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _infData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        // ownStatus
        if (isset($parse[$ext . ':ownStatus'])) {
            $res[$element . ':ownStatus'] = $parse[$ext . ':ownStatus']['@attributes']['s'];
        }
        // Contact consentForPublishing
        if (isset($parse[$ext . ':consentForPublishing'])) {
            $res[$element . ':consentForPublishing'] = $parse[$ext . ':consentForPublishing'];
        }
        // Contact Registrant
        if (isset($res['extension'][$ext . ':infData'][$ext . ':registrant'])) {
            $res = $parse[$ext . ':registrant'];
        }
        return $res;
    }

    /**
     * Parse extension family
     * 
     * Evalutate infNsToValidateData children on command Domain Info
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _infNsToValidateData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        foreach ($parse[$ext . ':nsToValidate'][$element . ':hostAttr'] as $aa) {
            $res[] = $aa[$element . ':hostName'];
        }
        return $res;
    }

    /**
     * Parse extension family
     * 
     * Evalutate secDNS:infData children on command
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _secDNS_infData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return $parse['secDNS:dsData'];
    }

    /**
     * Parse extension family
     * 
     * Evalutate extsecDNS:infDsOrKeyToValidateData children on command
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _extsecDNS_infDsOrKeyToValidateData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return (isset($parse['secDNS:dsData'])) ? $parse['secDNS:dsData'] : $parse;
    }
}
