/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { buttonLoaderClasses, ButtonTypes } from "@library/forms/buttonTypes";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";

interface IProps {
    className?: string;
    buttonType?: ButtonTypes;
}

/**
 * A smart loading component. Takes up the full page and only displays in certain scenarios.
 */
export default class ButtonLoader extends React.Component<IProps> {
    public render() {
        const classes = buttonLoaderClasses(this.props.buttonType ? this.props.buttonType : ButtonTypes.STANDARD);
        return (
            <React.Fragment>
                <div className={classNames(classes.root, this.props.className)} aria-hidden="true" />
                <ScreenReaderContent>{t("Loading")}</ScreenReaderContent>
            </React.Fragment>
        );
    }
}
