/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ReportReasonList } from "@dashboard/communityManagementSettings/ReportReasonList";
import { ModerationAdminLayout } from "@dashboard/components/navigation/ModerationAdminLayout";
import { useDashboardSectionActions } from "@dashboard/DashboardSectionHooks";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardInputWrap } from "@dashboard/forms/DashboardInputWrap";
import { dashboardClasses } from "@dashboard/forms/dashboardStyles";
import { DashboardToggle } from "@dashboard/forms/DashboardToggle";
import { css, cx } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { useConfigPatcher, useConfigsByKeys } from "@library/config/configHooks";
import Translate from "@library/content/Translate";
import { useToast } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import ModalConfirm from "@library/modal/ModalConfirm";
import SmartLink from "@library/routing/links/SmartLink";
import { getMeta } from "@library/utility/appUtils";
import { useMutation } from "@tanstack/react-query";
import { t } from "@vanilla/i18n";
import { useEffect, useState } from "react";

const BETA_ENABLED = getMeta("featureFlags.CommunityManagementBeta.Enabled", false);
export const CONF_ESCALATIONS_ENABLED = "escalations.enabled";
const CONF_TRIAGE_ENABLED = "triage.enabled";
const CONF_DISCUSSION_THREAD = "customLayout.post";

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

    // Force escalations off if beta is disabled.
    useEffect(() => {
        if (!BETA_ENABLED && isEscalationsEnabled) {
            void escalationsPatcher
                .patchConfig({
                    [CONF_ESCALATIONS_ENABLED]: false,
                })
                .then(() => {
                    fetchDashboardSections();
                });
        }
    }, [isEscalationsEnabled]);

    return (
        <ModerationAdminLayout
            title={t("Content Settings")}
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
                                    void escalationsPatcher
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
                                void triagePatcher
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
                void resolveAllMutation.mutateAsync().then(() => {
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
