<?php

namespace HS\Install\Tables\Copier\Serialized;

class HSMailRulesTRuleDef extends AbstractSerializedEncoder
{
    /**
     * Encode serialized string as required per
     * field, as each serialized data type is unique.
     * @param  string $string
     * @return string
     */
    public function encode($string)
    {
        require_once cBASEPATH.'/helpspot/lib/class.mail.rule.php';

        $rules = $this->mb_unserialize($string);

        if (! $rules) {
            return false;
        }

        $rules->name = $this->encodeString($rules->name);
        $rules->CONDITIONS = $this->recursiveEncode($rules->CONDITIONS);
        $rules->ACTIONS = $this->recursiveEncode($rules->ACTIONS);

        return serialize($rules);
    }
}
