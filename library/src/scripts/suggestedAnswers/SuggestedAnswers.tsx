/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import Translate from "@library/content/Translate";
import { useToastErrorHandler } from "@library/features/toaster/ToastContext";
import { useCurrentUser } from "@library/features/users/userHooks";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { DotLoader } from "@library/loaders/DotLoader";
import { SuggestedAnswerItem } from "@library/suggestedAnswers/SuggestedAnswerItem";
import { suggestedAnswersClasses } from "@library/suggestedAnswers/SuggestedAnswers.classes";
import {
    SuggestedAnswersProvider,
    useSuggestedAnswerContext,
} from "@library/suggestedAnswers/SuggestedAnswers.context";
import { ISuggestedAnswer } from "@library/suggestedAnswers/SuggestedAnswers.variables";
import { SuggestedAnswersOptionsMenu } from "@library/suggestedAnswers/SuggestedAnswersOptionsMenu";
import { ToggleSuggestions } from "@library/suggestedAnswers/ToggleSuggestions";
import { getMeta, t } from "@library/utility/appUtils";
import { useDiscussionQuery } from "@vanilla/addon-vanilla/thread/DiscussionThread.hooks";
import { Icon } from "@vanilla/icons";
import { useMemo, useState } from "react";
import { animated, useSpring } from "react-spring";
import { useGenerateSuggestions } from "./SuggestedAnswers.hooks";

interface ISuggestedAnswersAssetProps {
    discussion: IDiscussion;
    discussionApiParams?: DiscussionsApi.GetParams;
    isPreview?: boolean;
}

export function SuggestedAnswersAsset(props: ISuggestedAnswersAssetProps) {
    const { discussion: discussionPreload, discussionApiParams, isPreview } = props;
    const { discussionID } = discussionPreload;
    const currentUser = useCurrentUser();

    const {
        query: { data },
        invalidate: onMutateSuccess,
    } = useDiscussionQuery(discussionID, discussionApiParams, discussionPreload);

    const discussion = data!;

    const hasBeenEdited = useMemo<boolean>(() => {
        const referrer = document.referrer;
        return referrer.includes(`/post/editdiscussion/${discussionID}`);
    }, [discussionID]);

    const suggestionsEnabledForUser = currentUser?.userID === discussion.insertUserID && currentUser?.suggestAnswers;

    const [showSuggestions, setShowSuggestions] = useState<boolean>(
        ((discussion.suggestions?.length ?? 0) > 0 || hasBeenEdited) && (!!suggestionsEnabledForUser || !!isPreview),
    );

    if (showSuggestions) {
        return (
            <SuggestedAnswersProvider value={{ onMutateSuccess, discussionID, toggleSuggestions: setShowSuggestions }}>
                <SuggestedAnswers
                    suggestions={discussion.suggestions ?? []}
                    showSuggestions={discussion.showSuggestions}
                    postHasBeenEdited={hasBeenEdited}
                />
            </SuggestedAnswersProvider>
        );
    }

    return null;
}

interface ISuggestedAnswersProps {
    suggestions: ISuggestedAnswer[];
    showSuggestions?: boolean;
    postHasBeenEdited?: boolean;
    storyMode?: boolean; // for being able to see certain features in storybook
}

export function SuggestedAnswers(props: ISuggestedAnswersProps) {
    const { suggestions, showSuggestions = true, postHasBeenEdited = false, storyMode = false } = props;
    const classes = suggestedAnswersClasses();
    const { discussionID, onMutateSuccess } = useSuggestedAnswerContext();
    const generateSuggestions = useGenerateSuggestions(discussionID);
    const toastError = useToastErrorHandler();
    const aiAssistant = getMeta("aiAssistant", { name: "AI Assistant" });
    const [isGenerating, setIsGenerating] = useState<boolean>(false);
    const [showRegeneration, setShowRegeneration] = useState<boolean>(postHasBeenEdited);
    const { height } = useSpring({ height: showSuggestions ? "auto" : 1 });
    const showActions = !showRegeneration && !isGenerating;

    const filteredSuggestions = suggestions.filter(({ hidden, commentID }) => !hidden && !commentID);

    const handleRegenerateSuggestions = async () => {
        setShowRegeneration(false);
        setIsGenerating(true);

        // In storybook, pressing the regeneration button should just show what the regeneration notice looks like
        if (!storyMode) {
            try {
                await generateSuggestions();
                onMutateSuccess?.();
            } catch (err) {
                toastError(err);
                setIsGenerating(false);
                onMutateSuccess?.();
            }
        }
    };

    return (
        <div className={classes.root}>
            <div className={classes.header}>
                <div className={classes.headerContent}>
                    <div className={classes.user}>
                        <UserPhoto userInfo={aiAssistant} size={UserPhotoSize.XSMALL} />
                        <span className={classes.userName}>{aiAssistant.name}</span>
                        <Icon icon="ai-sparkle-monocolor" size="compact" />
                    </div>
                    {showActions && (
                        <>
                            <p className={classes.helperText}>
                                {t("Suggestions are only visible to you until you accept the answer")}
                            </p>
                            <ToggleSuggestions visible={showSuggestions} />
                        </>
                    )}
                </div>
                <SuggestedAnswersOptionsMenu
                    suggestions={suggestions}
                    regenerateSuggestions={handleRegenerateSuggestions}
                    showActions={showActions}
                />
            </div>
            {showRegeneration ? (
                <div className={classes.regenerateBox}>
                    <Icon icon="notification-alert" />
                    <p>
                        <Translate
                            source="It looks like you edited your post. <0/> or <1/>."
                            c0={
                                <Button buttonType={ButtonTypes.TEXT_PRIMARY} onClick={handleRegenerateSuggestions}>
                                    {t("Regenerate Suggestions")}
                                </Button>
                            }
                            c1={
                                <Button
                                    buttonType={ButtonTypes.TEXT_PRIMARY}
                                    onClick={() => setShowRegeneration(false)}
                                >
                                    {t("Show Original Suggestions")}
                                </Button>
                            }
                        />
                    </p>
                </div>
            ) : isGenerating ? (
                <>
                    <p className={classes.helperText}>
                        {t("Generating Suggestions, this may take some time.")}
                        <span>
                            {t(
                                "You will be notified when suggestions are ready for you to review. You may navigate away or refresh the page.",
                            )}
                        </span>
                    </p>
                </>
            ) : (
                <animated.div style={{ height }} className={classes.contentBox} aria-expanded={showSuggestions}>
                    {filteredSuggestions.length ? (
                        <>
                            <p className={classes.intro}>
                                <strong>{t(`"Accept Answer" if a suggestion answers your question.`)}</strong>
                                {t(
                                    "This will guide other users with similar questions to the right answers faster and will display the AI Suggested Answer and link the referenced material for other users to see. Suggestions are only visible to you until you accept the answer.",
                                )}
                            </p>
                            <ul className={classes.list}>
                                {filteredSuggestions.map((item) => (
                                    <SuggestedAnswerItem key={item.aiSuggestionID} {...item} />
                                ))}
                            </ul>
                        </>
                    ) : (
                        <p className={classes.helperText}>{t("No further suggestions.")}</p>
                    )}
                </animated.div>
            )}
        </div>
    );
}

export default SuggestedAnswersAsset;
