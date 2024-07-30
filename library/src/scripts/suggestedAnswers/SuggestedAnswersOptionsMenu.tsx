/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import Translate from "@library/content/Translate";
import { useToast, useToastErrorHandler } from "@library/features/toaster/ToastContext";
import { IPatchUserParams } from "@library/features/users/UserActions";
import { useCurrentUser, usePatchUser } from "@library/features/users/userHooks";
import DropDown, { DropDownOpenDirection, FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import ModalConfirm from "@library/modal/ModalConfirm";
import ModalSizes from "@library/modal/ModalSizes";
import { useSuggestedAnswerContext } from "@library/suggestedAnswers/SuggestedAnswers.context";
import { useAcceptSuggestion, useRestoreSuggestions } from "@library/suggestedAnswers/SuggestedAnswers.hooks";
import { ISuggestedAnswer } from "@library/suggestedAnswers/SuggestedAnswers.variables";
import { getMeta, t } from "@library/utility/appUtils";
import { Icon } from "@vanilla/icons";
import { useState } from "react";

interface IProps {
    suggestions: ISuggestedAnswer[];
    regenerateSuggestions: () => void;
    showActions?: boolean;
}

export function SuggestedAnswersOptionsMenu(props: IProps) {
    const { suggestions, regenerateSuggestions, showActions = true } = props;
    const { discussionID, onMutateSuccess, toggleSuggestions } = useSuggestedAnswerContext();
    const toast = useToast();
    const toastError = useToastErrorHandler();
    const restoreSuggestions = useRestoreSuggestions(discussionID);
    const acceptSuggestions = useAcceptSuggestion(discussionID);
    const currentUser = useCurrentUser();
    const { patchUser } = usePatchUser(currentUser?.userID as number);
    const aiAssistant = getMeta("aiAssistant", { name: "AI Assistant" });

    const [menuVisible, setMenuVisible] = useState<boolean>(false);
    const [whyModalVisible, setWhyModalVisible] = useState<boolean>(false);
    const [disableModalVisible, setDisableModalVisible] = useState<boolean>(false);

    const hasDismissedSuggestions = suggestions.filter(({ hidden }) => hidden).length > 0;
    const hasSuggestions = suggestions.filter(({ hidden, commentID }) => !hidden && !commentID).length > 0;

    const handleRestoreSuggestions = async () => {
        setMenuVisible(false);
        try {
            await restoreSuggestions();
            onMutateSuccess?.();
        } catch (err) {
            toastError(err);
        }
    };

    const handleAcceptAll = async () => {
        setMenuVisible(false);
        try {
            await acceptSuggestions({ suggestion: "all", accept: true });
            onMutateSuccess?.();
        } catch (err) {
            toastError(err);
        }
    };

    const handleDisableAiSuggestions = async () => {
        setMenuVisible(false);
        try {
            const patchParams: IPatchUserParams = {
                userID: currentUser?.userID as number,
                suggestAnswers: false,
            };
            await patchUser(patchParams);
            setWhyModalVisible(false);
            setDisableModalVisible(false);
            toggleSuggestions?.(false);
            toast.addToast({
                autoDismiss: true,
                body: <Translate source="<0 /> will not suggest answers on Q&A posts" c0={aiAssistant.name} />,
            });
        } catch (err) {
            toastError(err);
        }
    };

    const handleRegenerateSuggestions = async () => {
        setMenuVisible(false);
        regenerateSuggestions();
    };

    return (
        <>
            <DropDown
                name={t("Suggested Answers Options")}
                buttonContents={<Icon icon="navigation-circle-ellipsis" />}
                openDirection={DropDownOpenDirection.BELOW_LEFT}
                flyoutType={FlyoutType.LIST}
                isVisible={menuVisible}
                onVisibilityChange={setMenuVisible}
            >
                <DropDownItemButton onClick={() => setWhyModalVisible(true)}>
                    {t("Why am I seeing this?")}
                </DropDownItemButton>
                <DropDownItemButton onClick={() => setDisableModalVisible(true)}>
                    {t("Turn off AI Suggested Answers")}
                </DropDownItemButton>
                {showActions && (
                    <>
                        {hasDismissedSuggestions && (
                            <DropDownItemButton onClick={handleRestoreSuggestions}>
                                {t("Show Dismissed Suggestions")}
                            </DropDownItemButton>
                        )}
                        {hasSuggestions && (
                            <DropDownItemButton onClick={handleAcceptAll}>
                                {t("Mark All Suggested Answers as Accepted")}
                            </DropDownItemButton>
                        )}
                        <DropDownItemButton onClick={handleRegenerateSuggestions}>
                            {t("Regenerate AI Suggestions")}
                        </DropDownItemButton>
                    </>
                )}
            </DropDown>
            <ModalConfirm
                isVisible={whyModalVisible}
                title={t("Why am I seeing AI Suggested Answers?")}
                onCancel={() => setWhyModalVisible(false)}
                onConfirm={handleDisableAiSuggestions}
                confirmTitle={t("Turn off for now")}
                size={ModalSizes.MEDIUM}
            >
                {t(
                    "AI Suggested Answers provides suggested answers based on community posts and linked knowledge bases. You can turn this on or off anytime by visiting your profile preferences.",
                )}
            </ModalConfirm>
            <ModalConfirm
                isVisible={disableModalVisible}
                title={t("Turn off AI Suggested Answers")}
                onCancel={() => setDisableModalVisible(false)}
                onConfirm={handleDisableAiSuggestions}
                confirmTitle={t("Yes")}
                size={ModalSizes.MEDIUM}
            >
                {t(
                    "Are you sure you want to turn off all AI Suggested Answers? You can undo this in your profile settings at anytime.",
                )}
            </ModalConfirm>
        </>
    );
}
