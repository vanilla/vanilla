/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { inputBlockClasses } from "@library/forms/InputBlockStyles";
import classNames from "classnames";

interface IProps {
    visible: boolean;
}

export default class Or extends React.PureComponent<IProps, {}> {
    public static defaultProps: IProps = {
        visible: true,
    };

    public render() {
        const classes = inputBlockClasses();
        return this.props.visible ? (
            <div className={classNames(classes.labelText, "authenticateUser-divider")}>{t("or")}</div>
        ) : null;
    }
}
