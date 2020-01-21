/**
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { PlusIcon } from "@library/icons/common";
import { currentThemeClasses } from "./currentThemeStyles";
import SmartLink from "@library/routing/links/SmartLink";
import Button from "@library/forms/Button";
import addThemeClasses from "./addThemeStyles";

interface IProps {
    onAdd?: React.ReactNode;
}

export default class AddTheme extends React.Component<IProps> {
    constructor(props) {
        super(props);
    }

    public render() {
        const classes = addThemeClasses();
        return (
            <Button className={classes.button}>
                <div className={classes.addTheme}>{this.props.onAdd}</div>
            </Button>
        );
    }
}
