<?php

namespace EppClient\Traits\Parse;

trait resultTrait
{

    /**
     * Parse <result> element
     * 
     * The <result> Element structure
     * <result>
     *     <code> // one
     *     <msg>  // one
     *     <value>
     *         <wrongValue> // can be one or more
     *             <element>
     *             <namespace>
     *             <value>
     *         </worngValue>
     *     </value>
     *     <extValue> // can be one or more
     *         <value> // one
     *         <reason> // one
     *     </extvalue>
     *    
     * </result>
     * 
     * If the command has been succesfully there is only one <result> element
     * Otherwhise can be more than one <result> elements 
     *
     * @param array|null $parse
     * @param string|null $element
     * @param string|null $ext
     * @return array|null
     */
    static public function _Result(?array $parse, ?string $element = null, ?string $ext = null): array|string
    {
        $res['code'] = $parse['@attributes']['code'];
        $res['msg'] = $parse['msg']['@value'];
        if (isset($parse['value'])) {
            if (count($parse['value']) > 1) {
                foreach ($parse['value'] as $value) {
                    $res['wrongValue'][] = [
                        'element' => $value['extepp:wrongValue']['extepp:element'],
                        'value' => $value['extepp:wrongValue']['extepp:value'],
                        'namespace' => $value['extepp:wrongValue']['extepp:namespace']
                    ];
                }
            } else {
                $res['wrongValue']['element'] = $parse['value']['extepp:wrongValue']['extepp:element'];
                $res['wrongValue']['namespace'] = $parse['value']['extepp:wrongValue']['extepp:namespace'];
                $res['wrongValue']['value'] = $parse['value']['extepp:wrongValue']['extepp:value'];
            }
        }
        if (isset($parse['extValue'])) {
            if (count($parse['extValue']) > 1) {
                foreach ($parse['extValue'] as $value) {
                    $res['wrongValue'][] = [
                        'code' => $value['value']['extepp:reasonCode'],
                        'reason' => $value['reason']['@value']
                    ];
                }
            } else {
                $res['wrongValue']['code'] = $parse['extValue']['value']['extepp:reasonCode'];
                $res['wrongValue']['reason'] = $parse['extValue']['reason']['@value'];
            }
        }
        return $res;
    }
}
