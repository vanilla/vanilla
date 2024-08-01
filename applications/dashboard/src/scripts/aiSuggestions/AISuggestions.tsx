/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useAISuggestionsSettings, useDependenciesEnabled } from "@dashboard/aiSuggestions/AISuggestions.hooks";
import { SettingsForm } from "@dashboard/aiSuggestions/components/SettingsForm";
import { getSettingsSchema } from "@dashboard/aiSuggestions/settingsSchemaUtils";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { DashboardHelpAsset } from "@dashboard/forms/DashboardHelpAsset";
import Translate from "@library/content/Translate";
import { ErrorPageBoundary } from "@library/errorPages/ErrorPageBoundary";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import Message from "@library/messages/Message";
import SmartLink from "@library/routing/links/SmartLink";
import { t } from "@library/utility/appUtils";
import { MemoryRouter } from "react-router";

export function AISuggestions() {
    const dependenciesEnabled = useDependenciesEnabled();
    const { data: settings, error } = useAISuggestionsSettings();
    const sections = getSettingsSchema(settings);
    const title = t("AI Suggested Answers");

    return (
        <MemoryRouter>
            <ErrorPageBoundary>
                {dependenciesEnabled && sections && !error ? (
                    <SettingsForm title={title} sections={sections} settings={settings} />
                ) : (
                    <>
                        <DashboardHeaderBlock title={title} />
                        {error ? (
                            <Message type="error" error={error} />
                        ) : (
                            <>
                                {!dependenciesEnabled && (
                                    <Message
                                        type="error"
                                        stringContents={t(
                                            "Contact Vanilla Staff to get the Q&A site configuration enabled. Then enable the Q&A addon in the Addons section of the dashboard.",
                                        )}
                                        title={t("Feature is not configured")}
                                        contents={
                                            <Translate
                                                source="Contact Vanilla Staff to get the Q&A site configuration enabled. Then enable the Q&A addon in the <0/> section of the dashboard."
                                                c0={
                                                    <SmartLink to="/settings/addons#qna-addon">{t("Addons")}</SmartLink>
                                                }
                                            />
                                        }
                                    />
                                )}
                                {!sections && (
                                    <>
                                        <LoadingSpacer height={16} />
                                        <LoadingRectangle height={32} />
                                        <LoadingSpacer height={16} />
                                        <LoadingRectangle height={32} />
                                        <LoadingSpacer height={16} />
                                        <LoadingRectangle height={32} />
                                        <LoadingSpacer height={16} />
                                        <LoadingRectangle height={32} />
                                    </>
                                )}
                            </>
                        )}
                    </>
                )}
                <DashboardHelpAsset>
                    <Translate
                        source="AI Suggested Answers provides suggested answers to users based on community posts, knowledge base articles, and Zendesk articles and allows users to mark them as accepted. <0/>."
                        c0={
                            <SmartLink to="https://success.vanillaforums.com/kb/articles/1606-ai-suggested-answers">
                                {t("Read more in the documentation")}
                            </SmartLink>
                        }
                    />
                </DashboardHelpAsset>
            </ErrorPageBoundary>
        </MemoryRouter>
    );
}
