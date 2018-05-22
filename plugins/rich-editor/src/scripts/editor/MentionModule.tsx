/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import Parchment from "parchment";
import Quill, { RangeStatic, Sources, DeltaStatic } from "quill/core";
import Delta from "quill-delta";
import { AxiosResponse } from "axios";
import Emitter from "quill/core/emitter";
import uniqueId from "lodash/uniqueId";
import Keyboard from "quill/modules/keyboard";
import LinkBlot from "quill/formats/link";
import axios from "axios";
import { logError } from "@dashboard/utility";
import { getMentionRange, getBlotAtIndex } from "../quill/utility";
import api from "@dashboard/apiv2";
import MentionAutoCompleteBlot from "../quill/blots/embeds/MentionAutoCompleteBlot";
import MentionBlot from "../quill/blots/embeds/MentionBlot";
import { t, isAllowedUrl } from "@dashboard/application";
import SelectionPositionToolbar from "./SelectionPositionToolbarContainer";
import Toolbar from "./generic/Toolbar";
import { withEditor, IEditorContextProps } from "./ContextProvider";
import { IMenuItemData } from "./generic/MenuItem";
import MentionList from "./MentionList";
import { IMentionData } from "./MentionSuggestion";

interface IProps extends IEditorContextProps {}

interface IState {
    inActiveMention: boolean;
    autoCompleteBlot: MentionAutoCompleteBlot | null;
    suggestions: IMentionData[];
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
export class MentionModule extends React.PureComponent<IProps, IState> {
    private quill: Quill;
    private ID = uniqueId("mentionList-");
    private noResultsID = uniqueId("mentionList-noResults-");
    private comboBoxID = uniqueId("mentionComboBox-");
    private isConvertingMention = false;
    private apiCancelSource = axios.CancelToken.source();

    constructor(props: IProps) {
        super(props);
        this.quill = props.quill!;
        this.state = {
            inActiveMention: false,
            autoCompleteBlot: null,
            suggestions: [],
            username: "",
            startIndex: 0,
            activeItemID: "",
            activeItemIndex: 0,
            hasApiResponse: false,
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
        return (
            <MentionList
                onItemClick={this.onItemClick}
                mentionData={this.state.suggestions}
                matchedString={this.state.username}
                activeItemId={this.state.activeItemID}
                isVisible={this.state.inActiveMention && this.state.hasApiResponse}
                id={this.ID}
                noResultsID={this.noResultsID}
            />
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
        const { activeItemIndex, suggestions, inActiveMention, hasApiResponse } = this.state;
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
            const lastIndex = suggestions.length - 1;

            switch (true) {
                case Keyboard.match(event, Keyboard.keys.DOWN):
                    newIndex = activeItemIndex === lastIndex ? firstIndex : nextIndex;
                    newItemID = this.generateIdForMentionData(suggestions[newIndex]);
                    this.setState({
                        activeItemID: newItemID,
                        activeItemIndex: newIndex,
                    });
                    this.injectComboBoxAccessibility();
                    event.preventDefault();
                    break;
                case Keyboard.match(event, Keyboard.keys.UP):
                    newIndex = activeItemIndex === firstIndex ? lastIndex : prevIndex;
                    newItemID = this.generateIdForMentionData(suggestions[newIndex]);
                    this.setState({
                        activeItemID: newItemID,
                        activeItemIndex: newIndex,
                    });
                    this.injectComboBoxAccessibility();
                    event.preventDefault();
                    break;
                case Keyboard.match(event, Keyboard.keys.ENTER):
                case Keyboard.match(event, Keyboard.keys.TAB):
                    this.confirmActiveMention();
                    event.preventDefault();
                    break;
                case Keyboard.match(event, Keyboard.keys.ESCAPE):
                    this.cancelActiveMention();
                    event.preventDefault();
                    break;
            }
        }
    };

    /**
     * Generate an ID for a mention suggestion.
     */
    private generateIdForMentionData(data: IMentionData) {
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

    /**
     * Make an API request for mention suggestions. These results are cached by the lookup username.
     */
    private lookupMention(username: string) {
        if (mentionCache.has(username)) {
            return this.handleMentionResponse(mentionCache.get(username)!);
        }

        // Cache the result as null for now.
        mentionCache.set(username, null);

        // Make the result.
        const params = {
            name: username + "*",
            order: "mention",
            limit: 5,
        };

        api
            .get("/users/by-names/", { params, cancelToken: this.apiCancelSource.token })
            .then(response => {
                mentionCache.set(username, response);
                return this.handleMentionResponse(response);
            })
            .catch(logError);
    }

    /**
     * Handle mention responses from the API.
     */
    private handleMentionResponse(response: AxiosResponse | null) {
        if (!response) {
            return;
        }

        const suggestions = response.data.map((data: IMentionData, index) => {
            data.uniqueID = this.generateIdForMentionData(data);
            data.onMouseEnter = () => {
                this.setState(
                    { activeItemID: data.uniqueID, activeItemIndex: index },
                    this.injectComboBoxAccessibility,
                );
            };
            return data;
        });

        this.setState(
            {
                suggestions,
                activeItemID: suggestions.length > 0 ? suggestions[0].uniqueID : null,
                activeItemIndex: 0,
                hasApiResponse: true,
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
            this.quill.update(Quill.sources.SILENT);
            this.quill.setSelection(selection, Quill.sources.SILENT);
        }
        this.setState({
            inActiveMention: false,
            autoCompleteBlot: null,
            username: "",
            suggestions: [],
            hasApiResponse: false,
        });
        this.isConvertingMention = false;
    }

    /**
     * Convert the active MentionAutoCompleteBlot into a MentionBlot.
     */
    private confirmActiveMention() {
        const { autoCompleteBlot, suggestions, activeItemIndex } = this.state;
        if (!(autoCompleteBlot instanceof MentionAutoCompleteBlot) || this.isConvertingMention) {
            return;
        }

        this.isConvertingMention = true;
        const activeSuggestion = suggestions[activeItemIndex];
        const start = autoCompleteBlot.offset(this.quill.scroll);

        autoCompleteBlot.finalize(activeSuggestion);
        this.quill.update(Quill.sources.SILENT);
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
        this.lookupMention(autoCompleteBlot.username);
    };
}

export default withEditor<IProps>(MentionModule);
