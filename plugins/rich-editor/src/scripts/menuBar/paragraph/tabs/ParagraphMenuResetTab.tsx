/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { paragraphMenuTabsClasses } from "@rich-editor/menuBar/paragraph/paragraphMenuBarStyles";
import { pilcrow } from "@library/icons/editorIcons";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";

interface IProps {
    formatParagraphHandler: () => void;
    className?: string;
    isActive: boolean;
    isDisabled?: boolean;
    tabIndex: 0 | -1;
    setRovingIndex: (callback?: () => void) => void;
    closeMenuAndSetCursor: () => void;
}

/**
 * Resets paragraph style to normal paragraph
 */
export default class ParagraphMenuResetTab extends React.PureComponent<IProps> {
    private buttonRef: React.RefObject<HTMLButtonElement> = React.createRef();

    public render() {
        const title = t("Set style to plain paragraph");
        const classes = paragraphMenuTabsClasses();
        const handleClick = (event: React.MouseEvent) => {
            this.props.setRovingIndex(() => {
                this.props.formatParagraphHandler();
                this.props.closeMenuAndSetCursor();
            });
        };
        return (
            <button
                type="button"
                disabled={this.props.isDisabled}
                title={title}
                aria-label={title}
                onClick={handleClick}
                className={classNames(
                    this.props.className,
                    classes.tabHandle,
                    this.props.isActive ? classes.activeTabHandle : "",
                )}
                ref={this.buttonRef}
            >
                {pilcrow()}
            </button>
        );
    }
}
