/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Quill, { RangeStatic, Sources, DeltaStatic } from "quill/core";
import uniqueId from "lodash/uniqueId";
import debounce from "lodash/debounce";
import Keyboard from "quill/modules/keyboard";
import { withEditor, IEditorContextProps } from "@rich-editor/components/context";
import MentionSuggestionList from "./pieces/MentionSuggestionList";
import { thunks as mentionThunks, actions as mentionActions } from "@rich-editor/state/mention/mentionActions";
import MentionAutoCompleteBlot from "@rich-editor/quill/blots/embeds/MentionAutoCompleteBlot";
import { getBlotAtIndex, getMentionRange } from "@rich-editor/quill/utility";
import { Dispatch } from "redux";
import IStoreState from "@rich-editor/state/IState";
import { IMentionValue } from "@rich-editor/state/mention/MentionTrie";
import { connect } from "react-redux";
import { IMentionSuggestionData, IMentionProps } from "@rich-editor/components/toolbars/pieces/MentionSuggestion";

interface IProps extends IEditorContextProps {
    lookupMentions: (username: string) => void;
    setActiveItem: (itemID: string, itemIndex: number) => void;
    suggestions: IMentionValue | null;
    lastSuccessfulUsername: string;
    activeSuggestionID: string;
    activeSuggestionIndex: number;
    showLoader: boolean;
}

interface IMentionState {
    inActiveMention: boolean;
    autoCompleteBlot: MentionAutoCompleteBlot | null;
}

/**
 * Module for inserting, removing, and editing at-mentions.
 */
export class MentionToolbar extends React.Component<IProps, IMentionState> {
    private quill: Quill;
    private ID = uniqueId("mentionList-");
    private loaderID = uniqueId("mentionList-noResults-");
    private comboBoxID = uniqueId("mentionComboBox-");
    private isConvertingMention = false;
    private readonly MENTION_COMPLETION_CHARACTERS = [".", "!", "?", " ", "\n"];

    constructor(props: IProps) {
        super(props);
        this.quill = props.quill!;
        this.state = {
            inActiveMention: false,
            autoCompleteBlot: null,
        };
    }

    public componentDidMount() {
        document.addEventListener("keydown", this.keyDownListener, true);
        document.addEventListener("click", this.onDocumentClick, false);
        this.quill.on("text-change", this.onTextChange);
        this.quill.on("selection-change", this.onSelectionChange);
    }

    public componentWillUnmount() {
        document.removeEventListener("keydown", this.keyDownListener, true);
        document.removeEventListener("click", this.onDocumentClick, false);
        this.quill.off("text-change", this.onTextChange);
        this.quill.off("selection-change", this.onSelectionChange);
    }

    public componentDidUpdate() {
        this.injectComboBoxAccessibility();
    }

    public render() {
        const { suggestions, lastSuccessfulUsername, showLoader, activeSuggestionID } = this.props;
        const isActive = suggestions && suggestions.status === "SUCCESSFUL";
        const data = suggestions && suggestions.status === "SUCCESSFUL" ? suggestions.users : [];

        return (
            <MentionSuggestionList
                onItemClick={this.onItemClick}
                mentionProps={this.createMentionProps(data)}
                matchedString={lastSuccessfulUsername}
                activeItemId={activeSuggestionID}
                isVisible={!!isActive || showLoader}
                id={this.ID}
                loaderID={this.loaderID}
                showLoader={showLoader}
            />
        );
    }

