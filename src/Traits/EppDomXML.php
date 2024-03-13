<?php

/**
 * Create the xml request fo NIC
 *
 * Use class Arra2XML to transform and array into XML structure
 *
 * $vars must be an multilevel array of epp command
 *
 * Every XML request to Epp server contains
 * - intital standard element
 *   - <?xml version="" encoding="UTF-8" standalone="no">
 *   - <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
 * - an element can be:
 *   - <hello>
 *   - <command>
 *     - one or more operation element
 *     - <extension> optionally element
 *     - <clTRID> element
 * - final standard element
 *   - </epp>
 *
 * @package EPPClient
 * @author STEF@N MCDS S.a.s. <info@stefan-mcds.it>
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 *
 */

namespace EppClient\Traits;

use Utilita\Array2XML;

trait EppDomXML
{
    // Set Registry
    static public ?array $registro;

    // Use SECDNS
    static public ?bool $usednssec = false;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->registro = parent::$registry;
        print_r("EppDomXML Registro: " . $this->registro . "\n");
    }

    /**
     * Configure Registro
     *
     * @return void
     */
    static public function _setRegistry($registry)
    {
        self::$registro = $registry;
        if (self::$usednssec === false) {
            foreach (self::$registro['extURI'] as $key => $value) {
                if (stristr($value, 'secDNS')) {
                    unset(self::$registro['extURI'][$key]);
                }
            }
        }
        return self::$registro;
    }

    /**
     * Configure Registro
     *
     * @return void
     */
    static public function _setDNSSEC($secdns)
    {
        self::$usednssec = $secdns;
        return self::$usednssec;
    }

    /**
     * Construct Final Element
     *
     * @param array $finalElement
     * @return mixed
     */
    static private function _XML(array $finalElement)
    {
        //$xml = Array2XML::getXMLRoot();
        //$xml->appendChild(Array2XML::convert('epp', $finalElement));
        $xml = Array2XML::createXML('epp', $finalElement);
        return $xml->saveXML();
    }

    /**
     * Hello XML
     *
     * @return void
     */
    static public function _Hello()
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
            ],
            'hello'
        ];
        return self::_XML($finalElement);
    }
    /**
     * Login XML
     *
     * @param mixed $var
     * @return void
     */
    static public function _Login(mixed $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd'
            ],
            'command' => [
                'login' => [
                    'clID' => $vars['clID'],
                    'pw' => $vars['pw'],
                    'options' => [
                        'version' => (isset(self::$registro['version']))
                            ? self::$registro['version']
                            : '1.0',
                        'lang' => (isset(self::$registro['lang']))
                            ? self::$registro['lang'][0]
                            : 'en',
                    ],
                    'svcs' => [
                        'objURI' => (isset(self::$registro['objetURI']))
                            ? array_values(self::$registro['objetURI'])
                            : [
                                'urn:ietf:params:xml:ns:contact-1.0',
                                'urn:ietf:params:xml:ns:domain-1.0',
                            ],
                        'svcExtension' => [
                            'extURI' => (isset(self::$registro['extURI']))
                                ? array_values(self::$registro['extURI'])
                                : [
                                    'http://www.nic.it/ITNIC-EPP/extepp-2.0',
                                    'http://www.nic.it/ITNIC-EPP/extcon-1.0',
                                    'http://www.nic.it/ITNIC-EPP/extdom-2.0',
                                    'urn:ietf:params:xml:ns:rgp-1.0',
                                ],
                        ],
                    ],
                ],
            ],
        ];
        if (null !== $vars['newpw']) {
            $finalElement['command']['login']['newpw'] = $vars['newpw'];
        }
        return self::_XML($finalElement);
    }
    /**
     * Logout XML
     *
     * @return object
     */
    static public function _Logout()
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd'
            ],
            'command' => [
                0 => ['logout']
            ]
        ];
        return self::_XML($finalElement);
    }
    /**
     * Poll XML
     *
     * @param array|null $vars
     * @return object
     */
    static public function _Poll(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd'
            ],
            'command' => [
                'poll' => [
                    '@attributes' => [
                        'op' => $vars['sessionvar']['type'],
                    ]
                ],
                'clTRID' => $vars['clTRID'],
            ]
        ];
        if (isset($vars['sessionvar']['msgID'])) {
            $finalElement['command']['poll']['@attributes']['msgID'] = $vars['sessionvar']['msgID'];
        }
        return self::_XML($finalElement);
    }
    /**
     * Check XML
     *
     * @param array|null $vars
     * @return object
     */
    static public function _Check(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'check' => null,
                'clTRID' => $vars['clTRID'],
            ]
        ];
        if (isset($vars['contact'])) {
            $finalElement['command']['check'] = [
                'contact:check' => [
                    '@attributes' => (isset(self::$registro['contact']['schema']))
                        ? self::$registro['contact']['schema']
                        : [
                            'xmlns:contact' => 'urn:ietf:params:xml:ns:contact-1.0',
                        ],
                    'contact:id' => $vars['contact']
                ]
            ];
        }
        if (isset($vars['domain'])) {
            $finalElement['command']['check'] = [
                'domain:check' => [
                    '@attributes' => (isset(self::$registro['domain']['schema']))
                        ? self::$registro['domain']
                        : [
                            'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                        ],
                ]
            ];
            if (is_array($vars['domain'])) {
                foreach ($vars['domain'] as $domain) {
                    $finalElement['command']['check']['domain:check']['domain:name'][] = $domain;
                }
            } else {
                $finalElement['command']['check']['domain:check']['domain:name'] = $vars['domain'];
            }
        }
        return self::_XML($finalElement);
    }
    /**
     * ChangePassword XML
     *
     * @param array|null $vars
     * @return object
     */
    static public function _ChangePasswod(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
            ],
            'command' => [
                'pw' => $vars['pw'],
                'newpw' => $vars['newpw'],
                'options' => [
                    'version' => '1.0',
                    'lang' => 'en',
                ],
                'svcs' => [
                    'objURI' => (isset(self::$registro['objetURI']))
                        ? array_values(self::$registro['objetURI'])
                        :  [
                            'urn:ietf:params:xml:ns:contact-1.0',
                            'urn:ietf:params:xml:ns:domain-1.0',
                        ],
                    'svcExtension' => [
                        'extURI' => isset(self::$registro['extURI'])
                            ? array_values(self::$registro['extURI'])
                            : [
                                'http://www.nic.it/ITNIC-EPP/extepp-2.0',
                                'http://www.nic.it/ITNIC-EPP/extcon-1.0',
                                'http://www.nic.it/ITNIC-EPP/extdom-2.0',
                                'urn:ietf:params:xml:ns:rgp-1.0',
                            ],
                    ],
                ],
                'clTRID' => $vars['clTRID']
            ]
        ];
        return self::_XML($finalElement);
    }
    /**
     * Create XML
     *
     * @param array|null $vars
     * @return object
     */
    static public function _Create(?array $vars)
    {
        if (isset($vars['contact'])) {
            $finalElement = self::_CreateContact($vars);
        } else if (isset($vars['domain'])) {
            $finalElement = self::_CreateDomain($vars);
        } else {
            return false;
        }
        return self::_XML($finalElement);
    }
    /**
     * Create Contact XML
     *
     * @param array|null $vars
     * @return array
     */
    static private function _CreateContact(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'create' => [
                    'contact:create' => [
                        '@attributes' => (isset(self::$registro['contact']))
                            ? self::$registro['contact']['schema']
                            : [
                                'xmlns:contact' => 'urn:ietf:params:xml:ns:contact-1.0',
                                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd',
                            ],
                        'contact:id' => $vars['contact']['id'],
                        'contact:postalInfo' => [
                            '@attributes' => [
                                'type' => "loc",
                            ],
                            'contact:name' => $vars['contact']['name'],
                            'contact:org' => $vars['contact']['org'],
                            'contact:addr' => [
                                'contact:street' => [
                                    $vars['contact']['street'],
                                    $vars['contact']['street2'],
                                    $vars['contact']['street3'],
                                ],
                                'contact:city' => $vars['contact']['city'],
                                'contact:sp' => $vars['contact']['province'],
                                'contact:pc' => $vars['contact']['postalcode'],
                                'contact:cc' => $vars['contact']['countrycode'],
                            ]
                        ],
                        'contact:voice' => [
                            '@attributes' => [
                                'x' => "2111",
                            ],
                            $vars['contact']['voice']
                        ],
                        'contact:fax' => $vars['contact']['fax'],
                        'contact:email' => $vars['contact']['email'],
                        'contact:authInfo' => [
                            'contact:pw' => $vars['contact']['authinfo'],
                        ]
                    ]
                ],
                'extension' => [
                    'extcon:create' => [
                        '@attributes' => (isset(self::$registro['contact']['extcon']))
                            ? self::$registro['contact']['extcon']
                            : [
                                'xmlns:extcon' => 'http://www.nic.it/ITNIC-EPP/extcon-1.0',
                                'xsi:schemaLocation' => 'http://www.nic.it/ITNIC-EPP/extcon-1.0 extcon-1.0.xsd',
                            ],
                        'extcon:consentForPublishing' => $vars['contact']['consentforpublishing'],
                    ],
                ],
                'clTRID' => $vars['clTRID']
            ],
        ];
        if ($vars['entitytype'] != 0) {
            $finalElement['command']['extension']['extcon:create']['extcon:registrant'] = [
                'extcon:nationalityCode' => $vars['contact']['nationalitycode'],
                'extcon:entityType' => $vars['contact']['entitytype'],
                'extcon:regCode' => $vars['contact']['regcode']
            ];
        }
        if ($vars['schoolcode']) {
            $finalElement['command']['extension']['extcon:create']['extcon:registrant']['extcon:schoolCode'] = $vars['contact']['schoolcode'];
        }
        return $finalElement;
    }
    /**
     * Create Domain XML
     *
     * @param array|null $vars
     * @return array
     */
    static private function _CreateDomain(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd'
            ],
            'command' => [
                'create' => [
                    'domain:create' => [
                        '@attributes' => (isset(self::$registro['domain']['schema']))
                            ? self::$registro['domain']['schema']
                            : [
                                'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                            ],
                        'domain:name' => $vars['domain']['name'],
                        'domain:period' => [
                            '@attributes' => [
                                'unit' => 'y',
                            ],
                            '1',
                        ],
                        'domain:ns' => [
                            'domain:hostAttr' => [
                                'domain:hostName',
                                'domain:hostAddr' => [
                                    '@attributes' => null,
                                    null,
                                ],
                            ],
                        ],
                        'domain:registrant' => $vars['domain']['registrant'],
                        'domain:contact' => [
                            '@attributes' => ['type' => 'admin'],
                            $vars['domain']['admin'],
                        ],
                        'domain:authInfo' => [
                            'domain:pw' => $vars['domain']['authInfo'],
                        ],
                    ],
                ],
                'clTRID' => $vars['clTRID']
            ],
        ];
        $finalElement['command']['create']['domain:create']['domain:contact'] = [
            '@attribute' => [
                'type' => 'tech',
            ],
            $vars['domain']['tech'],
        ];
        foreach ($vars['domain']['ns'] as $ns) {
            if (!empty($ns['hostAddr']) && !empty($ns['ip'])) {
                $arraydns[] = [
                    'domain:hostAttr' => [
                        'domain:hostName' => $ns['hostname'],
                        'domain:hostAddr' => [
                            '@attributes' => [
                                'type' => $ns['ip']
                            ],
                            $ns['hostAddr'],
                        ],
                    ],
                ];
            } else {
                $arraydns[] = [
                    'domain:hostAttr' => [
                        'domain:hostName' => $ns['hostname'],
                    ],
                ];
            }
        }
        $finalElement['command']['create']['domain:create']['domain:ns'] = $arraydns;
        if (self::$usednssec === true) {
            // Add DNSSEC information to the command.
            $finalElement['command']['create']['extension'] = [
                'secDNS:create' => [
                    '@attributes' => (isset(self::$registro['domain']['secDNS']))
                        ? self::$registro['domain']['secDNS']
                        : ['xmlns:secDNS' => "urn:ietf:params:xml:ns:secDNS-1.1"],
                    'secDNS:dsData' => [
                        'secDNS:keyTag' => $vars['secDNS']['keyTag'],
                        'secDNS:alg' => $vars['secDNS']['alg'],
                        'secDNS:digestType' => $vars['secDNS']['digestType'],
                        'secDNS:digest' => $vars['secDNS']['digest']
                    ]
                ]
            ];
        }
        return $finalElement;
    }
    /**
     * Cancel Delente Pending Domain XML
     *
     * @param array|null $vars
     * @return objext
     */
    static public function _CancelDeleteDomain(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'transfer' => [
                    '@attributes' => [
                        'op' => 'cancel'
                    ],
                    'domain:transfer' => [
                        '@attributes' => (isset(self::$registro['domain']['schema']))
                            ? self::$registro['domain']['schema']
                            : [
                                'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                            ],
                        'domain:name' => $vars['domain']['name'],
                        'domain:authInfo' => [
                            'domain:pw' => $vars['domain']['authInfo'],
                        ]
                    ]
                ],
                'clTRID' => $vars['clTRID'],
            ],
        ];
        return self::_XML($finalElement);
    }
    /**
     * Delete XML
     *
     * @param array|null $vars
     * @return object
     */
    static public function _Delete(?array $vars)
    {
        if (isset($vars['contact'])) {
            $finalElement = self::_DeleteContact($vars);
        } else if (isset($vars['domain'])) {
            $finalElement = self::_DeleteDomain($vars);
        } else {
            return false;
        }
        return self::_XML($finalElement);
    }
    /**
     * Contact Delete XML
     *
     * @param array|null $vars
     * @return array
     */
    static private function _DeleteContact(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'delete' => [
                    'contact:delete' => [
                        '@attributes' => (isset(self::$registro['contact']['schema']))
                            ? self::$registro['contact']['schema']
                            : [
                                'xmlns:contact' => 'urn:ietf:params:xml:ns:contact-1.0',
                                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd',
                            ],
                        'contact:id' => $vars['contact'],
                    ],
                ],
                'clTRID' => $vars['clTRID'],
            ],
        ];
        return $finalElement;
    }
    /**
     * Domain Delete XML
     *
     * @param array|null $vars
     * @return array
     */
    static private function _DeleteDomain(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd'
            ],
            'command' => [
                'delete' => [
                    'domain:delete' => [
                        '@attributes' => (isset(self::$registro['domain']['schema']))
                            ? self::$registro['domain']['schema']
                            : [
                                'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                            ],
                        'domain:name' => $vars['domain'],
                    ],
                ],
                'clTRID' => $vars['clTRID'],
            ],
        ];
        return $finalElement;
    }
    /**
     * Info XML
     *
     * @param array|null $vars
     * @return object
     */
    static public function _Info(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'info' => null,
                'extension' => null,
                'clTRID' => $vars['clTRID'],
            ],
        ];
        if (isset($vars['contact'])) {
            $finalElement['command']['info'] = [
                'contact:info' => [
                    '@attributes' => (isset(self::$registro['contact']['schema']))
                        ? self::$registro['contact']['schema']
                        : [
                            'xmlns:contact' => 'urn:ietf:params:xml:ns:contact-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd',
                        ],
                    'contact:id' => $vars['contact'],
                ]
            ];
        }
        if (isset($vars['domain'])) {
            $finalElement['command']['info'] = [
                'domain:info' => [
                    '@attributes' => (isset(self::$registro['domain']['schema']))
                        ? self::$registro['domain']['schema']
                        : [
                            'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd'
                        ],
                    'domain:name' => [
                        '@attributes' => [
                            'hosts' => 'all',
                        ],
                        '@value' => $vars['domain']['name']
                    ],
                ],
            ];
            if (isset($vars['domain']['authInfo'])) {
                $finalElement['command']['info']['domain:info']['domain:authInfo'] = [
                    'domain:pw' => $vars['domain']['authInfo'],
                ];
            }
            if (isset($vars['infContacts'])) {
                $finalElement['command']['extension'] = [
                    'extdom:infContacts' => [
                        '@attributes' => [
                            'op' => (isset($vars['infContacts'])) ? $vars['infContacts'] : 'all',
                        ]
                    ],
                ];
                if (isset(self::$registro['domain']['extdom'])) {
                    foreach (self::$registro['domain']['extdom'] as $key => $value) {
                        $finalElement['command']['extension']['extdom:infContacts']['@attributes'][$key] = $value;
                    }
                } else {
                    $finalElement['command']['extension']['extdom:infContacts']['@attributes']['xmlns:extdom'] = 'http://www.nic.it/ITNIC-EPP/extdom-2.0';
                    $finalElement['command']['extension']['extdom:infContacts']['@attributes']['xsi:schemaLocation'] = 'http://www.nic.it/ITNIC-EPP/extdom-2.0 extdom-2.0.xsd';
                }
            }
        }
        return self::_XML($finalElement);
    }
    /**
     * Restore Domain from Delete
     *
     * @param array|null $vars
     * @return object
     */
    static public function _Restore(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'update' => [
                    'domain:update' => [
                        '@attributes' => (isset(self::$registro['domain']['schema']))
                            ? self::$registro['domain']['schema']
                            : [
                                'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                            ],
                        'domain:name' => $vars['domain'],
                        'domain:chg',
                    ],
                ],
                'extension' => [
                    'rgp:update' => (isset(self::$registro['domain']['rgp']))
                        ? self::$registro['domain']['rgp']
                        : [
                            'xmlns:rgp' => 'urn:ietf:params:xml:ns:rgp-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:rgp-1.0 rgp-1.0.xsd',
                        ],
                    'rgp:restore' => [
                        '@attributes' => [
                            'op' => 'request',
                        ]
                    ],
                ],
                'clTRID' => $vars['clTRID'],
            ],
        ];
        return self::_XML($finalElement);
    }
    /**
     * Transfer Domain Case XML
     *
     * @param array|null $vars
     * @param string|null $motive
     * @return object
     */
    static public function _Tranfer(?array $vars, ?string $motive)
    {
        if (isset($op)) {
            $finalElement = [
                '@attributes' => [
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
                ],
                'command' => [
                    'transfer' => [
                        '@attributes' => [
                            'op' => strtolower($motive),
                        ],
                        'domain:transfer' => [
                            '@attributes' => (isset(self::$registro['domain']['schema']))
                                ? self::$registro['domain']['schema']
                                : [
                                    'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                                    'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                                ],
                            'domain:name' => $vars['domain']['name'],
                            'domain:authInfo' => [
                                'domain:pw' > $vars['domain']['authInfo'],
                            ],
                        ],
                        'clTRID' => $vars['clTRID'],
                    ],
                ]
            ];
            if (strtolower($motive) === 'request') {
                $finalElement['command']['transfer']['domain:transfer']['domain:authInfo'] = [
                    'domain:pw' > $vars['domain']['oldauthinfo'],
                ];
                $finalElement['command']['extension'] = [
                    'extdom:trade' => [
                        '@attributes' => (isset(self::$registro['domain']['extdom']))
                            ? self::$registro['domain']['extdom']
                            : [
                                'xmlns:extdom' => 'http://www.nic.it/ITNIC-EPP/extdom-2.0',
                                'xsi:schemaLocation' => 'http://www.nic.it/ITNIC-EPP/extdom-2.0 extdom-2.0.xsd',
                            ],
                        'extdom:transferTrade' => [
                            'extdom:newRegistrant' => $vars['domain']['registrant'],
                            'extdom:newAuthInfo' => [
                                'extdom:pw' => $vars['domain']['authInfo'],
                            ]
                        ],
                    ],
                ];
            }
        } else {
            return false;
        }
        return self::_XML($finalElement);
    }
    /**
     * Update XML
     *
     * @param array|null $vars
     * @param string|null $what
     * @return objext
     */
    static public function _Update(?array $vars, ?string $what = null)
    {
        if (isset($vars['contact'])) {
            $finalElement = self::_UpdateContact($vars);
        } else if (isset($vars['domain'])) {
            $finalElement = self::_UpdateDomain($vars, $what);
        } else {
            return false;
        }
        return self::_XML($finalElement);
    }
    /**
     * Update Domain XML
     *
     * @param array|null $vars
     * @param string|null $what
     * @return array
     */
    static private function _UpdateDomain(?array $vars, ?string $what)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'update' => [
                    'domain:update' => [
                        '@attributes' => (isset(self::$registro['domain']['schema']))
                            ? self::$registro['domain']['schema']
                            : [
                                'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                            ],
                        'domain:name' => $vars['domain']['name'],
                        'domain:chg' => [
                            'domain:authInfo' => [
                                'domain:pw' => $vars['domain']['authInfo'],
                            ],
                        ],
                    ],
                    'clTRID' => $vars['clTRID'],
                ],
            ],
        ];
        if ($what === 'registrant') {
            $finalElement['command']['update']['domain:update']['domain:chg']['domain:registrant'] = $vars['domain']['registrant'];
        }
        if ($what === 'admin') {
            $finalElement['command']['update']['domain:update']['domain:chg']['domain:contact'] = [
                '@attributes' => [
                    'type' => 'admin',
                ],
                $vars['domain']['admin']
            ];
        }
        if ($what === 'ns') {
            unset($finalElement['command']['command']['update']['domain:update']['domain:cfg']);
            foreach ($vars['domain']['ns'] as $ns) {
                if (!empty($ns['hostAddr']) && !empty($ns['ip'])) {
                    $upd[] = [
                        'domain:hostAttr' => [
                            'domain:hostName' => $ns['hostname'],
                            'domain:hostAddr' => [
                                '@attributes' => [
                                    'type' => $ns['ip']
                                ],
                                $ns['hostAddr'],
                            ],
                        ],
                    ];
                } else {
                    $upd[] = [
                        'domain:hostAttr' => [
                            'domain:hostName' => $ns['hostname'],
                        ],
                    ];
                }
            }
            foreach ($vars['old']['ns'] as $ns) {
                if (!empty($ns['hostAddr']) && !empty($ns['ip'])) {
                    $rem[] = [
                        'domain:hostAttr' => [
                            'domain:hostName' => $ns['hostname'],
                            'domain:hostAddr' => [
                                '@attributes' => [
                                    'type' => $ns['ip']
                                ],
                                $ns['hostAddr'],
                            ],
                        ],
                    ];
                } else {
                    $rem[] = [
                        'domain:hostAttr' => [
                            'domain:hostName' => $ns['hostname'],
                        ],
                    ];
                }
            }
            $finalElement['command']['command']['update']['domain:update']['domain:add']['domain:ns'] = $upd;
            $finalElement['command']['command']['update']['domain:update']['domain:rem']['domain:ns'] = $rem;
            if (self::$usednssec === true) {
                if ($vars['old']['secDNS'] === 'all') {
                    $finalElement['command']['command']['update']['extension'] = [
                        'secDNS:update' => [
                            '@attributes' => (isset(self::$registro['domain']['secDNS']))
                                ? self::$registro['domain']['secDNS']
                                : ['xmlns:secDNS' => "urn:ietf:params:xml:ns:secDNS-1.1"],
                            'secDNS:rem' => [
                                'secDNS:all' => 'true'
                            ],
                            'secDNS:add' => [
                                'secDNS:dsData' => [
                                    'secDNS:keyTag' => $vars['secDNS']['keyTag'],
                                    'secDNS:alg' => $vars['secDNS']['alg'],
                                    'secDNS:digestType' => $vars['secDNS']['digestType'],
                                    'secDNS:digest' => $vars['secDNS']['digest']
                                ],
                            ],
                        ],
                    ];
                } else {
                    $finalElement['command']['command']['update']['extension'] = [
                        'secDNS:update' => [
                            '@attributes' => (isset(self::$registro['domain']['secDNS']))
                                ? self::$registro['domain']['secDNS']
                                : ['xmlns:secDNS' => "urn:ietf:params:xml:ns:secDNS-1.1"],
                            'secDNS:rem' => [
                                'secDNS:dsData' => [
                                    'secDNS:keyTag' => $vars['old']['secDNS']['keyTag'],
                                    'secDNS:alg' => $vars['old']['secDNS']['alg'],
                                    'secDNS:digestType' => $vars['old']['secDNS']['digestType'],
                                    'secDNS:digest' => $vars['old']['secDNS'['digest']],
                                ],
                            ],
                            'secDNS:add' => [
                                'secDNS:dsData' => [
                                    'secDNS:keyTag' => $vars['secDNS']['keyTag'],
                                    'secDNS:alg' => $vars['secDNS']['alg'],
                                    'secDNS:digestType' => $vars['secDNS']['digestType'],
                                    'secDNS:digest' => $vars['secDNS']['digest']
                                ],
                            ],
                        ],
                    ];
                }
            }
        }
        return $finalElement;
    }
    /**
     * Update Contact XML
     *
     * @param array|null $vars
     * @return array
     */
    static private function _UpdateContact(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'update' => [
                    'contact:update' => [
                        [
                            'attributes' => (isset(self::$registro['contact']['schema']))
                                ? self::$registro['contact']['schema']
                                : [
                                    'xmlns:contact' => 'urn:ietf:params:xml:ns:contact-1.0',
                                    'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd',
                                ],
                            'contact:id' => $vars['contact']['handle'],
                            'contact:add' => [
                                'contact:status' => [
                                    '@attributes' => [
                                        's' => 'clientDeleteProhibited',
                                    ],
                                ],
                            ],
                            'contact:chg' => [
                                'contact:voice' => '+39.05863152111',
                                'contact:email' => 'info@esempio.it',
                            ],
                        ],
                    ],
                    'extension' => [
                        'extcon:update' => [
                            'attributes' => (isset(self::$registro['contact']['extcon']))
                                ?  self::$registro['contact']['extcon']
                                : [
                                    'xmlns:extcon' => 'http://www.nic.it/ITNIC-EPP/extcon-1.0',
                                    'xsi:schemaLocation' => 'http://www.nic.it/ITNIC-EPP/extcon-1.0 extcon-1.0.xsd',
                                ],
                            'extcon:consentForPublishing' => $vars['contact']['consentforpublishing'],
                        ],
                    ],
                    'clTRID' => $vars['clTRID'],
                ],
            ],
        ];
        return $finalElement;
    }
}
