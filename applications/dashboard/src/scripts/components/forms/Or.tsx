/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { t } from "@dashboard/application";

export default class Or extends React.Component {
    public render() {
        return <div className="inputBlock-labelText authenticateUser-divider">{t("or")}</div>;
    }
}
