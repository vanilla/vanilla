/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useToastErrorHandler } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { suggestedAnswersClasses } from "@library/suggestedAnswers/SuggestedAnswers.classes";
import { useSuggestedAnswerContext } from "@library/suggestedAnswers/SuggestedAnswers.context";
import { useToggleSuggestionsVisibility } from "@library/suggestedAnswers/SuggestedAnswers.hooks";
import { t } from "@library/utility/appUtils";

interface IProps {
    visible: boolean;
}

export function ToggleSuggestions(props: IProps) {
    const { visible } = props;
    const classes = suggestedAnswersClasses();
    const { discussionID, onMutateSuccess } = useSuggestedAnswerContext();
    const toggleVisibility = useToggleSuggestionsVisibility(discussionID);
    const toastError = useToastErrorHandler();

    const handleButtonClick = async () => {
        try {
            await toggleVisibility(!visible);
            await onMutateSuccess?.();
        } catch (err) {
            toastError(err);
        }
    };

    return (
        <Button buttonType={ButtonTypes.TEXT_PRIMARY} className={classes.toggleVisibility} onClick={handleButtonClick}>
            {visible ? t("Hide Suggestions") : t("Show Suggestions")}
        </Button>
    );
}
