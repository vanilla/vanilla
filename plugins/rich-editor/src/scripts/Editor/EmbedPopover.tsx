/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import KeyboardModule from "quill/modules/keyboard";
import { t, isAllowedUrl } from "@core/application";
import { withEditor, IEditorContextProps } from "./ContextProvider";
import Popover from "./Generic/Popover";
import PopoverController, { IPopoverControllerChildParameters } from "./Generic/PopoverController";
import EmbedInsertionModule from "../Quill/EmbedInsertionModule";
import * as Icons from "./Icons";

interface IProps extends IEditorContextProps {}

interface IState {
    url: string;
    isInputValid: boolean;
}

export class EmbedPopover extends React.PureComponent<IProps, IState> {
    public state = {
        url: "",
        isInputValid: false,
    };

    private embedModule: EmbedInsertionModule;
    private titleId: string;
    private descriptionId: string;

    public constructor(props) {
        super(props);
        this.embedModule = props.quill.getModule("embed/insertion");
        this.titleId = props.id + "-title";
        this.descriptionId = props.id + "-description";
    }

    public render() {
        const title = t("Insert Media");
        const description = t("Insert an embedded web page, or video into your message.");

        const Icon = <Icons.embed />;

        return (
            <PopoverController classNameRoot="embedDialogue" icon={Icon} onClose={this.clearInput}>
                {(params: IPopoverControllerChildParameters) => {
                    const { initialFocusRef, closeMenuHandler, blurHandler, isVisible } = params;

                    const body = (
                        <React.Fragment>
                            <p className="insertMedia-description">{t("Paste the URL of the media you want.")}</p>
                            <input
                                className="InputBox"
                                placeholder={t("http://")}
                                value={this.state.url}
                                onChange={this.inputChangeHandler}
                                onKeyDown={this.buttonKeyDownHandler}
                                aria-labelledby={this.titleId}
                                aria-describedby={this.descriptionId}
                                ref={initialFocusRef}
                            />
                        </React.Fragment>
                    );

                    // The blur handler goes on the link if the button is disabled.
                    // We want it to be on the last element in the popover.
                    const footer = (
                        <React.Fragment>
                            <a
                                onBlur={this.state.isInputValid ? undefined : blurHandler}
                                href="#"
                                className="insertMedia-help"
                                aria-label={t("Get Help on Inserting Media")}
                            >
                                {t("Help")}
                            </a>

                            <input
                                type="button"
                                className="Button Primary insertMedia-insert"
                                value={"Insert"}
                                disabled={!this.state.isInputValid}
                                aria-label={"Insert Media"}
                                onBlur={this.state.isInputValid ? blurHandler : undefined}
                                onClick={this.buttonClickHandler}
                            />
                        </React.Fragment>
                    );

                    return (
                        <Popover
                            title={title}
                            accessibleDescription={description}
                            body={body}
                            footer={footer}
                            additionalClassRoot="insertMedia"
                            onCloseClick={closeMenuHandler}
                            isVisible={isVisible}
                            titleId={this.titleId}
                            descriptionId={this.descriptionId}
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
            this.submitUrl();
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
        return url.match(/$https?:\/\//) ? url : "http://" + url;
    }
}

export default withEditor(EmbedPopover);
