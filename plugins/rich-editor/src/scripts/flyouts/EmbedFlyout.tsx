/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import { isAllowedUrl, t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { useEditor } from "@rich-editor/editor/context";
import { IconForButtonWrap } from "@rich-editor/editor/pieces/IconForButtonWrap";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import { insertMediaClasses } from "@rich-editor/flyouts/pieces/insertMediaClasses";
import { forceSelectionUpdate } from "@rich-editor/quill/utility";
import classNames from "classnames";
import KeyboardModule from "quill/modules/keyboard";
import React, { useEffect, useMemo, useRef, useState } from "react";
import { style } from "typestyle";
import { EmbedIcon } from "@library/icons/editorIcons";

interface IProps {
    disabled?: boolean;
    renderAbove?: boolean;
    renderLeft?: boolean;
}

export default function EmbedFlyout(props: IProps) {
    const { quill, legacyMode } = useEditor();
    const inputRef = useRef<HTMLInputElement>(null);
    const embedModule = useMemo(() => quill && quill.getModule("embed/insertion"), [quill]);
    const id = useMemo(() => uniqueIDFromPrefix("embedPopover"), []);
    const titleID = id + "-title";
    const descriptionID = id + "-description";

    const [isInputValid, setInputValid] = useState(false);
    const [url, setUrl] = useState("");

    const clearInput = () => {
        setUrl("");
    };

    /**
     * Normalize the URL with a prepended http if there isn't one.
     */
    const normalizeUrl = (urlToNormalize: string) => {
        const result = urlToNormalize.match(/^https?:\/\//) ? urlToNormalize : "http://" + urlToNormalize;
        return result;
    };

    const submitUrl = () => {
        clearInput();
        embedModule.scrapeMedia(normalizeUrl(url));
    };

    /**
     * Handle key-presses for the link toolbar.
     */
    const buttonKeyDownHandler = (event: React.KeyboardEvent<any>) => {
        if (KeyboardModule.match(event.nativeEvent, "enter")) {
            event.preventDefault();
            event.stopPropagation();
            isInputValid && submitUrl();
        }
    };

    /**
     * Handle a submit button click..
     */
    const buttonClickHandler = (event: React.MouseEvent<any>) => {
        event.preventDefault();
        submitUrl();
    };

    /**
     * Control the inputs value.
     */
    const inputChangeHandler = (event: React.ChangeEvent<any>) => {
        setUrl(normalizeUrl(event.target.value));
    };

    // We need to check the value after we've set it with setUrl
    useEffect(() => {
        setInputValid(isAllowedUrl(url));
    }, [url]);

    const classesRichEditor = richEditorClasses(legacyMode);
    const classesInsertMedia = insertMediaClasses();
    const placeholderText = `https://`;

    function handleVisibilityChange(newVisibility: boolean) {
        if (newVisibility) {
            inputRef.current && inputRef.current.focus();
        }
    }

    return (
        <>
            <DropDown
                id={id}
                name={t("Insert Media")}
                buttonClassName={classNames("richEditor-button", "richEditor-embedButton", classesRichEditor.button)}
                title={t("Insert Media")}
                onVisibilityChange={handleVisibilityChange}
                disabled={props.disabled}
                buttonContents={<IconForButtonWrap icon={<EmbedIcon />} />}
                buttonBaseClass={ButtonTypes.CUSTOM}
                renderAbove={!!props.renderAbove}
                renderLeft={!!props.renderLeft}
                initialFocusElement={inputRef.current}
                flyoutType={FlyoutType.FRAME}
                contentsClassName={!legacyMode ? classesRichEditor.flyoutOffset : ""}
            >
                <Frame
                    body={
                        <FrameBody>
                            <p className={style({ marginTop: 6, marginBottom: 6 })}>
                                {t("Paste the URL of the media you want.")}
                            </p>
                            <input
                                className={classNames("InputBox", classesInsertMedia.insert, {
                                    inputText: !legacyMode,
                                })}
                                placeholder={placeholderText}
                                value={url}
                                onChange={inputChangeHandler}
                                onKeyDown={buttonKeyDownHandler}
                                aria-labelledby={titleID}
                                aria-describedby={descriptionID}
                                ref={inputRef}
                            />
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter>
                            <Button
                                className={classNames("insertMedia-insert", classesInsertMedia.button)}
                                baseClass={ButtonTypes.TEXT_PRIMARY}
                                disabled={!isInputValid}
                                onClick={buttonClickHandler}
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
