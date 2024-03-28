<?php

namespace HS\Install\Tables\Copier\Serialized;

class HSTriggersTTriggerDef extends AbstractSerializedEncoder
{
    /**
     * Encode serialized string as required per
     * field, as each serialized data type is unique.
     * @param  string $string
     * @return string
     */
    public function encode($string)
    {
        require_once cBASEPATH.'/helpspot/lib/class.triggers.php';

        $trigger = $this->mb_unserialize($string);

        if (! $trigger) {
            return false;
        }

        $trigger->name = $this->encodeString($trigger->name);
        $trigger->filter_folder = $this->encodeString($trigger->filter_folder);
        $trigger->CONDITIONS = $this->recursiveEncode($trigger->CONDITIONS);
        $trigger->ACTIONS = $this->recursiveEncode($trigger->ACTIONS);

        return serialize($trigger);
    }
}
