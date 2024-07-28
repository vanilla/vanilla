/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ReportReasonList } from "@dashboard/communityManagementSettings/ReportReasonList";
import AdminLayout from "@dashboard/components/AdminLayout";
import { ModerationNav } from "@dashboard/components/navigation/ModerationNav";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { LoadStatus } from "@library/@types/api/core";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import Translate from "@library/content/Translate";
import { FormToggle } from "@library/forms/FormToggle";
import { useTitleBarDevice, TitleBarDevices } from "@library/layout/TitleBarContext";
import SmartLink from "@library/routing/links/SmartLink";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { useCollisionDetector } from "@vanilla/react-utils";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";
import { css, cx } from "@emotion/css";
import { useDashboardSectionActions } from "@dashboard/DashboardSectionHooks";
import { getMeta } from "@library/utility/appUtils";
import { useEffect } from "react";

const BETA_ENABLED = getMeta("featureFlags.CommunityManagementBeta.Enabled", false);
export const CONF_ESCALATIONS_ENABLED = "escalations.enabled";
const CONF_TRIAGE_ENABLED = "triage.enabled";
const CONF_DISCUSSION_THREAD = "customLayout.discussionThread";

const classes = {
    main: css({
        padding: "0 18px",
    }),
};

export default function ModerationContentSettingsPage() {
    const configs = useConfigsByKeys([CONF_ESCALATIONS_ENABLED, CONF_DISCUSSION_THREAD, CONF_TRIAGE_ENABLED]);
    const escalationsPatcher = useConfigPatcher();
    const triagePatcher = useConfigPatcher();
    const { fetchDashboardSections } = useDashboardSectionActions();
    const isCmdHighlighted = new URL(window.location.href).searchParams.get("highlight");

    const isConfigLoading = [LoadStatus.PENDING, LoadStatus.LOADING].includes(configs.status);
    const isEscalationsLoading = isConfigLoading || escalationsPatcher.isLoading;
    const isCustomDiscussionThreadsEnabled = configs.data?.[CONF_DISCUSSION_THREAD] ?? false;
    const isEscalationsEnabled: boolean = configs.data?.[CONF_ESCALATIONS_ENABLED] ?? false;

    const isTriageLoading = isConfigLoading || triagePatcher.isLoading;
    const isTriageEnabled: boolean = configs.data?.[CONF_TRIAGE_ENABLED] ?? false;

    let toggle = (
        <span className="input-wrap">
            <FormToggle
                enabled={isEscalationsEnabled}
                indeterminate={isEscalationsLoading}
                disabled={isEscalationsLoading || (isCustomDiscussionThreadsEnabled && isEscalationsEnabled)}
                onChange={(enabled) => {
                    escalationsPatcher
                        .patchConfig({
                            [CONF_ESCALATIONS_ENABLED]: enabled,
                        })
                        .then(() => {
                            // reload dashboard sections.
                            fetchDashboardSections();
                        });
                }}
            />
        </span>
    );

    if (isCustomDiscussionThreadsEnabled && isEscalationsEnabled) {
        toggle = (
            <ToolTip label={t("This setting must be enabled to use Custom Discussion Threads.")}>{toggle}</ToolTip>
        );
    }

    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;

    const cmdClasses = communityManagementPageClasses();

    // Force escalations off if beta is disabled.
    useEffect(() => {
        if (!BETA_ENABLED && isEscalationsEnabled) {
            escalationsPatcher
                .patchConfig({
                    [CONF_ESCALATIONS_ENABLED]: false,
                })
                .then(() => {
                    fetchDashboardSections();
                });
        }
    }, [isEscalationsEnabled]);

    return (
        <AdminLayout
            title={t("Content Settings")}
            leftPanel={!isCompact && <ModerationNav />}
            rightPanel={
                <>
                    <h3>{t("Heads Up!")}</h3>
                    <p>{t("Configure where reports are sent and manage your community's report reasons.")}</p>
                </>
            }
            content={
                <section className={classes.main}>
                    {BETA_ENABLED && (
                        <DashboardFormGroup
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
                            className={cx(
                                dashboardClasses().spaceBetweenFormGroup,
                                isCmdHighlighted && dashboardClasses().highlight,
                            )}
                        >
                            {toggle}
                        </DashboardFormGroup>
                    )}
                    <DashboardFormGroup
                        label={t("Enable Triage Dashboard")}
                        description={t(
                            "All users with the staff permission will be able to see and mark discussions as resolved or unresolved. These users will also be able to access the triage dashboard to moderate their categories.",
                        )}
                        className={dashboardClasses().spaceBetweenFormGroup}
                    >
                        <span className="input-wrap">
                            <FormToggle
                                indeterminate={isTriageLoading}
                                enabled={isTriageEnabled}
                                onChange={(enabled) => {
                                    triagePatcher
                                        .patchConfig({
                                            [CONF_TRIAGE_ENABLED]: enabled,
                                        })
                                        .then(() => {
                                            // reload dashboard sections.
                                            fetchDashboardSections();
                                        });
                                }}
                            />
                        </span>
                    </DashboardFormGroup>
                    {BETA_ENABLED && <ReportReasonList />}
                </section>
            }
        />
    );
}
