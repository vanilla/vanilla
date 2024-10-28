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
import { css, cx } from "@emotion/css";
import { useDashboardSectionActions } from "@dashboard/DashboardSectionHooks";
import { getMeta } from "@library/utility/appUtils";
import { useEffect, useState } from "react";
import Button from "@library/forms/Button";
import ModalConfirm from "@library/modal/ModalConfirm";
import { useMutation } from "@tanstack/react-query";
import apiv2 from "@library/apiv2";
import { useToast } from "@library/features/toaster/ToastContext";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";

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
    const [showResolveAll, setShowResolveAll] = useState(false);

    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;

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
                                    source="The new community management system allows for custom reporting reasons, escalations, assignments, and automation rules. Enabling this changes reporting intake to use the new system. <0>Read More</0>."
                                    c0={(content) => (
                                        <SmartLink to="https://success.vanillaforums.com/kb/articles/1619-vanillas-new-community-management-system">
                                            {content}
                                        </SmartLink>
                                    )}
                                />
                            }
                            className={cx(isCmdHighlighted && dashboardClasses().highlight)}
                            labelType={DashboardLabelType.JUSTIFIED}
                        >
                            <DashboardToggle
                                enabled={isEscalationsEnabled}
                                indeterminate={isEscalationsLoading}
                                disabled={
                                    isEscalationsLoading || (isCustomDiscussionThreadsEnabled && isEscalationsEnabled)
                                }
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
                                tooltip={
                                    isCustomDiscussionThreadsEnabled && isEscalationsEnabled
                                        ? t("This setting must be enabled to use Custom Discussion Threads.")
                                        : undefined
                                }
                            />
                        </DashboardFormGroup>
                    )}
                    <DashboardFormGroup
                        label={t("Enable Triage Dashboard")}
                        description={t(
                            "All users with the staff permission will be able to see and mark discussions as resolved or unresolved. These users will also be able to access the triage dashboard to moderate their categories.",
                        )}
                        labelType={DashboardLabelType.JUSTIFIED}
                    >
                        <DashboardToggle
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
                    </DashboardFormGroup>
                    <DashboardFormGroup
                        label={t("Resolve All Discussions")}
                        description={t(
                            "Resolve All Posts will resolve all existing posts in the community. This bulk action will not trigger webhooks or analytics.",
                        )}
                        labelType={DashboardLabelType.JUSTIFIED}
                    >
                        <DashboardInputWrap>
                            <Button onClick={() => setShowResolveAll(true)}>{t("Resolve All")}</Button>
                        </DashboardInputWrap>
                    </DashboardFormGroup>
                    <ResolveAllModal isVisible={showResolveAll} setIsVisible={setShowResolveAll} />
                    {BETA_ENABLED && <ReportReasonList />}
                </section>
            }
        />
    );
}

function ResolveAllModal(props: { isVisible: boolean; setIsVisible: (isVisible: boolean) => void }) {
    const { fetchDashboardSections } = useDashboardSectionActions();
    const toast = useToast();
    const resolveAllMutation = useMutation({
        mutationFn: async () => {
            await apiv2.post("/discussions/resolve-bulk");
        },
        onSuccess() {
            toast.addToast({
                body: t("All posts have been resolved."),
                autoDismiss: true,
            });
            fetchDashboardSections();
            props.setIsVisible(false);
        },
    });
    return (
        <ModalConfirm
            onCancel={() => props.setIsVisible(false)}
            isVisible={props.isVisible}
            title={t("Resolve All")}
            confirmTitle={t("Continue")}
            isConfirmLoading={resolveAllMutation.isLoading}
            isConfirmDisabled={resolveAllMutation.isLoading}
            onConfirm={() => {
                resolveAllMutation.mutateAsync().then(() => {
                    props.setIsVisible(false);
                });
            }}
        >
            {t(
                "This will resolve all existing posts in the community. This bulk action will not trigger webhooks or log analytics. Continue?",
            )}
        </ModalConfirm>
    );
}
