/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { t } from "@dashboard/application";

interface IProps {
    visible: boolean;
}

export default class Or extends React.PureComponent<IProps, {}> {
    public static defaultProps: IProps = {
        visible: true,
    };

    public render(): JSX.Element {
        return this.props.visible ? <div className="inputBlock-labelText authenticateUser-divider">{t("or")}</div> : null;
    }
}
