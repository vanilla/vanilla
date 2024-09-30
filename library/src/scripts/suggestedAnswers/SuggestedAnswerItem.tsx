/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import { CollapsableContent } from "@library/content/CollapsableContent";
import { useToastErrorHandler } from "@library/features/toaster/ToastContext";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import SmartLink from "@library/routing/links/SmartLink";
import { suggestedAnswersClasses } from "@library/suggestedAnswers/SuggestedAnswers.classes";
import { useSuggestedAnswerContext } from "@library/suggestedAnswers/SuggestedAnswers.context";
import { useAcceptSuggestion, useDismissSuggestion } from "@library/suggestedAnswers/SuggestedAnswers.hooks";
import { ISuggestedAnswer } from "@library/suggestedAnswers/SuggestedAnswers.variables";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@library/utility/appUtils";
import { Icon } from "@vanilla/icons";
import startCase from "lodash-es/startCase";
import { useState } from "react";

export function SuggestedAnswerItem(props: ISuggestedAnswer) {
    const classes = suggestedAnswersClasses();
    const { discussionID, onMutateSuccess } = useSuggestedAnswerContext();
    const acceptAnswer = useAcceptSuggestion(discussionID);
    const dismissAnswer = useDismissSuggestion(discussionID);
    const toastError = useToastErrorHandler();
    const [disableButtons, setDisableButtons] = useState<boolean>(false);

    const handleAcceptAnswer = async () => {
        setDisableButtons(true);

        try {
            await acceptAnswer({
                suggestion: props.aiSuggestionID,
                accept: true,
            });
            onMutateSuccess?.();
        } catch (err) {
            toastError(err);
            setDisableButtons(false);
        }
    };

    const handleDismissAnswer = async () => {
        setDisableButtons(true);

        try {
            await dismissAnswer(props.aiSuggestionID);
            onMutateSuccess?.();
        } catch (err) {
            toastError(err);
            setDisableButtons(false);
        }
    };

    return (
        <li className={classes.item}>
            <div className={classes.itemContent}>
                <CollapsableContent maxHeight={100}>
                    <SuggestedAnswerContent {...props} />
                    <Button
                        buttonType={ButtonTypes.TEXT_PRIMARY}
                        className={classes.answerButton}
                        onClick={handleAcceptAnswer}
                        disabled={disableButtons}
                    >
                        <Icon icon="data-checked" size="compact" />
                        {t("Accept Answer")}
                    </Button>
                </CollapsableContent>
            </div>
            <ToolTip label={t("Dismiss Answer")}>
                <Button
                    buttonType={ButtonTypes.ICON_COMPACT}
                    className={classes.dismissAnswer}
                    onClick={handleDismissAnswer}
                    disabled={disableButtons}
                >
                    <Icon icon="search-close" size="compact" />
                    <ScreenReaderContent>{t("Dismiss Answer")}</ScreenReaderContent>
                </Button>
            </ToolTip>
        </li>
    );
}

export function SuggestedAnswerContent(props: ISuggestedAnswer & { className?: string }) {
    const { sourceIcon = "search-discussion", title, summary, url, type, className } = props;
    const classes = suggestedAnswersClasses();

    return (
        <p className={cx(classes.content, className)}>
            <span className={classes.itemIcon}>
                <Icon icon={sourceIcon} size="compact" />
            </span>
            <span className={classes.itemTitle}>{title}</span> {summary}{" "}
            <SmartLink to={url} className={classes.itemLink} target="_blank">
                {t(startCase(type))}
                <Icon icon="meta-external-compact" className={classes.itemLinkIcon} />
            </SmartLink>
        </p>
    );
}
