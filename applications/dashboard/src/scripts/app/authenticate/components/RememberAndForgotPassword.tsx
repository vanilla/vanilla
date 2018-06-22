/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { t } from "@dashboard/application";
import React from "react";
import { Link } from "react-router-dom";
import Checkbox from "@dashboard/components/forms/Checkbox";
import get from "lodash/get";

interface IProps {
    editable?: boolean;
    rememberMe?: boolean;
    onChange: any;
}

export default class RememberAndForgotPassword extends React.Component<IProps> {
    constructor(props) {
        super(props);
        this.handleCheckBoxChange = this.handleCheckBoxChange.bind(this);
    }

    public handleCheckBoxChange = event => {
        const value: boolean = get(event, "target.checked", false);
        this.props.onChange(value);
    };

    public render() {
        return (
            <div className="inputBlock inputBlock-tighter">
                <div className="rememberMeAndForgot">
                    <div className="rememberMeAndForgot-rememberMe">
                        <Checkbox
                            label={t("Keep me signed in")}
                            onChange={this.handleCheckBoxChange}
                            checked={this.props.rememberMe}
                        />
                    </div>
                    <div className="rememberMeAndForgot-forgot">
                        <Link to="/authenticate/recoverpassword">{t("Forgot your password?")}</Link>
                    </div>
                </div>
            </div>
        );
    }
}
