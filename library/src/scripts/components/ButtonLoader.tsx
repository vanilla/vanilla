/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import { buttonLoaderClasses } from "@library/styles/buttonStyles";
import { ColorHelper } from "csx";
import { globalVariables } from "@library/styles/globalStyleVars";

interface IProps {
    spinnerColor?: ColorHelper;
}

/**
 * A smart loading component. Takes up the full page and only displays in certain scenarios.
 */
export default class ButtonLoader extends React.Component<IProps> {
    public render() {
        const globalVars = globalVariables();
        const classes = buttonLoaderClasses(
            this.props.spinnerColor ? this.props.spinnerColor : globalVars.mainColors.primary,
        );
        return (
            <React.Fragment>
                <div className={classes.root} aria-hidden="true" />
                <span className="sr-only">{t("Loading")}</span>
            </React.Fragment>
        );
    }
}
