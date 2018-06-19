/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import classNames from "classnames";
import { t } from "@dashboard/application";

export interface IMentionSuggestionData {
    userID: number;
    name: string;
    photoUrl: string;
    uniqueID: string;
    onMouseEnter: React.MouseEventHandler<any>;
}

export interface IMentionProps extends IMentionSuggestionData {
    matchedString: string;
    isActive: boolean;
    onClick(event: React.MouseEvent<any>);
}

/**
 * A single Suggestion in a MentionList
 */
export default function MentionSuggestion(props: IMentionProps) {
    const { isActive, matchedString, photoUrl, name, onClick, userID, uniqueID, onMouseEnter } = props;

    const classes = classNames("richEditor-menuItem", "atMentionList-item", {
        isActive,
    });

    let matched = false;
    const formattedName = name.split(new RegExp(`(${matchedString})`, "i")).map((piece, index) => {
        if (piece.toLowerCase() === matchedString.toLowerCase() && !matched) {
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
            id={uniqueID}
            className={classes}
            role="option"
            aria-selected={isActive}
            onClick={onClick}
            onMouseEnter={onMouseEnter}
        >
            <button type="button" className="atMentionList-suggestion">
                <span className="atMentionList-user">
                    <span className="PhotoWrap atMentionList-photoWrap">
                        <img src={photoUrl} alt={name} className="atMentionList-photo ProfilePhoto" />
                    </span>
                    <span className="atMentionList-userName">{formattedName}</span>
                </span>
            </button>
        </li>
    );
}

/**
 * An item in the mention list for when no results are found.
 */
export function MentionSuggestionNotFound(props: { id: string }) {
    return (
        <span className="richEditor-menuItem atMentionList-item">
            <span id={props.id} className="atMentionList-noResults">
                {t("No results found")}
            </span>
        </span>
    );
}
