/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Button from "@library/forms/Button";

interface IProps {
    id: string;
    ariaControls: string;
    ariaSelected: boolean;
    baseClass: ButtonTypes;
    className: string;
    tabIndex: number;
    children: React.ReactNode;
    setTab: (selectedTab: number) => void;
    index: number;
    onKeyDown?: (event: React.KeyboardEvent) => void;
}

/**
 * Implements TabButton component
 */
export default class TabButton extends React.Component<IProps> {
    public render() {
        const { id, ariaControls, ariaSelected, baseClass, className, tabIndex, children } = this.props;
        return (
            <Button
                id={id}
                aria-controls={ariaControls}
                aria-selected={ariaSelected}
                baseClass={baseClass}
                className={className}
                role="tab"
                tabIndex={tabIndex}
                onClick={this.handleClick}
                onKeyDown={this.props.onKeyDown}
            >
                {children}
            </Button>
        );
    }

    private handleClick = () => {
        this.props.setTab(this.props.index);
    };
}
