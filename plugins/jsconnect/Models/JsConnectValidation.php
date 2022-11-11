<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\JsConnect\Models;

use Garden\Schema\Validation;

/**
 * Child class of Validation which supports translations for JsConnect-specific fields.
 */
class JsConnectValidation extends Validation
{
    private $fieldMapping = [];

    /**
     * Returns a new instance of JsConnectValidation which is copied from an existing Validation object.
     * Updates a local variable containing a mapping of modern field names to legacy field names.
     *
     * @param Validation $validation
     * @return JsConnectValidation
     */
    public static function createFromValidation(Validation $validation): JsConnectValidation
    {
        $self = new self();
        $errors = $validation->getRawErrors();
        foreach ($errors as $error) {
            $path = isset($error["path"]) ? "{$error["path"]}.{$error["field"]}" : $error["field"];
            if (isset(JsConnectAuthenticatorTypeProvider::INPUT_MAP[$path])) {
                $self->fieldMapping[$error["field"]] = JsConnectAuthenticatorTypeProvider::INPUT_MAP[$path];
            }
            $self->addError($error["field"], $error["code"], $error);
        }
        $self->setTranslateFieldNames(true);
        return $self;
    }

    /**
     * Translates an error message string. This will translate using legacy field names.
     *
     * @param $str
     * @return string
     */
    public function translate($str): string
    {
        if (isset($this->fieldMapping[$str])) {
            $str = $this->fieldMapping[$str];
        }
        return t($str);
    }
}
