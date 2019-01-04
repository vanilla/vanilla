/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";

/**
 * A smart loading component. Takes up the full page and only displays in certain scenarios.
 */
export default class ButtonLoader extends React.Component {
    public render() {
        return (
            <React.Fragment>
                <div className="buttonLoader" aria-hidden="true" />
                <h1 className="sr-only">{t("Loading")}</h1>
            </React.Fragment>
        );
    }
}
