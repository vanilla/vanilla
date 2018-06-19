/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill, { RangeStatic, Sources, DeltaStatic } from "quill/core";
import { AxiosResponse } from "axios";
import uniqueId from "lodash/uniqueId";
import Keyboard from "quill/modules/keyboard";
import axios from "axios";
import { withEditor, IEditorContextProps } from "@rich-editor/components/context";
import MentionSuggestionList from "./pieces/MentionSuggestionList";
import { thunks as mentionThunks } from "@rich-editor/state/mentionActions";
import MentionAutoCompleteBlot from "@rich-editor/quill/blots/embeds/MentionAutoCompleteBlot";
import { getBlotAtIndex, getMentionRange } from "@rich-editor/quill/utility";
import { Dispatch } from "redux";
import IStoreState from "@rich-editor/state/IState";
import { IMentionValue } from "@rich-editor/state/MentionTrie";
import { IMentionUser } from "@dashboard/apiv2";
import { connect } from "react-redux";

interface IProps extends IEditorContextProps {
    lookupMentions: (username: string) => void;
    currentSuggestions: IMentionValue | null;
}

interface IMentionState {
    inActiveMention: boolean;
    autoCompleteBlot: MentionAutoCompleteBlot | null;
    username: string;
    startIndex: number;
    activeItemID: string;
    activeItemIndex: number;
    hasApiResponse: boolean;
}

const mentionCache: Map<string, AxiosResponse | null> = new Map();

/**
 * Module for inserting, removing, and editing at-mentions.
 */
export class MentionToolbar extends React.PureComponent<IProps, IMentionState> {
    private quill: Quill;
    private ID = uniqueId("mentionList-");
    private noResultsID = uniqueId("mentionList-noResults-");
    private comboBoxID = uniqueId("mentionComboBox-");
    private isConvertingMention = false;

    constructor(props: IProps) {
        super(props);
        this.quill = props.quill!;
        this.state = {
            inActiveMention: false,
            autoCompleteBlot: null,
            username: "",
            startIndex: 0,
            activeItemID: "",
            activeItemIndex: 0,
            hasApiResponse: true,
        };
    }

    public componentDidMount() {
        this.quill.on("text-change", this.onTextChange);
        this.quill.on("selection-change", this.onSelectionChange);
        this.quill.root.addEventListener("keydown", this.keyDownListener);
    }

    public componentWillUnmount() {
        this.quill.off("text-change", this.onTextChange);
        this.quill.off("selection-change", this.onSelectionChange);
        this.quill.root.removeEventListener("keydown", this.keyDownListener);
    }

    public render() {
        const { currentSuggestions } = this.props;
        return (
            currentSuggestions &&
            currentSuggestions.status === "SUCCESSFUL" && (
                <MentionSuggestionList
                    onItemClick={this.onItemClick}
                    mentionData={this.transformSuggestions(currentSuggestions.users)}
                    matchedString={this.state.username}
                    activeItemId={this.state.activeItemID}
                    isVisible={this.state.inActiveMention /*  && this.state.hasApiResponse */}
                    id={this.ID}
                    noResultsID={this.noResultsID}
                />
            )
        );
    }

    /**
     * Handle click events on a mention suggestions.
     */
    private onItemClick = (event: React.MouseEvent<any>) => {
        event.preventDefault();
        this.confirmActiveMention();
    };

