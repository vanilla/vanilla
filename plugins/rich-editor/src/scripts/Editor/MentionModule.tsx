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
import { logError } from "@core/utility";
import { getMentionRange, getBlotAtIndex } from "../Quill/utility";
import api from "@core/apiv2";
import MentionAutoCompleteBlot from "../Quill/Blots/Embeds/MentionAutoCompleteBlot";
import MentionBlot from "../Quill/Blots/Embeds/MentionBlot";
import { t, isAllowedUrl } from "@core/application";
import SelectionPositionToolbar from "./SelectionPositionToolbarContainer";
import Toolbar from "./Generic/Toolbar";
import { withEditor, IEditorContextProps } from "./ContextProvider";
import { IMenuItemData } from "./Generic/MenuItem";
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

export class MentionModule extends React.PureComponent<IProps, IState> {
    private quill: Quill;
    private ID = uniqueId("mentionList-");
    private comboBoxID = uniqueId("mentionComboBox-");
    private isConvertingMention = false;

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
        if (!this.state.inActiveMention || !this.state.hasApiResponse) {
            return null;
        }
        const styles = this.getStyles();

        return (
            <MentionList
                onItemClick={this.onItemClick}
                mentionData={this.state.suggestions}
                matchedString={this.state.username}
                activeItemId={this.state.activeItemID}
                id={this.ID}
                style={styles}
            />
        );
    }

    /**
     * Get styles to absolute position the results next to the current text.
     */
    private getStyles(): React.CSSProperties {
        const { startIndex, inActiveMention, hasApiResponse } = this.state;
        const quillBounds = this.quill.getBounds(startIndex);
        const offset = 3;

        return {
            position: "absolute",
            top: quillBounds.bottom + offset,
            left: quillBounds.left,
            zIndex: 1,
            display: inActiveMention && hasApiResponse ? "block" : "none",
        };
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
        if (this.quill.hasFocus() && this.state.inActiveMention) {
            const { activeItemIndex, suggestions } = this.state;
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
                    this.resetMentionState();
                    event.preventDefault();
                    break;
            }
        }
    };

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
            .get("/users/by-names/", { params })
            .then(response => {
                mentionCache.set(username, response);
                return this.handleMentionResponse(response);
            })
            .catch(logError);
    }

    private generateIdForMentionData(data: IMentionData) {
        return this.props.editorID + "-mentionItem-" + data.userID;
    }

    private injectComboBoxAccessibility = () => {
        const { autoCompleteBlot, activeItemID } = this.state;
        if (autoCompleteBlot) {
            autoCompleteBlot.injectAccessibilityAttributes({
                ID: this.comboBoxID,
                activeItemID,
                mentionListID: this.ID,
            });
        }
    };

    /**
     * Handle mention responses from the API.
     */
    private handleMentionResponse(response: AxiosResponse | null) {
        if (!response) {
            return;
        }

        const suggestions = response.data.map((data: IMentionData) => {
            data.uniqueID = this.generateIdForMentionData(data);
            data.onMouseEnter = () => {
                this.setState({ activeItemID: data.uniqueID }, this.injectComboBoxAccessibility);
            };
            return data;
        });

        this.setState(
            {
                suggestions,
                activeItemID: suggestions.length > 0 ? suggestions[0].uniqueID : "",
                activeItemIndex: 0,
                hasApiResponse: true,
            },
            this.injectComboBoxAccessibility,
        );
    }

    /**
     * Watch for selection change events in quill. We need to clear the mention list if we have text selected or their is no selection.
     */
    private onSelectionChange = (range: RangeStatic, oldRange: RangeStatic, sources) => {
        if (sources === Quill.sources.SILENT || !this.state.inActiveMention || !this.state.hasApiResponse) {
            return;
        }

        if (!range || range.length > 0) {
            return this.resetMentionState();
        }

        // Bail out if we're in a mention in progress.
        if (getBlotAtIndex(this.quill, range.index, MentionAutoCompleteBlot)) {
            return;
        }

        // Clear the mention the new selection doesn't match.
        const mentionRange = getMentionRange(this.quill, range);
        if (mentionRange === null && !this.isConvertingMention) {
            return this.resetMentionState();
        }
    };

    /**
     * Reset the component's mention state. Also clears the current combobox.
     *
     * @param clearComboBox - Whether or not to clear the current combobox. An situation where you would not want to do this is if it is already deleted or it has already been detached from quill.
     */
    private resetMentionState(clearComboBox = true) {
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
        this.resetMentionState();
    }

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
        const range = this.quill.getSelection();
        if (range == null || range.index == null) {
            return this.resetMentionState(false);
        }

        // Clear the mention the new selection doesn't match.
        const mentionRange = getMentionRange(this.quill, range);
        if (mentionRange === null) {
            return this.resetMentionState();
        }

        // Create a autoCompleteBlot if it doesn't already exist.
        let autoCompleteBlot = getBlotAtIndex(this.quill, range.index - 1, MentionAutoCompleteBlot);
        if (!autoCompleteBlot) {
            this.quill.formatText(
                mentionRange.index,
                mentionRange.length,
                "mention-autocomplete",
                true,
                Quill.sources.API,
            );
            this.quill.setSelection(range.index, 0, Quill.sources.API);

            // Get the autoCompleteBlot
            autoCompleteBlot = getBlotAtIndex(this.quill, range.index - 1, MentionAutoCompleteBlot)!;
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
