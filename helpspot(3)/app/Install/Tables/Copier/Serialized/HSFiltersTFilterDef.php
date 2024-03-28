<?php

namespace HS\Install\Tables\Copier\Serialized;

class HSFiltersTFilterDef extends AbstractSerializedEncoder
{
    /**
     * Encode serialized string as required per
     * field, as each serialized data type is unique.
     * @param  string $string
     * @return string
     */
    public function encode($string)
    {
        // Filter definitions are also of type hs_auto_rule
        require_once cBASEPATH.'/helpspot/lib/class.auto.rule.php';

        $rules = $this->mb_unserialize($string);

        if (! $rules) {
            return false;
        }

        $rules->name = $this->encodeString($rules->name);
        $rules->CONDITIONS = $this->recursiveEncode($rules->CONDITIONS);
        $rules->ACTIONS = $this->recursiveEncode($rules->ACTIONS);
        $rules->filter_folder = $this->encodestring($rules->filter_folder);

        return serialize($rules);
    }
}
