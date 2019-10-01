/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { splitStringLoosely } from "@vanilla/utils";
import { richEditorClasses } from "@rich-editor/editor/richEditorStyles";
import { IUserFragment } from "@library/@types/api/users";

export interface IMentionSuggestionData extends IUserFragment {
    domID: string;
}

interface IGenericMentionProps {
    matchedString: string;
    isActive: boolean;
    onMouseEnter?: React.MouseEventHandler<any>;
    onClick?: React.MouseEventHandler<any>;
}

export interface IMentionProps extends IGenericMentionProps {
    mentionData: IMentionSuggestionData;
}

export interface IMentionLoadingProps extends IGenericMentionProps {
    loadingData: {
        domID: string;
    };
}

/**
 * A single Suggestion in a MentionList
 */
export default function MentionSuggestion(props: IMentionProps) {
    const { isActive, matchedString, mentionData, onClick, onMouseEnter } = props;
    const { photoUrl, name, domID } = mentionData;
    const classesRichEditor = richEditorClasses(false);

    const classes = classNames("richEditor-menuItem", "atMentionList-item", classesRichEditor.menuItem, {
        isActive,
    });

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
                <mark className="atMentionList-mark" key={index}>
                    {piece}
                </mark>
            );
        } else {
            return piece;
        }
    });

    return (
        <li
            id={domID}
            className={classes}
            role="option"
            aria-selected={isActive}
            onClick={onClick}
            onMouseEnter={onMouseEnter}
        >
            <button type="button" className="atMentionList-suggestion">
                <span className="atMentionList-user">
                    <span className="atMentionList-photoWrap">
                        <img src={photoUrl} alt={name} className="atMentionList-photo" />
                    </span>
                    <span className="atMentionList-userName">{formattedName}</span>
                </span>
            </button>
        </li>
    );
}

/**
 * A loading indicator suggestion.
 */
export function MentionSuggestionLoading(props: IMentionLoadingProps) {
    const { loadingData, onMouseEnter, isActive } = props;
    const { domID } = loadingData;
    const classesRichEditor = richEditorClasses(false);
    const classes = classNames(
        "richEditor-menuItem",
        "atMentionList-item",
        "atMentionList-loader",
        classesRichEditor.menuItem,
        {
            isActive,
        },
    );

    return (
        <li id={domID} className={classes} role="option" aria-selected={isActive} onMouseEnter={onMouseEnter}>
            <button type="button" className="atMentionList-suggestion" disabled>
                <span className="atMentionList-user atMentionList-loader">
                    <span className="PhotoWrap atMentionList-photoWrap">
                        <img alt={name} className="atMentionList-photo ProfilePhoto" />
                    </span>
                    <span className="atMentionList-userName">{t("Loading...")}</span>
                </span>
            </button>
        </li>
    );
}

/**
 * We need a dummy "spacer" suggestion so that we can get our initial measurements.
 */
export function MentionSuggestionSpacer() {
    const classesRichEditor = richEditorClasses(false);
    const classes = classNames(
        "richEditor-menuItem",
        "atMentionList-item",
        "atMentionList-spacer",
        classesRichEditor.menuItem,
    );
    return (
        <li aria-hidden="true" className={classes} style={{ visibility: "hidden" }}>
            <button type="button" className="atMentionList-suggestion">
                <span className="atMentionList-user atMentionList-loader">
                    <span className="PhotoWrap atMentionList-photoWrap">
                        <img alt={name} className="atMentionList-photo ProfilePhoto" />
                    </span>
                    <span className="atMentionList-userName" />
                </span>
            </button>
        </li>
    );
}