    /**
     * Keydown listener for ARIA compliance with
     */
    private keyDownListener = (event: KeyboardEvent) => {
        const { activeItemIndex, inActiveMention, hasApiResponse } = this.state;
        const { currentSuggestions } = this.props;

        if (!currentSuggestions || currentSuggestions.status !== "SUCCESSFUL") {
            return;
        }

        // const suggestions =
        if (this.quill.hasFocus() && inActiveMention && !hasApiResponse) {
            // Quill doesn't properly trigger update the range until after enter is pressed, which triggers out text change listener with the wrong range. Manually handle this for now.
            if (Keyboard.match(event, Keyboard.keys.ENTER)) {
                this.cancelActiveMention();
                this.quill.insertText(this.quill.getSelection().index, "\n", Quill.sources.API);
                event.preventDefault();
            }
            return;
        }

        if (this.quill.hasFocus() && inActiveMention) {
            let newIndex;
            let newItemID;
            const firstIndex = 0;
            const nextIndex = activeItemIndex + 1;
            const prevIndex = activeItemIndex - 1;
            const lastIndex = currentSuggestions.users.length - 1;

            switch (true) {
                case Keyboard.match(event, Keyboard.keys.DOWN):
                    newIndex = activeItemIndex === lastIndex ? firstIndex : nextIndex;
                    newItemID = this.generateIdForMentionData(currentSuggestions.users[newIndex]);
                    this.setState({
                        activeItemID: newItemID,
                        activeItemIndex: newIndex,
                    });
                    this.injectComboBoxAccessibility();
                    event.preventDefault();
                    event.stopPropagation();
                    break;
                case Keyboard.match(event, Keyboard.keys.UP):
                    newIndex = activeItemIndex === firstIndex ? lastIndex : prevIndex;
                    newItemID = this.generateIdForMentionData(currentSuggestions.users[newIndex]);
                    this.setState({
                        activeItemID: newItemID,
                        activeItemIndex: newIndex,
                    });
                    this.injectComboBoxAccessibility();
                    event.preventDefault();
                    event.stopPropagation();
                    break;
                case Keyboard.match(event, Keyboard.keys.ENTER):
                case Keyboard.match(event, Keyboard.keys.TAB):
                    this.confirmActiveMention();
                    event.preventDefault();
                    event.stopPropagation();
                    break;
                case Keyboard.match(event, Keyboard.keys.ESCAPE):
                    this.cancelActiveMention();
                    event.preventDefault();
                    event.stopPropagation();
                    break;
            }
        }
    };

    /**
     * Generate an ID for a mention suggestion.
     */
    private generateIdForMentionData(data: IMentionUser) {
        return this.props.editorID + "-mentionItem-" + data.userID;
    }

    /**
     * Inject accessibility attributes into the current MentionAutoComplete and ComboBox.
     */
    private injectComboBoxAccessibility = () => {
        const { autoCompleteBlot, activeItemID } = this.state;
        if (autoCompleteBlot) {
            autoCompleteBlot.injectAccessibilityAttributes({
                ID: this.comboBoxID,
                activeItemID,
                suggestionListID: this.ID,
                noResultsID: this.noResultsID,
            });
        }
    };

    private transformSuggestions(suggestions: IMentionUser[]) {
        return suggestions.map((data, index) => {
            const uniqueID = this.generateIdForMentionData(data);
            const onMouseEnter = () => {
                this.setState({ activeItemID: uniqueID, activeItemIndex: index }, this.injectComboBoxAccessibility);
            };

            return {
                ...data,
                uniqueID,
                onMouseEnter,
            };
        });
    }

    /**
     * Handle mention responses from the API.
     */
    private handleMentionResponse(response: AxiosResponse | null) {
        if (!response) {
            return;
        }

        this.setState(
            {
                // activeItemID: suggestions.length > 0 ? suggestions[0].uniqueID : null, // todo reset this somewhere else.
                activeItemIndex: 0, // TODO: reset this somewhere else.
                hasApiResponse: true, // TODO remove this.
            },
            this.injectComboBoxAccessibility,
        );
    }

    /**
     * Reset the component's mention state. Also clears the current combobox.
     *
     * @param clearComboBox - Whether or not to clear the current combobox. An situation where you would not want to do this is if it is already deleted or it has already been detached from quill.
     */
    private cancelActiveMention(clearComboBox = true) {
        if (this.state.autoCompleteBlot && clearComboBox && !this.isConvertingMention) {
            this.isConvertingMention = true;
            const selection = this.quill.getSelection();
            this.state.autoCompleteBlot.cancel();
            this.quill.setSelection(selection, Quill.sources.SILENT);
        }
        this.setState({
            inActiveMention: false,
            autoCompleteBlot: null,
            username: "",
            hasApiResponse: false,
        });
        this.isConvertingMention = false;
    }

