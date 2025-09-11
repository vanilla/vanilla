/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useState, useEffect } from "react";
import dompurify from "dompurify";
import { useQueryClient } from "@tanstack/react-query";
import { Icon, IconType } from "@vanilla/icons";
import { t, getMeta, formatUrl } from "@library/utility/appUtils";
import PanelWidget from "@library/layout/components/PanelWidget";
import { userContentClasses } from "@library/content/UserContent.styles";
import Loader from "@library/loaders/Loader";
import { loaderClasses } from "@library/loaders/loaderStyles";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { LoadingRectangle, LoadingSpacer } from "@library/loaders/LoadingRectangle";
import aiSearchSummaryClasses from "@library/search/AiSearchSummary.classes";
import {
    useAskCommunity,
    useGetConversation,
    useGetConversations,
    useStartConversation,
} from "@library/aiConversations/AiConversations.hooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { AiChatInterfaceModal } from "@library/aiConversations/AiChatInterface";
import { IReference } from "@library/aiConversations/AiConversations.types";
import { MetaItem, MetaTag } from "@library/metas/Metas";
import { TagPreset } from "@library/metas/Tags.variables";
import { DraftsApi } from "@vanilla/addon-vanilla/drafts/DraftsApi";
import { DraftRecordType } from "@vanilla/addon-vanilla/drafts/types";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { usePermissionsContext } from "@library/features/users/PermissionsContext";
import { IconMap } from "@library/aiConversations/AiConversations.fixtures";

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
    const { hasPermission } = usePermissionsContext();
    const queryClient = useQueryClient();

    const isAiConversationEnabled = getMeta("featureFlags.aiConversation.Enabled", false);
    const hasAiConversationPermission = hasPermission("aiAssistedSearch.view");
    const isEnabled = isAiConversationEnabled && hasAiConversationPermission;

    const [aiConversationID, setAiConversationID] = useState<number | undefined>(undefined);

    const startConversation = useStartConversation();

    useEffect(() => {
        async function handleStartConvo() {
            if (isEnabled && query && query !== "") {
                setAiConversationID(undefined);
                const response = await startConversation({ body: query });
                setAiConversationID(response.conversationID);
                await queryClient.invalidateQueries({ queryKey: ["getAiConversations", "getAiConversation"] });
            }
        }

        void handleStartConvo();
    }, [query]);

    return (
        isEnabled && (
            <PanelWidget>
                <AiSearchSummary conversationID={aiConversationID} />
            </PanelWidget>
        )
    );
}

interface IAiSearchSourcesPanelProps {
    conversationID?: number;
}

