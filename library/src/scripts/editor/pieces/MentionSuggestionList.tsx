/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

import { t } from "@library/utility/appUtils";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { cx } from "@emotion/css";
import MentionSuggestion, {
    IMentionProps,
    MentionSuggestionLoading,
    MentionSuggestionSpacer,
} from "@library/editor/pieces/MentionSuggestion";
import { richEditorClasses } from "@library/editor/richEditorStyles";
import { mentionListClasses, mentionListItemClasses } from "@library/editor/pieces/atMentionStyles";

interface IProps {
    mentionProps: Array<
        Partial<
            IMentionProps & {
                onMouseEnter?: React.MouseEventHandler<any>;
            }
        >
    >;
    matchedString: string;
    id: string;
    loaderID: string;
    activeItemID: string | null;
    onItemClick: React.MouseEventHandler<any>;
    showLoader: boolean;
}

export default React.forwardRef<HTMLElement, IProps>(function MentionSuggestionList(props, ref) {
    const classesDropDown = dropDownClasses();

    const listClasses = mentionListClasses();

    const { activeItemID, id, onItemClick, matchedString, mentionProps, showLoader, loaderID } = props;

    const classes = cx(
        "richEditor-menu",
        listClasses.listWrapper,
        "likeDropDownContent",
        classesDropDown.likeDropDownContent,
    );
    const classesRichEditor = richEditorClasses(false);

    const items = mentionProps.map((mentionProp) => {
        if (mentionProp.mentionData == null) {
            return null;
        }
        const isActive = mentionProp.mentionData.domID === activeItemID;
        const listItemClasses = mentionListItemClasses(isActive);
        return (
            <li
                key={mentionProp.mentionData.domID}
                id={mentionProp.mentionData.domID}
                className={cx(listItemClasses.listItem, classesRichEditor.menuItem)}
                role="option"
                aria-selected={isActive}
                onClick={onItemClick}
                onMouseEnter={mentionProp.onMouseEnter}
            >
                <MentionSuggestion mentionData={mentionProp.mentionData} matchedString={matchedString} />
            </li>
        );
    });

    if (showLoader) {
        const loadingData = {
            domID: loaderID,
        };
        const isActive = loadingData.domID === activeItemID;
        const listItemClasses = mentionListItemClasses(isActive);

        items.push(
            <li
                key={"Loading"}
                id={loadingData.domID}
                className={cx(listItemClasses.listItem, classesRichEditor.menuItem)}
                role="option"
                aria-selected={isActive}
            >
                <MentionSuggestionLoading />
            </li>,
        );
    }

    const hasResults = mentionProps.length > 0 || showLoader;

    return (
        <span className={listClasses.listWrapper} ref={ref}>
            <ul
                id={id}
                aria-label={t("@mention user suggestions")}
                className={classes + (hasResults ? "" : " isHidden")}
                role="listbox"
            >
                {hasResults && items}
            </ul>
            <div className={classes} style={{ visibility: "hidden" }}>
                <MentionSuggestionSpacer />
            </div>
        </span>
    );
});
