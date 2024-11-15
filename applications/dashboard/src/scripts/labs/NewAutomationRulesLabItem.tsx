/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import image from "./NewAutomationRulesLabItem.svg";
import { VanillaLabsItem } from "@dashboard/labs/VanillaLabsItem";
import { t } from "@vanilla/i18n";
import React from "react";
import SmartLink from "@library/routing/links/SmartLink";

export function NewAutomationRulesLabItem() {
    return (
        <VanillaLabsItem
            imageUrl={image}
            labName={"automationRules"}
            name={t("Automation Rules")}
            themeFeatureName="automationRules"
            description={t(
                "Automation Rules are powerful automations that allow you more easily manage your communities by pairing triggers and actions.",
                "Automation Rules are powerful automations that allow you more easily manage your communities by pairing triggers and actions.",
            )}
            notes={
                <SmartLink to="https://success.vanillaforums.com/kb/articles/1569-manage-your-automation-rules">
                    {t("Find out more")}
                </SmartLink>
            }
        />
    );
}
