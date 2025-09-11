/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import dompurify from "dompurify";
import { cx } from "@emotion/css";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import { getMeta } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { useCurrentUser } from "@library/features/users/userHooks";
import ProfileLink from "@library/navigation/ProfileLink";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { IMessage } from "@library/aiConversations/AiConversations.types";
import { aiChatStyles } from "@library/aiConversations/AiChatInterface.styles";
import MessageSources from "@library/aiConversations/MessageSources";
import Notice from "@library/metas/Notice";
import { ToolTip } from "@library/toolTip/ToolTip";

interface IChatMessageProps {
    message: IMessage;
    isAssistant?: boolean;
    currentModel?: string;
    handleReaction?: (message: IMessage, reaction: "like" | "dislike") => void;
    hasBeenLiked?: boolean;
    hasBeenDisliked?: boolean;
}

export default function ChatMessage(props: IChatMessageProps) {
    const { message, isAssistant, currentModel, handleReaction, hasBeenLiked, hasBeenDisliked } = props;

    const classes = aiChatStyles();
    const humanUser = useCurrentUser();

    const aiSuggestionsEnabled = getMeta("featureFlags.AISuggestions.Enabled", false);
    const aiAssistantSettings = aiSuggestionsEnabled ? getMeta("aiAssistant") : undefined;

    return (
        <div
            key={message?.messageID ?? "starter-message"}
            className={cx(classes.message, isAssistant ? classes.messageAssistant : classes.messageHuman)}
        >
            <span className={classes.messageMeta}>
                {isAssistant ? (
                    <>
                        {aiAssistantSettings?.photoUrl ? (
                            <img src={aiAssistantSettings.photoUrl} className={classes.aiAssistantPhoto} />
                        ) : (
                            <Icon icon={"ai-indicator"} size="compact" />
                        )}

                        <span>{aiAssistantSettings?.name ? aiAssistantSettings.name : t("AI Assistant")}</span>

                        <ToolTip
                            label={t(
                                "This chatbot uses artificial intelligence to understand your questions and deliver helpful answers based on the content available within this site—limited to what you have permission to view. If no relevant community content is found, it may draw on general information from the AI’s training data. While the assistant strives to be accurate, it may occasionally provide incorrect or outdated information—so it's always a good idea to verify important answers.",
                            )}
                        >
                            <span className={classes.aiNoticeMessage}>
                                <Notice>{t("AI")}</Notice>
                            </span>
                        </ToolTip>
                    </>
                ) : (
                    humanUser && (
                        <>
                            <ProfileLink userFragment={humanUser} isUserCard>
                                <UserPhoto size={UserPhotoSize.SMALL} userInfo={humanUser} />
                            </ProfileLink>
                            <span>{humanUser.name}</span>
                        </>
                    )
                )}
            </span>

            {message && <div dangerouslySetInnerHTML={{ __html: dompurify.sanitize(message.body as string) }} />}

            {isAssistant && message && handleReaction && (
                <>
                    <div className={classes.reactionButtonContainer}>
                        <Button
                            onClick={() => handleReaction(message, "like")}
                            buttonType={ButtonTypes.ICON_COMPACT}
                            ariaLabel={t("Like")}
                            className={cx({
                                [classes.reactionButtonActive]: hasBeenLiked,
                            })}
                        >
                            <Icon icon={"reaction-thumbs-up"} size="compact" />
                        </Button>

                        <Button
                            onClick={() => handleReaction(message, "dislike")}
                            buttonType={ButtonTypes.ICON_COMPACT}
                            ariaLabel={t("Dislike")}
                            className={cx({
                                [classes.reactionButtonActive]: hasBeenDisliked,
                            })}
                        >
                            <Icon icon={"reaction-thumbs-down"} size="compact" />
                        </Button>

                        <Button
                            onClick={async () => {
                                return await navigator.clipboard.writeText(message.body as string);
                            }}
                            buttonType={ButtonTypes.ICON_COMPACT}
                            ariaLabel={t("Copy Message Text")}
                        >
                            <Icon icon={"copy"} size="compact" />
                        </Button>
                    </div>

                    <MessageSources message={message} currentModel={currentModel} />
                </>
            )}
        </div>
    );
}
