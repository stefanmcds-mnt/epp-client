<?php

/**
 * Parse the Command response element
 *
 * The command respons contain
 * - one or more <result> elements
 * - one <msgQ> element
 * - one <resData> element
 * - one <extension> element
 * - one <trID> eleement
 * 
 * 
 * @package EPPClient
 * @author STEF@N MCDS S.a.s. <info@stefan-mcds.it>
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
 *
 */

namespace EppClient\Traits\Parse;

trait Command
{

    /**
     * Greeting on Hello request to Epp Server
     *
     * @param array|null $parse
     * @return array|string
     */
    static public function _Greeting(?array $parse): array|string
    {
        // Greeting only on Hello Command
        $res = array_merge($parse['svcMenu'], $parse['svcMenu']['svcExtension']);
        unset($parse['dcp']);
        unset($parse['svcMenu']);
        unset($parse['svcExtension']);
        unset($parse['greeting']);
        return array_merge($parse, $res);
    }

    /**
     * Parse msgQ element on Poll Command
     *
     * @param array|null $parse
     * @return array|string
     */
    static public function _msgQ(?array $parse): array|string
    {
        return [
            'date' => $parse['qDate'],
            'msg' => $parse['msg']['@value'],
            'id' => $parse['@attributes']['id'],
            'count' => $parse['@attributes']['count'],
        ];
    }

    /**
     * Parse trID element on Command
     *
     * @param array|null $parse
     * @return array|string
     */
    static public function _trID(?array $parse): array|string
    {
        return $parse;
    }
}
