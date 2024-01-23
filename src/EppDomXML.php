<?php

/**
 * Create the xml request fo NIC
 *
 * Use class Arra2XML to transform and array into XML structure
 * 
 * $vars must be an multilevel array of epp command
 * 
 * @package EPPClient
 * @author STEF@N MCDS S.a.s. <info@stefan-mcds.it>
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 *
 */

namespace EppClient;

use Utilita\Array2XML;

class EppDomXML extends EppAbstract
{

    /**
     * Construnctor Class
     *
     * @param mixed|null $registry
     */
    public function __construct(public mixed $registry)
    {
    }

    /**
     * Set Registry
     *
     * @param mixed $registry
     * @return mixed
     */
    public static function Registry(mixed $registry)
    {
        self::$registry = array_merge(self::$registry, $registry);
        foreach (self::$registry['objURI'] as $item) {
            $a = explode(':', $item);
            $b = explode('-', end($a));
            self::$registry[reset($b)] = [
                'xmlns:' . reset($b) => $item,
                'xsi:schemaLocation' => $item . ' ' . end($a) . '.xsd',
            ];
        }
        foreach (self::$registry['extURI'] as $item) {
            if (stristr($item, 'http')) {
                $a = explode('/', $item);
            }
            if (stristr($item, ':')) {
                $a = explode(':', $item);
            }
            $b = explode('-', end($a));
            self::$registry[reset($b)] = [
                'xmlns:' . reset($b) => $item,
                'xsi:schemaLocation' => $item . ' ' . end($a) . '.xsd',
            ];
        }
        return self::$registry;
    }

    /**
     * Construct Final Element
     *
     * @param array $finalElement
     * @return mixed
     */
    private static function _XML(array $finalElement)
    {
        $xml = Array2XML::getXMLRoot();
        $xml->appendChild(Array2XML::convert('epp', $finalElement));
        return $xml->saveXML();
    }

