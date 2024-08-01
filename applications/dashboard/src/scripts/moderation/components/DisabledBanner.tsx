/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import Message from "@library/messages/Message";
import { getMeta, t } from "@library/utility/appUtils";

export function DisabledBanner() {
    const isNewEscalationsEnabled = getMeta("featureFlags.escalations.Enabled", false);
    if (!isNewEscalationsEnabled) {
        return (
            <Message
                type={"warning"}
                title={t("New Community Management System is disabled")}
                stringContents={t(
                    "New content will not appear on this page until the New Community Management System is enabled on the Content Settings page.",
                )}
                linkURL={"/dashboard/content/settings?highlight=new_community_management_system"}
                linkText={t("Go to Content Settings")}
            />
        );
    }
    return <></>;
}
