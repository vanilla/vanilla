/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { FunctionComponent } from "react";
import { DownvoteIcon, UpvoteIcon } from "@library/icons/common";
import { voteCounterClasses } from "@library/voteCounter/VoteCounter.styles";
import { cx } from "@emotion/css";
import ActsAsCheckbox from "@library/forms/ActsAsCheckbox";
import { t } from "@vanilla/i18n";

export interface IProps {
    upvoted?: boolean;
    onToggleUpvoted?: () => Promise<void>;
    downvoted?: boolean;
    onToggleDownvoted?: () => Promise<void>;
    score?: number;
    className?: string;
}

const VoteCounter: FunctionComponent<IProps> = ({
    upvoted = false,
    onToggleUpvoted,
    downvoted = false,
    onToggleDownvoted,
    score = 0,
    className,
}) => {
    const { root, count, icon, iconChecked } = voteCounterClasses();

    {
        /* FIXME: truncate in case of long number */
    }
    const countElement = <span className={count}>{score}</span>;

    let upvote: JSX.Element = <></>;

    if (onToggleUpvoted) {
        upvote = (
            <ActsAsCheckbox checked={upvoted} onChange={onToggleUpvoted} title={t("Upvote")}>
                {({ disabled }) => (
                    <UpvoteIcon
                        className={cx(icon, {
                            [iconChecked]: upvoted && !disabled,
                        })}
                    />
                )}
            </ActsAsCheckbox>
        );
    }

    let downvote: JSX.Element = <></>;

    if (onToggleDownvoted) {
        downvote = (
            <ActsAsCheckbox checked={downvoted} onChange={onToggleDownvoted} title={t("Downvote")}>
                {({ disabled }) => (
                    <DownvoteIcon
                        className={cx(icon, {
                            [iconChecked]: downvoted && !disabled,
                        })}
                    />
                )}
            </ActsAsCheckbox>
        );
    }

    let content = (
        <>
            {countElement}
            {upvote}
        </>
    );

    if (onToggleDownvoted) {
        content = (
            <>
                {upvote}
                {countElement}
                {downvote}
            </>
        );
    }

    return <div className={cx(root, className)}>{content}</div>;
};

export default VoteCounter;