export function AiSearchSourcesPanel(props: IAiSearchSourcesPanelProps) {
    const { conversationID } = props;

    // if we don't have a conversation ID, fetch the most recent one
    const { data: mostRecentConversation } = useGetConversations({ limit: 1 }, !conversationID);

    const conversationIDToUse = conversationID ?? mostRecentConversation?.[0]?.conversationID ?? 0;

    const { query: conversationQuery } = useGetConversation(
        { conversationID: conversationIDToUse },
        !!conversationIDToUse,
    );

    const lastMessageSources = conversationQuery?.data?.references;

    const [isAiModalVisible, setIsAiModalVisible] = useState(false);

    const askCommunityMutation = useAskCommunity();
    const askCommunity = askCommunityMutation.mutateAsync;

    const handleAskCommunity = async () => {
        if (conversationID ?? mostRecentConversation?.[0]?.conversationID) {
            const response = await askCommunity({ conversationID: conversationIDToUse });

            if (response) {
                DraftsApi.post({
                    recordType: DraftRecordType.DISCUSSION,
                    attributes: {
                        body: response.body,
                        format: "rich2",
                        draftType: "discussion",
                        draftMeta: {
                            name: response.name,
                            pinLocation: "none",
                            pinned: false,
                            categoryID: response.categoryID,
                            postTypeID: response.postType,
                        },
                        name: response.name,
                    },
                    parentRecordID: response.categoryID,
                    parentRecordType: "category",
                })
                    .then((result) => {
                        window.location.href = formatUrl(`post/editdiscussion/0/${result.draftID}`);
                    })
                    .catch((error) => {
                        console.error("DraftsApi.post error", error);
                    });
            }
        }
    };

    const askCommunityLoadingOrSuccess = askCommunityMutation.isLoading || askCommunityMutation.isSuccess;

    const { hasPermission } = usePermissionsContext();
    const isAiConversationEnabled = getMeta("featureFlags.aiConversation.Enabled", false);
    const hasAiConversationPermission = hasPermission("aiAssistedSearch.view");
    const isEnabled = isAiConversationEnabled && hasAiConversationPermission;

    return (
        isEnabled && (
            <div className={aiSearchSummaryClasses().sourcesPanel}>
                <AiChatInterfaceModal
                    isVisible={isAiModalVisible}
                    onClose={() => setIsAiModalVisible(false)}
                    conversationID={conversationID}
                />

                <Button
                    buttonType={ButtonTypes.OUTLINE}
                    onClick={() => setIsAiModalVisible(true)}
                    className={aiSearchSummaryClasses().sourcesModalButton}
                >
                    {t("Refine search with AI Assistant")}{" "}
                    <Icon
                        icon={"meta-external"}
                        size="compact"
                        className={aiSearchSummaryClasses().sourcesModalButtonIcon}
                    />
                </Button>

                <div className={aiSearchSummaryClasses().sourcesContainer}>
                    <AiSearchSources sources={lastMessageSources} currentModel={conversationQuery?.data?.source} />

                    <div className={aiSearchSummaryClasses().askCommunityCTA}>
                        <p className={aiSearchSummaryClasses().askCommunityText}>{t("AI can make mistakes.")}</p>

                        <Button
                            disabled={conversationIDToUse === undefined || askCommunityLoadingOrSuccess}
                            buttonType={ButtonTypes.PRIMARY}
                            onClick={handleAskCommunity}
                            ariaLabel={t("Ask the Community")}
                        >
                            {askCommunityLoadingOrSuccess ? <ButtonLoader /> : t("Ask the Community")}
                        </Button>
                    </div>
                </div>
            </div>
        )
    );
}

interface IAiSearchSourcesProps {
    sources?: IReference[];
    currentModel?: string;
}
export function AiSearchSources(props: IAiSearchSourcesProps) {
    const { sources, currentModel } = props;

    if (!sources || sources.length === 0) {
        return null;
    }

    // Assumes the model is either OPENAIVNRAGBOT or WATSONXVNRAGBOT
    const urlParams =
        currentModel === "WATSONXVNRAGBOT"
            ? `?utm_source=ai-assistant&utm_medium=search-summary&utm_campaign=watsonX`
            : `?utm_source=ai-assistant&utm_medium=search-summary&utm_campaign=openAI`;

    return (
        <>
            <h3 className={aiSearchSummaryClasses().sourcesContainerTitle}>
                {t("Sources")}{" "}
                <span className={aiSearchSummaryClasses().sourcesContainerTitleNumber}>{sources.length}</span>
            </h3>

            <ol className={aiSearchSummaryClasses().sourcesList}>
                {sources.map((source) => {
                    const icon = IconMap[source.recordType as keyof typeof IconMap] ?? "meta-discussions";

                    return (
                        <li key={source.recordID} className={aiSearchSummaryClasses().sourcesListItem}>
                            <div className={"contents"}>
                                <a
                                    href={`${source.url}${urlParams}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className={aiSearchSummaryClasses().sourcesListItemLink}
                                >
                                    <h4>{source.name}</h4>
                                </a>

                                <div className={aiSearchSummaryClasses().sourcesListItemMeta}>
                                    <MetaTag
                                        tagPreset={TagPreset.GREYSCALE}
                                        className={aiSearchSummaryClasses().sourcesListItemMetaTag}
                                    >
                                        <Icon icon={icon as IconType} size={"compact"} />

                                        <MetaItem>{source.recordType}</MetaItem>
                                    </MetaTag>
                                </div>
                            </div>
                        </li>
                    );
                })}
            </ol>
        </>
    );
}
