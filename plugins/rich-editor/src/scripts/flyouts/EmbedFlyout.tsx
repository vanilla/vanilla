/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DropDown, { FlyoutSizes } from "@library/flyouts/DropDown";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { embed } from "@library/icons/editorIcons";
import { IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { isAllowedUrl, t } from "@library/utility/appUtils";
import { getRequiredID, IRequiredComponentID } from "@library/utility/idUtils";
import { IWithEditorProps } from "@rich-editor/editor/context";
import { withEditor } from "@rich-editor/editor/withEditor";
import { IconForButtonWrap } from "@rich-editor/editor/pieces/IconForButtonWrap";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import { insertMediaClasses } from "@rich-editor/flyouts/pieces/insertMediaClasses";
import EmbedInsertionModule from "@rich-editor/quill/EmbedInsertionModule";
import { forceSelectionUpdate } from "@rich-editor/quill/utility";
import classNames from "classnames";
import KeyboardModule from "quill/modules/keyboard";
import React from "react";
import { style } from "typestyle";
import Flyout from "@rich-editor/flyouts/pieces/Flyout";

interface IProps extends IWithEditorProps, IDeviceProps {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
    legacyMode: boolean;
}

interface IState extends IRequiredComponentID {
    id: string;
    url: string;
    isInputValid: boolean;
}

export class EmbedFlyout extends React.PureComponent<IProps, IState> {
    private embedModule: EmbedInsertionModule;
    private inputRef = React.createRef<HTMLInputElement>();

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
        const classesRichEditor = richEditorClasses(this.props.legacyMode);
        const classesInsertMedia = insertMediaClasses();
        const placeholderText = `https://`;
        return (
            <>
                <DropDown
                    id={this.state.id}
                    name={t("Insert Media")}
                    buttonClassName={classNames(
                        "richEditor-button",
                        "richEditor-embedButton",
                        classesRichEditor.button,
                    )}
                    title={t("Insert Media")}
                    paddedList={true}
                    onClose={this.clearInput}
                    onVisibilityChange={forceSelectionUpdate}
                    disabled={this.props.disabled}
                    buttonContents={<IconForButtonWrap icon={embed()} />}
                    buttonBaseClass={ButtonTypes.CUSTOM}
                    renderAbove={!!this.props.renderAbove}
                    renderLeft={!!this.props.renderLeft}
                    selfPadded={true}
                    initialFocusElement={this.inputRef.current}
                    flyoutSize={FlyoutSizes.MEDIUM}
                    contentsClassName={!this.props.legacyMode ? classesRichEditor.flyoutOffset : ""}
                >
                    <Frame
                        body={
                            <FrameBody>
                                <p className={style({ marginTop: 6, marginBottom: 6 })}>
                                    {t("Paste the URL of the media you want.")}
                                </p>
                                <input
                                    className={classNames("InputBox", classesInsertMedia.insert, {
                                        inputText: !this.props.legacyMode,
                                    })}
                                    placeholder={placeholderText}
                                    value={this.state.url}
                                    onChange={this.inputChangeHandler}
                                    onKeyDown={this.buttonKeyDownHandler}
                                    aria-labelledby={this.titleID}
                                    aria-describedby={this.descriptionID}
                                    ref={this.inputRef}
                                />
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter>
                                <Button
                                    className={classNames("insertMedia-insert", classesInsertMedia.button)}
                                    baseClass={ButtonTypes.TEXT_PRIMARY}
                                    disabled={!this.state.isInputValid}
                                    onClick={this.buttonClickHandler}
                                >
                                    {t("Insert")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </DropDown>
            </>
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

export default withDevice(withEditor<IProps>(EmbedFlyout));
