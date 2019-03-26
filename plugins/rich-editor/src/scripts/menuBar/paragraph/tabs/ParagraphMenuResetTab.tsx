/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { pilcrow } from "@library/icons/editorIcons";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import { richEditorClasses } from "@rich-editor/editor/richEditorClasses";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";

interface IProps {
    formatParagraphHandler: () => void;
    className?: string;
    isActive: boolean;
    isDisabled?: boolean;
    tabIndex: 0 | -1;
    setRovingIndex: (callback?: () => void) => void;
    closeMenuAndSetCursor: () => void;
    legacyMode?: boolean;
}

/**
 * Resets paragraph style to normal paragraph
 */
export default class ParagraphMenuResetTab extends React.PureComponent<IProps> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public render() {
        const title = t("Removes paragraph style and sets to plain paragraph");
        const classes = richEditorClasses(!!this.props.legacyMode);
        const handleClick = (event: React.MouseEvent) => {
            this.props.setRovingIndex();
            this.props.formatParagraphHandler();
            this.props.closeMenuAndSetCursor();
        };
        return (
            <button
                type="button"
                disabled={this.props.isDisabled}
                title={title}
                aria-label={title}
                onClick={handleClick}
                className={classNames(this.props.className, classes.button)}
                ref={this.buttonRef}
                tabIndex={this.props.tabIndex}
            >
                <ScreenReaderContent>{t("Paragraph")}</ScreenReaderContent>
                <span aria-hidden={true}>{pilcrow()}</span>
            </button>
        );
    }

    public componentDidMount() {
        if (this.props.tabIndex === 0) {
            this.buttonRef.current && this.buttonRef.current.focus();
        }
    }

    public componentDidUpdate() {
        if (this.props.tabIndex === 0) {
            this.buttonRef.current && this.buttonRef.current.focus();
        }
    }
}
