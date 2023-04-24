<?php if (!defined("APPLICATION")) {
    return;
}

ini_set("pcre.recursion_limit", "524");

class BBCodeRelaxed extends BBCode
{
    public function htmlEncode($string)
    {
        return $string;
    }
}
