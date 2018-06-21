/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import classNames from "classnames";
import { t } from "@dashboard/application";
import { IMentionUser } from "@dashboard/apiv2";
import { splitStringLoosely } from "@dashboard/utility";

export interface IMentionSuggestionData extends IMentionUser {
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

    const classes = classNames("richEditor-menuItem", "atMentionList-item", {
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

export function MentionSuggestionLoading(props: IMentionLoadingProps) {
    const { loadingData, onMouseEnter, isActive } = props;
    const { domID } = loadingData;
    const classes = classNames("richEditor-menuItem", "atMentionList-item", "atMentionList-loader", {
        isActive,
    });

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
