/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IError } from "@library/errorPages/CoreErrorMessages";
import { useDiscussionByIDs } from "@library/features/discussions/discussionHooks";
import { bulkDiscussionsClasses } from "@library/features/discussions/forms/BulkDiscussions.classes";
import { ErrorIcon, InformationIcon } from "@library/icons/common";
import { ILongRunnerResponse } from "@library/LongRunnerClient";
import Message from "@library/messages/Message";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React, { useMemo } from "react";

interface IProps {
    errorsByDiscussionID: ILongRunnerResponse["progress"]["exceptionsByID"] | null;
    generalError?: IError;
    messagePrefix: string;
}

export function BulkDiscussionErrors(props: IProps) {
    const failedDiscussionIDs = Object.keys(props.errorsByDiscussionID ?? {});
    const failedDiscussions = useDiscussionByIDs(failedDiscussionIDs);
    const { generalError, errorsByDiscussionID, messagePrefix } = props;

    const classes = bulkDiscussionsClasses();

    const [stringMessage, reactMessage, reactDescription] = useMemo(() => {
        if (failedDiscussions && Object.keys(failedDiscussions).length > 0) {
            let stringMessage = messagePrefix;
            let reactMessage: React.ReactNode = messagePrefix;
            let reactDescriptionArr: React.ReactNodeArray = [];

            const failedNames: string[] = [];
            Object.values(failedDiscussions).forEach((failedDiscussion, i) => {
                const name = failedDiscussion.name;
                failedNames.push(name);
                const detailedError = errorsByDiscussionID?.[failedDiscussion.discussionID] ?? {
                    message: t("Something went wrong."),
                };
                reactDescriptionArr.push(
                    <React.Fragment key={i}>
                        <span className={classes.errorLine}>
                            <span className={classes.errorLabel}>{name}</span>
                            <ToolTip key={i} label={detailedError.message}>
                                <ToolTipIcon>
                                    <InformationIcon />
                                </ToolTipIcon>
                            </ToolTip>
                        </span>
                    </React.Fragment>,
                );
            });
            stringMessage += `\n ${failedNames.join(", ")}`;
            const reactDescription = <>{reactDescriptionArr}</>;

            return [stringMessage, reactMessage, reactDescription];
        }

        if (generalError) {
            return [generalError.message, generalError.message, generalError.description];
        }

        return [undefined, undefined, undefined];
    }, [generalError, failedDiscussions, messagePrefix, errorsByDiscussionID]);

    if (stringMessage) {
        return (
            <Message
                className={classes.errorMessageOffset}
                icon={reactDescription ? undefined : <ErrorIcon />}
                stringContents={stringMessage}
                title={reactMessage}
                contents={reactDescription}
            />
        );
    } else {
        return <></>;
    }
}