    /**
     * Determine if we have a valid API response.
     */
    private get hasApiResponse() {
        const { suggestions } = this.props;
        return suggestions && suggestions.status === "SUCCESSFUL";
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
        const { inActiveMention } = this.state;
        const { suggestions, activeSuggestionIndex, activeSuggestionID, showLoader } = this.props;

        if (!suggestions || suggestions.status !== "SUCCESSFUL") {
            return;
        }

        if (this.quill.hasFocus() && inActiveMention && !this.hasApiResponse) {
            if (Keyboard.match(event, Keyboard.keys.ENTER)) {
                this.cancelActiveMention();
            }
            return;
        }

        if (this.quill.hasFocus() && inActiveMention) {
            const firstIndex = 0;
            const nextIndex = activeSuggestionIndex + 1;
            const prevIndex = activeSuggestionIndex - 1;
            const lastIndex = showLoader ? suggestions.users.length : suggestions.users.length - 1;
            const currentItemIsLoader = activeSuggestionID === this.loaderID;

            const getIDFromIndex = (newIndex: number) => {
                return showLoader && newIndex === lastIndex ? this.loaderID : suggestions.users[newIndex].domID;
            };

            switch (true) {
                case Keyboard.match(event, Keyboard.keys.DOWN): {
                    const newIndex = activeSuggestionIndex === lastIndex ? firstIndex : nextIndex;
                    const newItemID = getIDFromIndex(newIndex);
                    this.props.setActiveItem(newItemID, newIndex);
                    event.preventDefault();
                    event.stopPropagation();
                    break;
                }
                case Keyboard.match(event, Keyboard.keys.UP): {
                    const newIndex = activeSuggestionIndex === firstIndex ? lastIndex : prevIndex;
                    const newItemID = getIDFromIndex(newIndex);
                    this.props.setActiveItem(newItemID, newIndex);
                    event.preventDefault();
                    event.stopPropagation();
                    break;
                }
                case Keyboard.match(event, Keyboard.keys.ENTER): {
                    if (suggestions.users.length > 0 && !currentItemIsLoader) {
                        this.confirmActiveMention();
                        event.preventDefault();
                        event.stopPropagation();
                    } else {
                        this.cancelActiveMention();
                    }
                    break;
                }
                case Keyboard.match(event, Keyboard.keys.TAB): {
                    if (!currentItemIsLoader) {
                        this.confirmActiveMention();
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    break;
                }
                case Keyboard.match(event, Keyboard.keys.ESCAPE): {
                    this.cancelActiveMention();
                    event.preventDefault();
                    event.stopPropagation();
                    break;
                }
            }
        }
    };

    /**
     * Inject accessibility attributes into the current MentionAutoComplete and ComboBox.
     */
    private injectComboBoxAccessibility = () => {
        const { autoCompleteBlot } = this.state;
        const { activeSuggestionID, activeSuggestionIndex } = this.props;
        if (autoCompleteBlot) {
            autoCompleteBlot.injectAccessibilityAttributes({
                ID: this.comboBoxID,
                activeItemID: activeSuggestionID,
                suggestionListID: this.ID,
                activeItemIsLoader: activeSuggestionID === this.loaderID,
            });
        }
    };

    private createMentionProps(suggestions: IMentionSuggestionData[]): Array<Partial<IMentionProps>> {
        return suggestions.map((data, index) => {
            const onMouseEnter = () => {
                this.props.setActiveItem(data.domID, index);
            };

            return {
                mentionData: data,
                onMouseEnter,
            };
        });
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
        });
        this.isConvertingMention = false;
    }

    /**
     * Convert the active MentionAutoCompleteBlot into a MentionBlot.
     */
    private confirmActiveMention(insertCharacter: string = " ") {
        const { autoCompleteBlot } = this.state;
        const { suggestions, activeSuggestionIndex } = this.props;
        if (
            !(autoCompleteBlot instanceof MentionAutoCompleteBlot) ||
            this.isConvertingMention ||
            !suggestions ||
            suggestions.status !== "SUCCESSFUL"
        ) {
            return;
        }

        this.isConvertingMention = true;
        const activeSuggestion = suggestions.users[activeSuggestionIndex];
        const start = autoCompleteBlot.offset(this.quill.scroll);

        autoCompleteBlot.finalize(activeSuggestion);
        this.quill.insertText(start + 1, insertCharacter, Quill.sources.SILENT);
        this.quill.setSelection(start + 2, 0, Quill.sources.SILENT);
        this.cancelActiveMention();
    }

    private onDocumentClick = (event: MouseEvent) => {
        if (!this.quill.root.contains(event.target as Node)) {
            this.cancelActiveMention();
        }
    };

    /**
     * Watch for selection change events in quill. We need to clear the mention list if we have text selected or their is no selection.
     */
    private onSelectionChange = (range: RangeStatic, oldRange: RangeStatic, sources) => {
        if (sources !== Quill.sources.USER || !this.state.inActiveMention || !this.hasApiResponse) {
            return;
        }

        if (!range || range.length > 0) {
            return this.cancelActiveMention();
        }

        // The range is updated before the text content, so we need to step back one character sometimes.
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

        const lastOperation = delta.ops && delta.ops.length > 0 ? delta.ops[delta.ops.length - 1] : null;
        if (
            lastOperation &&
            lastOperation.insert &&
            this.MENTION_COMPLETION_CHARACTERS.includes(lastOperation.insert)
        ) {
            const { suggestions } = this.props;
            const users = suggestions && suggestions.status === "SUCCESSFUL" ? suggestions.users : [];

            const isASingleExactMatch = users.length === 1 && this.props.lastSuccessfulUsername === users[0].name;
            // Autocomplete the mention if certain conditions occur.

            if (isASingleExactMatch) {
                setImmediate(() => {
                    this.confirmActiveMention(lastOperation.insert);
                });
                return;
            }
        }

        this.setState({
            autoCompleteBlot,
            inActiveMention: true,
        });
        this.props.lookupMentions(autoCompleteBlot.username);
    };
}

function mapDispatchToProps(dispatch: Dispatch<any>) {
    const mentionLookupFn = username => dispatch(mentionThunks.loadUsers(username));

    return {
        lookupMentions: debounce(mentionLookupFn, 50),
        setActiveItem: (itemID: string, itemIndex: number) =>
            dispatch(mentionActions.setActiveSuggestion(itemID, itemIndex)),
    };
}

function mapStateToProps(state: IStoreState) {
    const {
        lastSuccessfulUsername,
        currentUsername,
        usersTrie,
        activeSuggestionID,
        activeSuggestionIndex,
    } = state.editor.mentions;

    const currentNode = currentUsername && usersTrie.getValue(currentUsername);
    const showLoader = currentNode && currentNode.status === "PENDING";

    return {
        suggestions: lastSuccessfulUsername ? usersTrie.getValue(lastSuccessfulUsername) : null,
        lastSuccessfulUsername,
        activeSuggestionID,
        activeSuggestionIndex,
        showLoader,
    };
}

const withRedux = connect(
    mapStateToProps,
    mapDispatchToProps,
);

export default withRedux(withEditor<IProps>(MentionToolbar));
