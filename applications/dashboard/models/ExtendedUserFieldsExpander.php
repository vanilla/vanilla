<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Class for expanding the "extended" fields on a user record. The behavior of this class duplicates that of the
 * UserProfileFieldsExpander, the only difference being the expand field's name ("extended" rather than "profileFields").
 * It is included for legacy compatibility.
 */
class ExtendedUserFieldsExpander extends UserProfileFieldsExpander
{
    /**
     * D.I.
     *
     * @param \Vanilla\Dashboard\Models\ProfileFieldModel $profileFieldModel
     */
    public function __construct(\Vanilla\Dashboard\Models\ProfileFieldModel $profileFieldModel)
    {
        $this->setBaseKey("extended");
        parent::__construct($profileFieldModel);
    }
}
