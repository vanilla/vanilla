/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import classNames from "classnames";
import { t } from "@core/application";

export interface IMentionData {
    userID: number;
    name: string;
    photoUrl: string;
    uniqueID: string;
    onMouseEnter: React.MouseEventHandler<any>;
}

export interface IMentionProps extends IMentionData {
    matchedString: string;
    isActive: boolean;
    onClick(event: React.MouseEvent<any>);
}

export default function MentionItem(props: IMentionProps) {
    const { isActive, matchedString, photoUrl, name, onClick, userID } = props;

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
            id={props.uniqueID}
            className={classes}
            role="option"
            aria-selected={isActive}
            onClick={props.onClick}
            onMouseEnter={props.onMouseEnter}
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

export function NoResultMentionItem(props) {
    const classes = classNames("richEditor-menuItem", "atMentionList-item");

    return (
        <li className={classes} role="option">
            <button type="button" className="atMentionList-suggestion">
                <span className="atMentionList-user">{t("No results found")}</span>
            </button>
        </li>
    );
}
