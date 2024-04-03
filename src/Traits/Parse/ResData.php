<?php

namespace EppClient\Traits\Parse;

trait ResData
{

    /**
     * resData element family on Command
     */

    /**
     * Parse resData family
     * 
     * Evalutate trnData children on transfer domain command
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _trnData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return [
            'name' => $parse[$element . ':trnData'][$element . ':name'],
            'trStatus' => $parse[$element . ':trnData'][$element . ':trStatus'],
            'reID' => $parse[$element . ':trnData'][$element . ':reID'],
            'reDate' => $parse[$element . ':trnData'][$element . ':reDate'],
            'acID' => $parse[$element . ':trnData'][$element . ':acID'],
            'acDate' => $parse[$element . ':trnData'][$element . ':acDate'],
        ];
    }

    /**
     * Parse resData Family
     * 
     * Evalutate infData Family Child
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _infData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return $parse;
    }

    /**
     * Parse resData Family
     * 
     * Evalutate infData Family child
     * 
     * Check status children
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _infData_status(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return $parse['@attributes']['s'];
    }

    /**
     * Parse infData Family
     * 
     * Evalutate contact childreen on Domain/Contact Command
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _infData_contact(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        // Contact
        $p = null;
        foreach ($parse as $key => $item) {
            if ($item['@attributes']['type'] === 'tech') {
                $p[$item['@attributes']['type']][] = $item['@value'];
            } else {
                $p[$item['@attributes']['type']] = $item['@value'];
            }
            unset($parse[$key]);
        }
        return $p;
    }

    /**
     * Parse infData Family
     * 
     * Evalutate ns childreeen on Domain command
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _infData_ns(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        // NS on command Domain
        $a = $parse[$element . ':hostAttr'];
        $p = null;
        foreach ($a as $key => $item) {
            $p[] = [
                'hostName' => $item[$element . ':hostName'],
                'hostAddr' => $item[$element . ':hostAddr']['@value'],
                'ip' => $item[$element . ':hostAddr']['@attributes']['ip']
            ];
        }
        return $p;
    }

    /**
     * Parse infData Family
     * 
     * Evalutate authInfo childreen on Domain command
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _infData_authInfo(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        // on command Domain
        return $parse[$element . ':pw'];
    }


    /**
     * Parse infData Family
     * 
     * Evalutate postalInfo childreen on Domain/Contact Command
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _infData_postalInfo(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        unset($parse['@attributes']);
        $p = array_merge(
            $parse,
            $parse[$element . ':addr']
        );
        unset($p[$element . ':addr']);
        return $p;
    }

    /**
     * Parse infData Family
     * 
     * Evalutate voice/fax childreen on Domain/Contact Command
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _infData_Tel(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        return (null !== $parse['@attributes']['x'])
            ? $parse['@value'] . ' int. ' . $parse['@attributes']['x']
            : $parse['@value'];
    }

    /**
     * Parse resData Family
     * 
     * Evalutate chkData childreen for Domain/Contact on Command Check
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _chkData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        $p = null;
        foreach ($parse[$element . ':cd'] as $aa) {
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
        return $p;
    }

    /**
     * Parse resData Family
     * 
     * Evalutate creData childreen for Domain/Contact on Command Create
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _creData(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        $p = null;
        if (isset($parse['id'])) {
            $p['id'] = $parse['id'];
        }
        if (isset($parse['name'])) {
            $p['name'] = $parse['name'];
        }
        if (isset($parse['exDate'])) {
            $p['exDate'] = $parse['exDate'];
        }
        $p['crData'] = $parse['crDate'];
        return $p;
    }
}
