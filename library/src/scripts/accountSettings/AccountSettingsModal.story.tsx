/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AccountSettingType } from "@library/accountSettings/AccountSettingsDetail";
import { AccountSettingsModal } from "@library/accountSettings/AccountSettingsModal";
import React from "react";

export default {
    title: "Account Settings/Account Settings Modal",
};

export function UsernameEdit() {
    return (
        <>
            <AccountSettingsModal
                visibility={true}
                onVisibilityChange={() => null}
                editType={AccountSettingType.USERNAME}
            />
        </>
    );
}

export function EmailEdit() {
    return (
        <>
            <AccountSettingsModal
                visibility={true}
                onVisibilityChange={() => null}
                editType={AccountSettingType.EMAIL}
            />
        </>
    );
}

export function PasswordEdit() {
    return (
        <>
            <AccountSettingsModal
                visibility={true}
                onVisibilityChange={() => null}
                editType={AccountSettingType.PASSWORD}
            />
        </>
    );
}
