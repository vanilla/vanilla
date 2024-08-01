/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { AutomationRulesProvider } from "@dashboard/automationRules/AutomationRules.context";
import { AutomationRulesList } from "@dashboard/automationRules/AutomationRulesList";
import AdminLayout from "@dashboard/components/AdminLayout";
import { ModerationNav } from "@dashboard/components/navigation/ModerationNav";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import { TitleBarDevices, useTitleBarDevice } from "@library/layout/TitleBarContext";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@vanilla/i18n";
import { useCollisionDetector } from "@vanilla/react-utils";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { useState } from "react";
import ModalConfirm from "@library/modal/ModalConfirm";
import Button from "@library/forms/Button";
import { communityManagementPageClasses } from "@dashboard/moderation/CommunityManagementPage.classes";

export default function EscalationRulesPage() {
    const cmdClasses = communityManagementPageClasses();
    const device = useTitleBarDevice();
    const { hasCollision } = useCollisionDetector();
    const isCompact = hasCollision || device === TitleBarDevices.COMPACT;
    const [rulesMaxLimitReached, setRulesMaxLimitReached] = useState<boolean>(false);
    const [maxLimitModalVisible, setMaxLimitModalVisible] = useState<boolean>(false);

    return (
        <AutomationRulesProvider isEscalationRulesMode>
            <ErrorPageBoundary>
                <>
                    <AdminLayout
                        title={t("Escalation Rules")}
                        leftPanel={!isCompact && <ModerationNav />}
                        rightPanel={
                            <>
                                <h3>{t("Escalation Rules").toLocaleUpperCase()}</h3>
                                <p>{t("Create and manage automations to easily manage posts and users.")}</p>
                                <SmartLink to={"https://success.vanillaforums.com/kb/articles/1569-automation-rules"}>
                                    {t("See documentation for more information.")}
                                </SmartLink>
                            </>
                        }
                        content={
                            <AutomationRulesList
                                isEscalationRulesList
                                headerClassName={cmdClasses.secondaryTitleBar}
                                onRulesMaxLimitReach={setRulesMaxLimitReached}
                            />
                        }
                        titleBarActions={
                            rulesMaxLimitReached ? (
                                <Button buttonType={ButtonTypes.OUTLINE} onClick={() => setMaxLimitModalVisible(true)}>
                                    {t("Add Rule")}
                                </Button>
                            ) : (
                                <LinkAsButton
                                    buttonType={ButtonTypes.OUTLINE}
                                    to={"/dashboard/content/escalation-rules/add"}
                                >
                                    {t("Add Rule")}
                                </LinkAsButton>
                            )
                        }
                    />
                    <ModalConfirm
                        isVisible={maxLimitModalVisible}
                        title={t("Maximum Limit Reached")}
                        onCancel={() => setMaxLimitModalVisible(false)}
                        cancelTitle={t("Close")}
                    >
                        {t("You cannot add more than 150 automation rules. Delete some rules and try again.")}
                    </ModalConfirm>
                </>
            </ErrorPageBoundary>
        </AutomationRulesProvider>
    );
}
