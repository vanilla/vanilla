/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import { buttonClasses, buttonLoaderClasses, buttonVariables, IButtonType } from "@library/styles/buttonStyles";
import classNames from "classnames";

interface IProps {
    className?: string;
    buttonType?: IButtonType;
}

/**
 * A smart loading component. Takes up the full page and only displays in certain scenarios.
 */
export default class ButtonLoader extends React.Component<IProps> {
    public render() {
        const classes = buttonLoaderClasses(this.props.buttonType || buttonVariables().primary);
        return (
            <React.Fragment>
                <div className={classNames(classes.root, this.props.className)} aria-hidden="true" />
                <span className="sr-only">{t("Loading")}</span>
            </React.Fragment>
        );
    }
}
