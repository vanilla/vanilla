/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { drawerClasses } from "@library/layout/drawer/drawerStyles";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import classNames from "classnames";

export interface IDrawerProps {
    title: string;
    children: React.ReactNode;
    className?: string;
    disabled?: boolean;
    contentsClassName?: string;
}

export interface IState {
    open: boolean;
}

/**
 * Creates a drop down menu
 */
export default class Drawer extends React.Component<IDrawerProps, IState> {
    private id = uniqueIDFromPrefix("drawer");
    public constructor(props) {
        super(props);
        this.state = {
            open: false,
        };
    }

    public render() {
        const chevronRight = `▸`;
        const chevronDown = `▾`;
        const classes = drawerClasses();
        return (
            <div className={classNames("drawer", this.props.className, classes.root)}>
                <Button
                    id={this.buttonID}
                    aria-controls={this.contentID}
                    aria-expanded={this.state.open}
                    disabled={this.props.disabled}
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames("drawer-toggle", classes.toggle)}
                    onClick={this.toggle}
                >
                    <span aria-hidden={true} className={classNames("drawer-icon", "icon-fake", classes.icon)}>
                        {this.state.open && chevronDown}
                        {!this.state.open && chevronRight}
                    </span>
                    {this.props.title}
                </Button>
                {this.state.open && (
                    <div
                        id={this.contentID}
                        aria-controlledby={this.buttonID}
                        className={classNames("drawer-contents", this.props.contentsClassName, classes.contents)}
                    >
                        {this.props.children}
                    </div>
                )}
            </div>
        );
    }

    private toggle = () => {
        this.setState({
            open: !this.state.open,
        });
    };

    private get buttonID() {
        return this.id + "-button";
    }
    private get contentID() {
        return this.id + "-content";
    }
}
