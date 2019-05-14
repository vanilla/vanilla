/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Quill, { Sources, DeltaStatic } from "quill/core";
import uniqueId from "lodash/uniqueId";
import isEqual from "lodash/isEqual";
import Keyboard from "quill/modules/keyboard";
import { IWithEditorProps } from "@rich-editor/editor/context";
import { withEditor } from "@rich-editor/editor/withEditor";
import MentionAutoCompleteBlot from "@rich-editor/quill/blots/embeds/MentionAutoCompleteBlot";
import { getBlotAtIndex } from "@rich-editor/quill/utility";
import { connect } from "react-redux";
import { LoadStatus } from "@library/@types/api/core";
import UserSuggestionModel, {
    IInjectableSuggestionsProps,
} from "@library/features/users/suggestion/UserSuggestionModel";
import UserSuggestionActions from "@library/features/users/suggestion/UserSuggestionActions";
import apiv2 from "@library/apiv2";
import { IMentionSuggestionData, IMentionProps } from "@rich-editor/toolbars/pieces/MentionSuggestion";
import MentionSuggestionList from "@rich-editor/toolbars/pieces/MentionSuggestionList";

interface IProps extends IWithEditorProps, IInjectableSuggestionsProps {
    suggestionActions: UserSuggestionActions;
}

interface IMentionState {
    autoCompleteBlot: MentionAutoCompleteBlot | null;
}

/**
 * Module for inserting, removing, and editing at-mentions.
 */
