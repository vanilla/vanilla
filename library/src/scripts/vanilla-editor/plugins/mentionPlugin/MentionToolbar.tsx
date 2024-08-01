import { cx } from "@emotion/css";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import MentionSuggestion, {
    IMentionSuggestionData,
    MentionSuggestionLoading,
} from "@library/editor/pieces/MentionSuggestion";
import { mentionListClasses, mentionListItemClasses } from "@library/editor/pieces/atMentionStyles";
import UserSuggestionActions from "@library/features/users/suggestion/UserSuggestionActions";
import UserSuggestionModel, {
    IInjectableSuggestionsProps,
} from "@library/features/users/suggestion/UserSuggestionModel";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { useVanillaEditorBounds } from "@library/vanilla-editor/VanillaEditorBoundsContext";
import { Combobox, ComboboxItemProps } from "@library/vanilla-editor/plugins/mentionPlugin/Combobox";
import { useComboboxSelectors } from "@udecode/plate-combobox";
import { getPluginOptions, usePlateEditorRef } from "@udecode/plate-common";
import { offset, shift } from "@udecode/plate-floating";
import {
    ELEMENT_MENTION,
    MentionPlugin,
    findMentionInput,
    getMentionOnSelectItem,
    removeMentionInput,
} from "@udecode/plate-mention";
import uniqueId from "lodash-es/uniqueId";
import React, { useEffect, useMemo } from "react";
import { connect } from "react-redux";

interface IProps extends IInjectableSuggestionsProps {
    suggestionActions: UserSuggestionActions;
    pluginKey?: string;
}

function MentionToolbar(props: IProps) {
    const SUGGESTION_LIMIT = 5;

    const { suggestionActions, suggestions, pluginKey = ELEMENT_MENTION } = props;

    const text = useComboboxSelectors.text() ?? "";

    const items = useMemo(
        () =>
            (suggestions?.data ?? []).map((data) => {
                const { userID, name: text } = data;
                return {
                    key: `${userID}`,
                    text,
                    data,
                };
            }),
        [suggestions],
    );

    const showLoader = props.isLoading;
    const loaderID = uniqueId("mentionList-noResults-");
    const matchedString = props.lastSuccessfulUsername;
    const activeSuggestionID = props.activeSuggestionID;

    const highlightedIndex = useComboboxSelectors.highlightedIndex();

    useEffect(() => {
        if (highlightedIndex !== undefined && items[highlightedIndex]?.data) {
            const activeSuggestionId = items[highlightedIndex].data.domID;
            props.suggestionActions.setActive(activeSuggestionId, highlightedIndex);
        }
    }, [highlightedIndex, items]);

    useEffect(() => {
        if (text) {
            suggestionActions.loadUsers(text);
        }
    }, [text]);

    const editor = usePlateEditorRef();

    const { trigger } = getPluginOptions<MentionPlugin>(editor, pluginKey);

    const loaderActive = activeSuggestionID === loaderID;

    const { boundsRef } = useVanillaEditorBounds();

    useEffect(() => {
        const currentMentionInput = findMentionInput(editor)!;
        if (
            text &&
            currentMentionInput &&
            props.suggestions?.data?.length === 0 &&
            props.suggestions.status === LoadStatus.SUCCESS
        ) {
            // if there are no matches, exit the combobox
            removeMentionInput(editor, currentMentionInput[1]);
        }
    }, [text, editor, props.suggestions]);

    function RenderItem(props: ComboboxItemProps<IMentionSuggestionData>) {
        return (
            <MentionSuggestion
                key={props.item.data.domID}
                mentionData={props.item.data}
                matchedString={matchedString ?? props.search}
            />
        );
    }

    function RenderLoader() {
        const listItemClasses = mentionListItemClasses(loaderActive);
        return (
            <li
                key={"Loading"}
                id={loaderID}
                className={cx(listItemClasses.listItem)}
                role="option"
                aria-selected={loaderActive}
            >
                <MentionSuggestionLoading />
            </li>
        );
    }

    const listWrapperClassName = mentionListClasses().listWrapper;

    const listClassName = cx(
        "richEditor-menu",
        "likeDropDownContent",
        mentionListClasses().list,
        { isHidden: !(items.length > 0) && !showLoader },
        dropDownClasses().likeDropDownContent,
    );

    const listItemClassName = mentionListItemClasses().listItem;
    const highlightedListItemClassName = mentionListItemClasses(true).listItem;

    return (
        <Combobox<IMentionSuggestionData>
            id={pluginKey}
            maxSuggestions={SUGGESTION_LIMIT}
            trigger={trigger!}
            controlled
            onSelectItem={getMentionOnSelectItem({
                key: pluginKey,
            })}
            disabled={false}
            items={items}
            floatingOptions={{
                middleware: [
                    shift({
                        boundary: boundsRef.current!,
                        padding: 14,
                    }),
                    offset(-10),
                ],
            }}
            listWrapperClassName={listWrapperClassName}
            listClassName={listClassName}
            listItemClassName={listItemClassName}
            highlightedListItemClassName={highlightedListItemClassName}
            onRenderItem={RenderItem}
            component={showLoader ? RenderLoader : undefined}
        />
    );
}

const withRedux = connect(UserSuggestionModel.mapStateToProps, (dispatch) => ({
    suggestionActions: new UserSuggestionActions(dispatch, apiv2),
}));

export default withRedux(MentionToolbar);