    /**
     * Convert the active MentionAutoCompleteBlot into a MentionBlot.
     */
    private confirmActiveMention() {
        const { autoCompleteBlot, activeItemIndex } = this.state;
        const { currentSuggestions } = this.props;
        if (
            !(autoCompleteBlot instanceof MentionAutoCompleteBlot) ||
            this.isConvertingMention ||
            !currentSuggestions ||
            currentSuggestions.status !== "SUCCESSFUL"
        ) {
            return;
        }

        this.isConvertingMention = true;
        const activeSuggestion = currentSuggestions.users[activeItemIndex];
        const start = autoCompleteBlot.offset(this.quill.scroll);

        autoCompleteBlot.finalize(activeSuggestion);
        this.quill.insertText(start + 1, " ", Quill.sources.SILENT);
        this.quill.setSelection(start + 2, 0, Quill.sources.SILENT);
        this.cancelActiveMention();
    }

    /**
     * Watch for selection change events in quill. We need to clear the mention list if we have text selected or their is no selection.
     */
    private onSelectionChange = (range: RangeStatic, oldRange: RangeStatic, sources) => {
        if (sources !== Quill.sources.USER || !this.state.inActiveMention || !this.state.hasApiResponse) {
            return;
        }

        if (!range || range.length > 0) {
            return this.cancelActiveMention();
        }

        // The range is updated before the text content, so we need to step back one character sometimes.
        const lookupIndex = range.index - 1;
        const autoCompleteBlot = getBlotAtIndex(this.quill, range.index, MentionAutoCompleteBlot);
        const mentionRange = getMentionRange(this.quill, range.index, true);

        if (!autoCompleteBlot && !mentionRange) {
            return this.cancelActiveMention();
        }
    };

    /**
     * A quill text change event listener.
     *
     * - Clears mention state if we no longer match a mention.
     * - Converts a range into mention combobox if it matches.
     * - Triggers name lookup on match.
     */
    private onTextChange = (delta: DeltaStatic, oldContents: DeltaStatic, source: Sources) => {
        // Ignore non-user changes.
        if (source !== Quill.sources.USER) {
            return;
        }

        // Clear the mention if there is no selection.
        const selection = this.quill.getSelection();
        if (selection == null || selection.index == null) {
            return this.cancelActiveMention(false);
        }

        let autoCompleteBlot = getBlotAtIndex(this.quill, selection.index, MentionAutoCompleteBlot);
        const mentionRange = getMentionRange(this.quill);

        if (!mentionRange) {
            return this.cancelActiveMention();
        }

        // Create a autoCompleteBlot if it doesn't already exist.
        if (!autoCompleteBlot) {
            this.quill.formatText(
                mentionRange.index,
                mentionRange.length,
                "mention-autocomplete",
                true,
                Quill.sources.API,
            );
            this.quill.setSelection(selection.index, 0, Quill.sources.API);

            // Get the autoCompleteBlot
            autoCompleteBlot = getBlotAtIndex(this.quill, selection.index - 1, MentionAutoCompleteBlot)!;
        }

        this.setState({
            autoCompleteBlot,
            inActiveMention: true,
            username: autoCompleteBlot.username,
            startIndex: autoCompleteBlot.offset(this.quill.scroll),
        });
        this.props.lookupMentions(autoCompleteBlot.username);
    };
}

function mapDispatchToProps(dispatch: Dispatch<any>) {
    return { lookupMentions: username => dispatch(mentionThunks.loadUsers(username)) };
}

function mapStateToProps(state: IStoreState) {
    const { lastSuccessfulUsername, currentUsername, usersTrie } = state.editor.mentions;
    return {
        currentSuggestions: lastSuccessfulUsername ? usersTrie.getValue(lastSuccessfulUsername) : null,
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(withEditor<IProps>(MentionToolbar));
