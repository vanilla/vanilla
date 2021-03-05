/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@vanilla/i18n";

export function EmptyAuthenticatorResults() {
    return (
        <div className="padded">
            <p>{t("Add a connection to get started.")}</p>
        </div>
    );
}
