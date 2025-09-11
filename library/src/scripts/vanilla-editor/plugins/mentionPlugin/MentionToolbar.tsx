import { Combobox, ComboboxItemProps } from "@library/vanilla-editor/plugins/mentionPlugin/Combobox";
import {
    ELEMENT_MENTION,
    MentionPlugin,
    findMentionInput,
    getMentionOnSelectItem,
    removeMentionInput,
} from "@udecode/plate-mention";
import MentionSuggestion, {
    IMentionSuggestionData,
    MentionSuggestionLoading,
} from "@library/editor/pieces/MentionSuggestion";
import { getPluginOptions, usePlateEditorRef } from "@udecode/plate-common";
import { mentionListClasses, mentionListItemClasses } from "@library/editor/pieces/atMentionStyles";
import { offset, shift } from "@udecode/plate-floating";
import { useEffect, useMemo } from "react";

import { cx } from "@emotion/css";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import uniqueId from "lodash-es/uniqueId";
import { useComboboxSelectors } from "@udecode/plate-combobox";
import { useMentions } from "@library/features/users/suggestion/MentionsContext";
import { useVanillaEditorBounds } from "@library/vanilla-editor/VanillaEditorBoundsContext";

interface IProps {
    pluginKey?: string;
}

function MentionToolbar(props: IProps) {
    const SUGGESTION_LIMIT = 5;

    const { pluginKey = ELEMENT_MENTION } = props;

    const { setUsername, suggestedUsers, isLoading, lastSuccessfulUsername, activeSuggestionID, setActive } =
        useMentions();

    const text = useComboboxSelectors.text() ?? "";

    const items = useMemo(
        () =>
            (suggestedUsers ?? []).map((data) => {
                const { userID, name: text } = data;
                return {
                    key: `${userID}`,
                    text,
                    data,
                };
            }),
        [suggestedUsers],
    );

    const showLoader = isLoading;
    const loaderID = uniqueId("mentionList-noResults-");
    const matchedString = lastSuccessfulUsername;

    const highlightedIndex = useComboboxSelectors.highlightedIndex();

    useEffect(() => {
        if (highlightedIndex !== undefined && items[highlightedIndex]?.data) {
            const activeSuggestionId = items[highlightedIndex].data.domID;
            setActive(activeSuggestionId, highlightedIndex);
        }
    }, [highlightedIndex, items, setActive]);

    useEffect(() => {
        if (text) {
            setUsername(text);
        }
    }, [text, setUsername]);

    const editor = usePlateEditorRef();

    const { trigger } = getPluginOptions<MentionPlugin>(editor, pluginKey);

    const loaderActive = activeSuggestionID === loaderID;

    const { boundsRef } = useVanillaEditorBounds();

    useEffect(() => {
        const currentMentionInput = findMentionInput(editor)!;
        if (text && currentMentionInput && suggestedUsers?.length === 0 && !isLoading) {
            // if there are no matches, exit the combobox
            removeMentionInput(editor, currentMentionInput[1]);
        }
    }, [text, editor, suggestedUsers, isLoading]);

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

export default MentionToolbar;
