/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";

/**
 * A smart loading component. Takes up the full page and only displays in certain scenarias.
 */
export default class FullPageLoader extends React.Component {
    public render() {
        return <div className="fullPageLoader" tabIndex={0} aria-label={t("Your requested page is loading")} />;
    }
}
