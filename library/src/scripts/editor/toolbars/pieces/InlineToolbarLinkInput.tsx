/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { FormEvent } from "react";
import { t } from "@library/utility/appUtils";
import CloseButton from "@library/navigation/CloseButton";
import classNames from "classnames";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import { richEditorClasses } from "@library/editor/richEditorStyles";
import { insertLinkClasses } from "@library/editor/toolbars/pieces/insertLinkClasses";
import Keyboard from "quill/modules/keyboard";

interface IProps {
    inputRef: React.RefObject<HTMLInputElement>;
    inputValue: string;
    onInputKeyDown?: React.KeyboardEventHandler<any>;
    onInputChange: React.ChangeEventHandler<any>;
    onCloseClick: React.MouseEventHandler<any>;
    onSubmit: (value: string) => void;
}

export default function InlineToolbarLinkInput(props: IProps) {
    const classesRichEditor = richEditorClasses();
    const classesInsertLink = insertLinkClasses();
    const classesDropDown = dropDownClasses();

    function handleFormSubmit(event: FormEvent) {
        event.preventDefault();
        const value = props.inputRef.current?.value;
        if (value) {
            props.onSubmit(value);
        }
    }

    return (
        <div
            className={classNames(classesInsertLink.root, classesDropDown.likeDropDownContent)}
            role="dialog"
            aria-label={t("Insert Url")}
        >
            <form onSubmit={handleFormSubmit}>
                <input
                    value={props.inputValue}
                    onChange={props.onInputChange}
                    ref={props.inputRef}
                    onKeyDown={(event) => {
                        if (Keyboard.match(event.nativeEvent, "enter")) {
                            handleFormSubmit(event);
                        } else {
                            props.onInputKeyDown?.(event);
                        }
                    }}
                    className={classNames(classesInsertLink.input)}
                    placeholder={t("Paste or type url")}
                />
                <button type="submit" style={{ visibility: "hidden" }} />
                <CloseButton className={classesRichEditor.close} onClick={props.onCloseClick} />
            </form>
        </div>
    );
}
