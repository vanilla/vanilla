/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState, useEffect } from "react";
import dompurify from "dompurify";
import { useQueryClient } from "@tanstack/react-query";
import { Icon } from "@vanilla/icons";
import { t, getMeta } from "@library/utility/appUtils";
import PanelWidget from "@library/layout/components/PanelWidget";
import { userContentClasses } from "@library/content/UserContent.styles";
import Loader from "@library/loaders/Loader";
import { loaderClasses } from "@library/loaders/loaderStyles";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import aiSearchSummaryClasses from "@library/search/AiSearchSummary.classes";
import { useGetConversation, useStartConversation } from "@library/aiConversations/AiConversations.hooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { AiChatInterfaceModal } from "@library/aiConversations/AiChatInterface";
import { RecordID } from "@vanilla/utils";

interface IAiSearchResults {
    conversationID: number;
}

export function AiSearchResults(props: IAiSearchResults) {
    const { query: conversationQuery } = useGetConversation({ conversationID: props.conversationID });

    const lastMessageBody = conversationQuery?.data?.lastMessageBody;
    const sanitizedResponse = lastMessageBody ? dompurify.sanitize(lastMessageBody) : undefined;

    return (
        <div className={userContentClasses().root}>
            {conversationQuery.isError && <p>{t("We're having trouble connecting to the AI.")}</p>}

            {conversationQuery.isLoading && <Loader size={100} loaderStyleClass={loaderClasses().mediumLoader} />}

            {conversationQuery.isSuccess && sanitizedResponse && (
                <div dangerouslySetInnerHTML={{ __html: sanitizedResponse }} />
            )}
        </div>
    );
}

interface IAiSearchSummaryProps {
    conversationID?: number;
}

export default function AiSearchSummary(props: IAiSearchSummaryProps) {
    const { conversationID } = props;
    const [isAiModalVisible, setIsAiModalVisible] = useState(false);

    return (
        <div>
            <span className={aiSearchSummaryClasses().labelContainer}>
                <Icon icon={"ai-indicator"} size="compact" />
                <span className={aiSearchSummaryClasses().label}>{t("AI Summary")}</span>
            </span>

            <AiChatInterfaceModal
                isVisible={isAiModalVisible}
                onClose={() => setIsAiModalVisible(false)}
                conversationID={conversationID}
            />

            <div className={aiSearchSummaryClasses().resultsContainer}>
                {conversationID ? (
                    <AiSearchResults conversationID={conversationID} />
                ) : (
                    <>
                        <ScreenReaderContent>{t("Loading AI Summary")}</ScreenReaderContent>
                        <LoadingRectangle height={14} width={"95%"} />
                        <LoadingSpacer height={12} />
                        <LoadingRectangle height={14} width={"80%"} />
                        <LoadingSpacer height={12} />
                        <LoadingRectangle height={14} width={"82%"} />
                        <LoadingSpacer height={12} />
                        <LoadingRectangle height={14} width={"75%"} />
                        <LoadingSpacer height={12} />
                        <LoadingRectangle height={14} width={"85%"} />
                    </>
                )}

                <div className={aiSearchSummaryClasses().footer}>
                    <Button buttonType={ButtonTypes.STANDARD} onClick={() => setIsAiModalVisible(true)}>
                        <Icon
                            icon={"create-discussion"}
                            size="compact"
                            className={aiSearchSummaryClasses().iconInButton}
                        />
                        {t("Launch chat")}
                    </Button>
                </div>
            </div>
        </div>
    );
}
interface IAiSearchResultsPanelProps {
    query: string;
}

export function AiSearchResultsPanel(props: IAiSearchResultsPanelProps) {
    const { query } = props;

    const isRagSearchEnabled = getMeta("featureFlags.ragSearch.Enabled", false);
    const queryClient = useQueryClient();

    const [aiConversationID, setAiConversationID] = useState<number | undefined>(undefined);

    const startConversation = useStartConversation();

    useEffect(() => {
        async function handleStartConvo() {
            if (isRagSearchEnabled && query && query !== "") {
                setAiConversationID(undefined);
                const response = await startConversation({ body: query });
                setAiConversationID(response.conversationID);
                await queryClient.invalidateQueries({ queryKey: ["getAiConversations", "getAiConversation"] });
            }
        }

        void handleStartConvo();
    }, [query]);

    return (
        isRagSearchEnabled && (
            <PanelWidget>
                <AiSearchSummary conversationID={aiConversationID} />
            </PanelWidget>
        )
    );
}