export class MentionToolbar extends React.Component<IProps, IMentionState> {
    private static SUGGESTION_LIMIT = 5;
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
            autoCompleteBlot: null,
        };
    }

    public componentDidMount() {
        document.addEventListener("keydown", this.keyDownListener, true);
        document.addEventListener("click", this.onDocumentClick, false);
        this.quill.on("text-change", this.onTextChange);
    }

    public componentWillUnmount() {
        document.removeEventListener("keydown", this.keyDownListener, true);
        document.removeEventListener("click", this.onDocumentClick, false);
        this.quill.off("text-change", this.onTextChange);
    }

    /**
     * When this component updates we need to see if the selection state has changed and trigger a lookup.
     */
    public componentDidUpdate(prevProps: IProps) {
        const { mentionSelection } = this.props;
        const prevMentionSelection = prevProps.mentionSelection;

        if (!isEqual(mentionSelection, prevMentionSelection) && mentionSelection) {
            const text = this.quill.getText(mentionSelection.index, mentionSelection.length).replace("@", "");
            this.props.suggestionActions.loadUsers(text);
        }

        // If we have loading or valid suggestions and a valid mention selection
        // Create an autocompleteblot if it doesn't exist, and inject accessibility attributes into it.
        const suggestions = this.props.suggestions;
        if (suggestions) {
            const isLoading = suggestions && suggestions.status === LoadStatus.LOADING;
            const isSuccess =
                suggestions &&
                suggestions.status === LoadStatus.SUCCESS &&
                suggestions.data &&
                suggestions.data.length > 0;

            if (mentionSelection && (isLoading || isSuccess)) {
                if (!this.state.autoCompleteBlot) {
                    const autoCompleteBlot = this.createAutoCompleteBlot();
                    this.setState({ autoCompleteBlot });
                }
                this.injectComboBoxAccessibility();
                return;
            }
        }

        if (this.state.autoCompleteBlot) {
            const selection = this.quill!.getSelection();
            this.cancelActiveMention();

            // We need to restore back the selection we had if the editor is still focused because
            // the cancelation might have messed up our position.
            if (this.quill.hasFocus()) {
                this.quill!.setSelection(selection);
            }
        }
    }

    public render() {
        const { suggestions, lastSuccessfulUsername, activeSuggestionID, isLoading } = this.props;
        const data =
            suggestions && suggestions.status === LoadStatus.SUCCESS && suggestions.data ? suggestions.data : [];

        return (
            <MentionSuggestionList
                onItemClick={this.onItemClick}
                mentionProps={this.createMentionProps(data)}
                matchedString={lastSuccessfulUsername || ""}
                activeItemId={activeSuggestionID}
                id={this.ID}
                loaderID={this.loaderID}
                showLoader={isLoading}
            />
        );
    }

    /**
     * Get an autocomplete blot. Creates a new one if we haven't made one yet.
     */
    private createAutoCompleteBlot(): MentionAutoCompleteBlot | null {
        const { currentSelection, mentionSelection } = this.props;
        if (!currentSelection || !mentionSelection) {
            return null;
        }

        this.quill.formatText(
            mentionSelection.index,
            mentionSelection.length,
            "mention-autocomplete",
            true,
            Quill.sources.API,
        );
        this.quill.setSelection(currentSelection.index, 0, Quill.sources.API);

        // Get the autoCompleteBlot
        const autoCompleteBlot = getBlotAtIndex(this.quill, currentSelection.index - 1, MentionAutoCompleteBlot)!;

        return autoCompleteBlot;
    }

    /**
     * Determine if we have a valid API response.
     */
    private get hasApiResponse() {
        const { suggestions } = this.props;
        return suggestions && suggestions.status === LoadStatus.SUCCESS;
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
        const { suggestions, activeSuggestionIndex, activeSuggestionID, isLoading } = this.props;
        const { mentionSelection } = this.props;
        const inActiveMention = mentionSelection !== null;

        if (!suggestions || suggestions.status !== LoadStatus.SUCCESS || !suggestions.data) {
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
            const userLength = Math.min(MentionToolbar.SUGGESTION_LIMIT, suggestions.data.length);
            const lastIndex = isLoading ? userLength : userLength - 1;
            const currentItemIsLoader = activeSuggestionID === this.loaderID;

            const getIDFromIndex = (newIndex: number) => {
                return isLoading && newIndex === lastIndex ? this.loaderID : suggestions.data![newIndex].domID;
            };

            switch (true) {
                case Keyboard.match(event, Keyboard.keys.DOWN): {
                    const newIndex = activeSuggestionIndex === lastIndex ? firstIndex : nextIndex;
                    const newItemID = getIDFromIndex(newIndex);
                    this.props.suggestionActions.setActive(newItemID, newIndex);
                    event.preventDefault();
                    event.stopPropagation();
                    break;
                }
                case Keyboard.match(event, Keyboard.keys.UP): {
                    const newIndex = activeSuggestionIndex === firstIndex ? lastIndex : prevIndex;
                    const newItemID = getIDFromIndex(newIndex);
                    this.props.suggestionActions.setActive(newItemID, newIndex);
                    event.preventDefault();
                    event.stopPropagation();
                    break;
                }
                case Keyboard.match(event, Keyboard.keys.ENTER): {
                    if (suggestions.data.length > 0 && !currentItemIsLoader) {
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
        return suggestions.slice(0, MentionToolbar.SUGGESTION_LIMIT).map((data, index) => {
            const onMouseEnter = () => {
                this.props.suggestionActions.setActive(data.domID, index);
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
    private cancelActiveMention() {
        if (this.state.autoCompleteBlot && !this.isConvertingMention) {
            this.isConvertingMention = true;
            this.state.autoCompleteBlot.cancel();
        }
        this.isConvertingMention = false;
        this.setState({
            autoCompleteBlot: null,
        });
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
            suggestions.status !== LoadStatus.SUCCESS ||
            !suggestions.data
        ) {
            return;
        }

        this.isConvertingMention = true;
        const activeSuggestion = suggestions.data[activeSuggestionIndex];
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

        const lastOperation = delta.ops && delta.ops.length > 0 ? delta.ops[delta.ops.length - 1] : null;
        if (
            lastOperation &&
            lastOperation.insert &&
            this.MENTION_COMPLETION_CHARACTERS.includes(lastOperation.insert)
        ) {
            const { suggestions } = this.props;
            const users =
                suggestions && suggestions.status === LoadStatus.SUCCESS && suggestions.data ? suggestions.data : [];

            const isASingleExactMatch = users.length === 1 && this.props.lastSuccessfulUsername === users[0].name;
            // Autocomplete the mention if certain conditions occur.

            if (isASingleExactMatch) {
                window.requestAnimationFrame(() => {
                    this.confirmActiveMention(lastOperation.insert);
                });
                return;
            }
        }
    };
}

const withRedux = connect(
    UserSuggestionModel.mapStateToProps,
    dispatch => ({
        suggestionActions: new UserSuggestionActions(dispatch, apiv2),
    }),
);

export default withRedux(withEditor(MentionToolbar));
