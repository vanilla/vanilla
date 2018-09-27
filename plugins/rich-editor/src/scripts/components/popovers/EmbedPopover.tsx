/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import KeyboardModule from "quill/modules/keyboard";
import { t, isAllowedUrl } from "@library/application";
import { getRequiredID, IRequiredComponentID } from "@library/componentIDs";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import * as Icons from "@rich-editor/components/icons";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
import Popover from "@rich-editor/components/popovers/pieces/Popover";
import PopoverController, { IPopoverControllerChildParameters } from "@library/components/PopoverController";
import { forceSelectionUpdate } from "@rich-editor/quill/utility";

interface IProps extends IWithEditorProps {}

interface IState extends IRequiredComponentID {
    id: string;
    url: string;
    isInputValid: boolean;
}

export class EmbedPopover extends React.PureComponent<IProps, IState> {
    private embedModule: EmbedInsertionModule;

    public constructor(props) {
        super(props);
        this.embedModule = props.quill.getModule("embed/insertion");
        this.state = {
            id: getRequiredID(props, "embedPopover"),
            url: "",
            isInputValid: false,
        };
    }

    get titleID(): string {
        return this.state.id + "-title";
    }

    get descriptionID(): string {
        return this.state.id + "-description";
    }

    public render() {
        const title = t("Insert Media");
        const Icon = <Icons.embed />;

        return (
            <PopoverController
                id={this.state.id}
                classNameRoot="embedDialogue"
                icon={Icon}
                onClose={this.clearInput}
                buttonClasses="richEditor-button richEditor-embedButton"
                onVisibilityChange={forceSelectionUpdate}
            >
                {(params: IPopoverControllerChildParameters) => {
                    const { initialFocusRef, closeMenuHandler, isVisible } = params;
                    const body = (
                        <React.Fragment>
                            <p id={this.descriptionID} className="insertMedia-description">
                                {t("Paste the URL of the media you want.")}
                            </p>
                            <input
                                className="InputBox"
                                placeholder={t("http://")}
                                value={this.state.url}
                                onChange={this.inputChangeHandler}
                                onKeyDown={this.buttonKeyDownHandler}
                                aria-labelledby={this.titleID}
                                aria-describedby={this.descriptionID}
                                ref={initialFocusRef}
                            />
                        </React.Fragment>
                    );

                    // The blur handler goes on the link if the button is disabled.
                    // We want it to be on the last element in the popover.
                    const footer = (
                        <React.Fragment>
                            <input
                                type="button"
                                className="Button Primary insertMedia-insert"
                                value={"Insert"}
                                disabled={!this.state.isInputValid}
                                aria-label={"Insert Media"}
                                onClick={this.buttonClickHandler}
                            />
                        </React.Fragment>
                    );

                    return (
                        <Popover
                            id={params.id}
                            descriptionID={this.descriptionID}
                            titleID={this.titleID}
                            title={title}
                            body={body}
                            footer={footer}
                            additionalClassRoot="insertMedia"
                            onCloseClick={closeMenuHandler}
                            isVisible={isVisible}
                        />
                    );
                }}
            </PopoverController>
        );
    }

    private clearInput = () => {
        this.setState({
            url: "",
        });
    };

    private submitUrl() {
        this.clearInput();
        this.embedModule.scrapeMedia(this.normalizeUrl(this.state.url));
    }

    /**
     * Handle key-presses for the link toolbar.
     */
    private buttonKeyDownHandler = (event: React.KeyboardEvent<any>) => {
        if (KeyboardModule.match(event.nativeEvent, "enter")) {
            event.preventDefault();
            event.stopPropagation();
            this.state.isInputValid && this.submitUrl();
        }
    };

    /**
     * Handle a submit button click..
     */
    private buttonClickHandler = (event: React.MouseEvent<any>) => {
        event.preventDefault();
        this.submitUrl();
    };

    /**
     * Control the inputs value.
     */
    private inputChangeHandler = (event: React.ChangeEvent<any>) => {
        const url = event.target.value;
        const isInputValid = isAllowedUrl(this.normalizeUrl(url));
        this.setState({ url, isInputValid });
    };

    /**
     * Normalize the URL with a prepended http if there isn't one.
     */
    private normalizeUrl(url: string) {
        const result = url.match(/^https?:\/\//) ? url : "http://" + url;
        return result;
    }
}

export default withEditor<IProps>(EmbedPopover);
