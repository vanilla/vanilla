/**
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { PlusIcon } from "@library/icons/common";
import { currentThemeClasses } from "./currentThemeStyles";
import SmartLink from "@library/routing/links/SmartLink";

interface IProps {
    to: string;
}

export default class AddTheme extends React.Component<IProps> {
    constructor(props) {
        super(props);
    }

    public render() {
        const classes = currentThemeClasses();
        return (
            <React.Fragment>
                <SmartLink to={this.props.to}>
                    <div className={classes.addTheme}>
                        <PlusIcon />
                    </div>
                </SmartLink>
            </React.Fragment>
        );
    }
}
