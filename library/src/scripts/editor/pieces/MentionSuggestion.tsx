/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { splitStringLoosely } from "@vanilla/utils";
import { richEditorClasses } from "@library/editor/richEditorStyles";
import { IUserFragment } from "@library/@types/api/users";
import { UserPhoto } from "@library/headers/mebox/pieces/UserPhoto";
import { mentionClasses, mentionListItemClasses } from "@library/editor/pieces/atMentionStyles";
import { cx } from "@emotion/css";

export interface IMentionSuggestionData extends IUserFragment {
    domID: string;
}

interface IGenericMentionProps {
    matchedString: string;
}

export interface IMentionProps extends IGenericMentionProps {
    mentionData: IMentionSuggestionData;
}

export interface IMentionLoadingProps extends IGenericMentionProps {}

/**
 * A single Suggestion in a MentionList
 */
export default function MentionSuggestion(props: IMentionProps) {
    const { matchedString, mentionData } = props;
    const { name } = mentionData;

    const classes = mentionClasses();

    let matched = false;
    const formattedName = splitStringLoosely(name, matchedString).map((piece, index) => {
        const searchCollator = Intl.Collator("en", {
            usage: "search",
            sensitivity: "base",
            ignorePunctuation: true,
            numeric: true,
        });
        if (searchCollator.compare(piece, matchedString) === 0 && !matched) {
            matched = true;
            return (
                <mark className={classes.mark} key={index}>
                    {piece}
                </mark>
            );
        } else {
            return piece;
        }
    });

    return (
        <button type="button" className={classes.suggestion}>
            <span className={classes.user}>
                <span className={classes.photoWrap}>
                    <UserPhoto userInfo={mentionData} className={classes.photo} />
                </span>
                <span className={classes.userName}>{formattedName}</span>
            </span>
        </button>
    );
}

/**
 * A loading indicator suggestion.
 */
export function MentionSuggestionLoading() {
    const classes = mentionClasses();

    return (
        <button type="button" className={classes.suggestion} disabled>
            <span className={classes.user}>
                <span className={cx("PhotoWrap", classes.photoWrap)}>
                    <span className={cx(classes.photo, "ProfilePhoto")} />
                </span>
                <span className={classes.userName}>{t("Loading...")}</span>
            </span>
        </button>
    );
}

/**
 * We need a dummy "spacer" suggestion so that we can get our initial measurements.
 */
export function MentionSuggestionSpacer() {
    const classesRichEditor = richEditorClasses(false);

    const classes = mentionClasses();

    return (
        <li
            aria-hidden="true"
            className={cx(mentionListItemClasses().listItem, classesRichEditor.menuItem)}
            style={{ visibility: "hidden" }}
        >
            <button type="button" className={classes.suggestion}>
                <span className={classes.user}>
                    <span className={cx("PhotoWrap", classes.photoWrap)}>
                        <span className={cx(classes.photo, "ProfilePhoto")} />
                    </span>
                    <span className={classes.userName} />
                </span>
            </button>
        </li>
    );
}