    /**
     * Hello XML
     *
     * @return void
     */
    public static function Hello()
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
    public static function Login(mixed $vars)
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
                        'version' => self::$registry['version'],
                        'lang' => self::$registry['lang'][0],
                    ],
                    'svcs' => [
                        /*
                        'objURI' => [
                            'urn:ietf:params:xml:ns:contact-1.0',
                            'urn:ietf:params:xml:ns:domain-1.0',
                        ],
                        'svcExtension' => [
                            'extURI' => [
                                'http://www.nic.it/ITNIC-EPP/extepp-2.0',
                                'http://www.nic.it/ITNIC-EPP/extcon-1.0',
                                'http://www.nic.it/ITNIC-EPP/extdom-2.0',
                                'urn:ietf:params:xml:ns:rgp-1.0',
                            ],
                        ],
                        */
                        'objURI' => array_values(self::$registry['objetURI']),
                        'svcExtension' => [
                            'extURI' => array_values(self::$registry['extURI']),
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
    public static function Logout()
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd'
            ],
            'command' => [
                'logout'
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
    public static function Poll(?array $vars)
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
    public static function Check(?array $vars)
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
                    /*
                    '@attributes' => [
                        'xmlns:contact' => 'urn:ietf:params:xml:ns:contact-1.0',
                    ],
                    */
                    '@attributes' => self::$registry['contact'],
                    'contact:id' => $vars['contact']
                ]
            ];
        } else if (isset($vars['domain'])) {
            $finalElement['command']['check'] = [
                'domain:check' => [
                    /*
                    '@attributes' => [
                        'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                        'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                    ],
                    */
                    '@attributes' => self::$registry['domain'],
                ]
            ];
            if (is_array($vars['domains'])) {
                foreach ($vars['domains'] as $domain) {
                    $finalElement['command']['check']['domain:check']['domain:name'][] = $domain;
                }
            } else {
                $finalElement['command']['check']['domain:check']['domain:name'] = $vars['domains'];
            }
        } else {
            return false;
        }
        return self::_XML($finalElement);
    }
    /**
     * ChangePassword XML
     *
     * @param array|null $vars
     * @return object
     */
    public static function ChangePasswod(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
            ],
            'command' => [
                'pw' => $vars['pw'],
                'newpw' => $vars['newpw'],
                'clTRID' => $vars['clTRID'],
                'options' => [
                    'version' => '1.0',
                    'lang' => 'en',
                ],
                'svcs' => [
                    /*
                        'objURI' => [
                            'urn:ietf:params:xml:ns:contact-1.0',
                            'urn:ietf:params:xml:ns:domain-1.0',
                        ],
                        'svcExtension' => [
                            'extURI' => [
                                'http://www.nic.it/ITNIC-EPP/extepp-2.0',
                                'http://www.nic.it/ITNIC-EPP/extcon-1.0',
                                'http://www.nic.it/ITNIC-EPP/extdom-2.0',
                                'urn:ietf:params:xml:ns:rgp-1.0',
                            ],
                        ],
                        */
                    'objURI' => array_values(self::$registry['objetURI']),
                    'svcExtension' => [
                        'extURI' => array_values(self::$registry['extURI']),
                    ],
                ],
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
    public static function Create(?array $vars)
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
    private static function _CreateContact(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'clTRID' => $vars['clTRID'],
                'create' => [
                    'contact:create' => [
                        /*
                        '@attributes' => [
                            'xmlns:contact' => 'urn:ietf:params:xml:ns:contact-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd',
                        ],
                        */
                        '@attributes' => self::$registry['contact'],
                        'contact:id' => $vars['contact']['handle'],
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
                        /*
                        '@attributes' => [
                            'xmlns:extcon' => 'http://www.nic.it/ITNIC-EPP/extcon-1.0',
                            'xsi:schemaLocation' => 'http://www.nic.it/ITNIC-EPP/extcon-1.0 extcon-1.0.xsd',
                        ],
                        */
                        '@attributes' => self::$registry['extcon'],
                        'extcon:consentForPublishing' => $vars['contact']['consentforpublishing'],
                    ],
                ],
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
    private static function _CreateDomain(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd'
            ],
            'command' => [
                'clTRID' => $vars['clTRID'],
                'create' => [
                    'domain:create' => [
                        /*
                        '@attributes' => [
                            'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                        ],
                        */
                        '@attributes' => self::$registry['domain'],
                        'domain:name' => $vars['domain']['domain'],
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
                            'domain:pw' => $vars['domain']['authinfo'],
                        ],
                    ],
                ],
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
        return $finalElement;
    }
    /**
     * Cancel Delente Pending Domain XML
     *
     * @param array|null $vars
     * @return objext
     */
    public static function CancelDeleteDomain(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'clTRID' => $vars['clTRID'],
                'transfer' => [
                    '@attributes' => [
                        'op' => 'cancel'
                    ],
                    'domain:transfer' => [
                        /*
                        '@attributes' => [
                            'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                        ],
                        */
                        '@attributes' => self::$registry['domain'],
                        'domain:name' => $vars['domain']['domain'],
                        'domain:authInfo' => [
                            'domain:pw' => $vars['domain']['authinfo'],
                        ]
                    ]
                ],
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
    public static function Delete(?array $vars)
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
    private static function _DeleteContact(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'clTRID' => $vars['clTRID'],
                'delete' => [
                    'contact:delete' => [
                        /*
                        '@attributes' => [
                            'xmlns:contact' => 'urn:ietf:params:xml:ns:contact-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd',
                        ],
                        */
                        '@attributes' => self::$registry['contact'],
                        'contact:id' => $vars['contact'],
                    ],
                ],
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
    private static function _DeleteDomain(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd'
            ],
            'command' => [
                'clTRID' => $vars['clTRID'],
                'delete' => [
                    'domain:delete' => [
                        /*
                        '@attributes' => [
                            'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                        ],
                        */
                        '@attributes' => self::$registry['domain'],
                        'domain:name' => $vars['domain'],
                    ],
                ],
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
    public static function Info(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'clTRID' => $vars['clTRID'],
                'info' => null,
            ],
        ];
        if (isset($vars['contact'])) {
            $finalElement['command']['info'] = [
                'contact:info' => [
                    /*
                    '@attributes' => [
                        'xmlns:contact' => 'urn:ietf:params:xml:ns:contact-1.0',
                        'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd',
                    ],
                    */
                    '@attributes' => self::$registry['contact'],
                    'contact:id' => $vars['contact'],
                ]
            ];
        } else if (isset($vars['domain'])) {
            if (!is_null($vars['domains']['authinfo'])) {
                $finalElement['command']['info'] = [
                    'domain:info' => [
                        /*
                        '@attributes' => [
                            'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd'
                        ],
                        */
                        '@attributes' => self::$registry['domain'],
                        'domain:name' => [
                            '@attribute' => [
                                'hosts' => 'all',
                            ],
                            $vars['domains']['domain'],
                        ],
                        'domain:authInfo' => [
                            'domain:pw' => $vars['domains']['authinfo'],
                        ],
                    ],
                ];
            } else {
                $finalElement['command']['info'] = [
                    'domain:info' => [
                        /*
                        '@attributes' => [
                            'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd'
                        ],
                        */
                        '@attributes' => self::$registry['domain'],
                        'domain:name' => [
                            '@attribute' => [
                                'hosts' => 'all',
                            ],
                            $vars['domains']['domain']
                        ],
                    ],
                ];
            }
            $finalElement['command']['info']['extension'] = [
                'extdom:infContacts' => [
                    '@attributes' => [
                        'op' => (isset($vars['infContacts'])) ? $vars['infContacts'] : 'all',
                        /*'xmlns:extdom' => 'http://www.nic.it/ITNIC-EPP/extdom-2.0',
                        'xsi:schemaLocation' => 'http://www.nic.it/ITNIC-EPP/extdom-2.0 extdom-2.0.xsd',*/
                        self::$registry['domain']
                    ]
                ],
            ];
        } else {
            return false;
        }
        return self::_XML($finalElement);
    }
    /**
     * Restore Domain from Delete
     *
     * @param array|null $vars
     * @return object
     */
    public static function Restore(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'clTRID' => $vars['clTRID'],
                'update' => [
                    'domain:update' => [
                        /*
                        '@attributes' => [
                            'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                        ],
                        */
                        '@attributes' => self::$registry['domain'],
                        'domain:name' => $vars['domain'],
                        'domain:chg',
                    ],
                ],
                'extension' => [
                    'rgp:update' => self::$registry['rgp'],
                    /*[
                        'xmlns:rgp' => 'urn:ietf:params:xml:ns:rgp-1.0',
                        'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:rgp-1.0 rgp-1.0.xsd',
                    ]*/
                    'rgp:restore' => [
                        '@attributes' => [
                            'op' => 'request',
                        ]
                    ],
                ],
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
    public static function Tranfer(?array $vars, ?string $motive)
    {
        if (isset($op)) {
            $finalElement = [
                '@attributes' => [
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                    'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
                ],
                'command' => [
                    'clTRID' => $vars['clTRID'],
                    'transfer' => [
                        '@attributes' => [
                            'op' => strtolower($motive),
                        ],
                        'domain:transfer' => [
                            /*
                            '@attributes' => [
                                'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                            ],
                            */
                            '@attributes' => self::$registry['domain'],
                            'domain:name' => $vars['domain']['domain'],
                            'domain:authInfo' => [
                                'domain:pw' > $vars['domain']['authinfo'],
                            ],
                        ],
                    ],
                ]
            ];
            if (strtolower($motive) === 'request') {
                $finalElement['command']['transfer']['domain:transfer']['domain:authInfo'] = [
                    'domain:pw' > $vars['domain']['oldauthinfo'],
                ];
                $finalElement['command']['extension'] = [
                    'extdom:trade' => [
                        /*
                        '@attributes' => [
                            'xmlns:extdom' => 'http://www.nic.it/ITNIC-EPP/extdom-2.0',
                            'xsi:schemaLocation' => 'http://www.nic.it/ITNIC-EPP/extdom-2.0 extdom-2.0.xsd',
                        ],
                        */
                        '@attributes' => self::$registry['extdom'],
                        'extdom:transferTrade' => [
                            'extdom:newRegistrant' => $vars['domain']['registrant'],
                            'extdom:newAuthInfo' => [
                                'extdom:pw' => $vars['domain']['authinfo'],
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
    public static function Update(?array $vars, ?string $what = null)
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
    private static function _UpdateDomain(?array $vars, ?string $what)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'clTRID' => $vars['clTRID'],
                'update' => [
                    'domain:update' => [
                        /*
                        '@attributes' => [
                            'xmlns:domain' => 'urn:ietf:params:xml:ns:domain-1.0',
                            'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd',
                        ],
                        */
                        '@attributes' => self::$registry['domain'],
                        'domain:name' => $vars['domain']['domain'],
                        'domain:chg' => [
                            'domain:authInfo' => [
                                'domain:pw' => $vars['domain']['authinfo'],
                            ],
                        ],
                    ],
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
        }
        return $finalElement;
    }
    /**
     * Update Contact XML
     *
     * @param array|null $vars
     * @return array
     */
    private static function _UpdateContact(?array $vars)
    {
        $finalElement = [
            '@attributes' => [
                'xmlns' => 'urn:ietf:params:xml:ns:epp-1.0',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd',
            ],
            'command' => [
                'clTRID' => $vars['clTRID'],
                'update' => [
                    'contact:update' => [
                        [
                            /*
                            'attributes' => [
                                'xmlns:contact' => 'urn:ietf:params:xml:ns:contact-1.0',
                                'xsi:schemaLocation' => 'urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd',
                            ],
                            */
                            'attributes' => self::$registry['contact'],
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
                            /*
                            '@attributes' => [
                                'xmlns:extcon' => 'http://www.nic.it/ITNIC-EPP/extcon-1.0',
                                'xsi:schemaLocation' => 'http://www.nic.it/ITNIC-EPP/extcon-1.0 extcon-1.0.xsd',
                            ],
                            */
                            'attributes' => self::$registry['extcon'],
                            'extcon:consentForPublishing' => $vars['contact']['consentforpublishing'],
                        ],
                    ],
                ],
            ],
        ];
        return $finalElement;
    }
}
