/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";

interface IProps {
    visible: boolean;
}

export default class Or extends React.PureComponent<IProps, {}> {
    public static defaultProps: IProps = {
        visible: true,
    };

    public render() {
        return this.props.visible ? (
            <div className="inputBlock-labelText authenticateUser-divider">{t("or")}</div>
        ) : null;
    }
}
