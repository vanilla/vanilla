/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@vanilla/i18n";
import { automationRulesClasses } from "@dashboard/automationRules/AutomationRules.classes";
import Translate from "@library/content/Translate";
import { DISCUSSIONS_MAX_PAGE_COUNT } from "@library/features/discussions/discussionHooks";
import { humanReadableNumber } from "@library/content/NumberFormatted";
import Message from "@library/messages/Message";

interface IProps {
    contentType: "Users" | "Posts" | "Comments" | "Discussions";
    totalResults?: number;
    emptyResults?: boolean;
    fromStatusToggle?: boolean;
    hasError?: boolean;
}

export function AutomationRulesPreviewContentHeader(props: IProps) {
    const { contentType, totalResults, emptyResults, fromStatusToggle, hasError } = props;
    const classes = automationRulesClasses();

    return (
        <>
            {hasError && (
                <div className={classes.padded()}>
                    <Message
                        type="error"
                        stringContents={t(
                            "Failed to load the preview data. Please check your trigger and action values.",
                        )}
                    />
                </div>
            )}
            <div>
                {totalResults ? (
                    <>
                        <div className={classes.bold}>
                            <Translate
                                source={`${contentType} Matching Criteria Now: <0 />`}
                                c0={
                                    totalResults >= DISCUSSIONS_MAX_PAGE_COUNT
                                        ? `${humanReadableNumber(totalResults)}+`
                                        : totalResults
                                }
                            />
                        </div>
                        <div>
                            {fromStatusToggle ? (
                                <Translate
                                    source={
                                        "The action will apply to them when the rule is enabled. In future, other <0 /> who meet the trigger criteria will have the action applied to them as well."
                                    }
                                    c0={contentType.toLowerCase()}
                                />
                            ) : (
                                t("The action will be applied to only them if you proceed.")
                            )}
                        </div>
                        <div className={classes.italic}>
                            <Translate
                                source={
                                    "Note: Actions will not affect <0 /> that already have the associated action applied."
                                }
                                c0={contentType.toLowerCase()}
                            />
                        </div>
                    </>
                ) : emptyResults ? (
                    <Translate
                        source={
                            "This will not affect any <0 /> right now. It will affect those that meet the criteria in future."
                        }
                        c0={contentType.toLowerCase()}
                    />
                ) : (
                    <></>
                )}
            </div>
        </>
    );
}
