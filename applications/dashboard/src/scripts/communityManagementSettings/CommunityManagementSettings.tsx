/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ReportReasonList } from "@dashboard/communityManagementSettings/ReportReasonList";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { LoadStatus } from "@library/@types/api/core";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import Translate from "@library/content/Translate";
import { FormToggle } from "@library/forms/FormToggle";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import { useUniqueID } from "@library/utility/idUtils";
import { t } from "@vanilla/i18n";
import { MemoryRouter } from "react-router";

const CONF_NEW_COMMUNITY_MANAGEMENT = "communityManagement.enabled";
const CONF_DISCUSSION_THREAD = "customLayout.discussionThread";

export function CommunityManagementSettings() {
    const configs = useConfigsByKeys([CONF_NEW_COMMUNITY_MANAGEMENT, CONF_DISCUSSION_THREAD]);
    const configPatcher = useConfigPatcher();

    const isToggleLoading =
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(configs.status) || configPatcher.isLoading;
    const isCustomDiscussionThreadsEnabled = configs.data?.[CONF_DISCUSSION_THREAD] ?? false;
    const isNewCommunityManagementEnabled = configs.data?.[CONF_NEW_COMMUNITY_MANAGEMENT] ?? false;

    const labelID = useUniqueID("formToggle");

    let toggle = (
        <span className="input-wrap">
            <FormToggle
                labelID={labelID}
                enabled={isNewCommunityManagementEnabled}
                indeterminate={isToggleLoading}
                disabled={isToggleLoading || (isCustomDiscussionThreadsEnabled && isNewCommunityManagementEnabled)}
                onChange={(enabled) => {
                    configPatcher.patchConfig({
                        [CONF_NEW_COMMUNITY_MANAGEMENT]: enabled,
                    });
                }}
            />
        </span>
    );

    if (isCustomDiscussionThreadsEnabled && isNewCommunityManagementEnabled) {
        toggle = (
            <ToolTip label={t("This setting must be enabled to use Custom Discussion Threads.")}>{toggle}</ToolTip>
        );
    }

    return (
        <MemoryRouter>
            <DashboardHeaderBlock title={t("Community Management Settings")} />
            <section>
                <DashboardFormGroup
                    labelID={labelID}
                    label={t("New Community Management System")}
                    description={
                        <Translate
                            source="The new community management system allows for custom reporting reasons, escalations, assignemnts, and automation rules. Turning this changes reporting intake to use the new system. <0>Read More</0>."
                            c0={(content) => (
                                <SmartLink to="https://success.vanillaforums.com/kb/articles/1619-vanillas-new-community-management-system">
                                    {content}
                                </SmartLink>
                            )}
                        />
                    }
                    className={dashboardClasses().spaceBetweenFormGroup}
                >
                    {toggle}
                </DashboardFormGroup>
                <ReportReasonList />
            </section>
            <DashboardHelpAsset>
                <h3>{t("Heads Up!")}</h3>
                <p>{t("Configure where reports are sent and manage your community's report reasons.")}</p>
            </DashboardHelpAsset>
        </MemoryRouter>
    );
}
