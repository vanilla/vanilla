/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/utility/appUtils";
import React from "react";
import Paragraph from "@library/layout/Paragraph";
import { IUserAuthenticator } from "@dashboard/@types/api/authenticate";

interface IProps {
    ssoMethods?: IUserAuthenticator[];
}

export default class SSOMethods extends React.Component<IProps> {
    public render() {
        if (!this.props.ssoMethods || this.props.ssoMethods.length === 0) {
            return null;
        }

        const ssoMethods = this.props.ssoMethods.map((method, index) => {
            const methodStyles = {
                backgroundColor: method.ui.backgroundColor || undefined,
                color: method.ui.foregroundColor || undefined,
            };
            return (
                <a
                    href={method.ui.url}
                    key={index}
                    className="BigButton button Button button-sso button-fullWidth"
                    style={methodStyles}
                >
                    <span className="button-ssoContents">
                        <img src={method.ui.photoUrl || undefined} className="ssoMethod-icon" aria-hidden={true} />
                        <span className="button-ssoLabel">{t(method.ui.buttonName)}</span>
                        <span className="ssoMethod-icon ssoMethod-iconSpacer" aria-hidden="true" />
                    </span>
                </a>
            );
        });

        return (
            <div className="ssoMethods">
                <Paragraph>{t("Sign in with one of the following:")}</Paragraph>
                {ssoMethods}
            </div>
        );
    }
}
