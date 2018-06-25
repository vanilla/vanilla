/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { t } from "@dashboard/application";
import React from "react";
import * as icons from "./Icons";
import { log } from "@dashboard/utility";

interface IProps {
    ssoUser: any;
    ui: any;
}

export default class SsoUser extends React.Component<IProps, {}> {
    public render() {
        const userInfo: string[] = [];
        if (this.props.ssoUser.fullName) {
            userInfo.push(this.props.ssoUser.fullName as string);
        }
        if (this.props.ssoUser.name) {
            userInfo.push(this.props.ssoUser.name as string);
        }
        if (this.props.ssoUser.defaultName) {
            userInfo.push(this.props.ssoUser.defaultName as string);
        }

        const nameFirstLine = <div className="ssoUser-namePrimary">{userInfo.pop()}</div>;
        let nameSecondLine: any = null;
        if (userInfo.length > 0) {
            nameSecondLine = <div className="ssoUser-nameSecondary">{userInfo.pop()}</div>;
        }

        return (
            <div className="ssoUser">
                <div className="ssoUser-info">
                    <div className="Photo PhotoWrap PhotoWrapLarge thumbnail-shadow ssoUser-imageWrap">
                        <img
                            aria-hidden="true"
                            src={this.props.ssoUser.photoUrl}
                            className="ssoUser-image ProfilePhotoLarge"
                            alt={t("User: ") + nameFirstLine}
                        />
                    </div>
                    <div
                        className="ssoUser-provider"
                        style={{
                            backgroundColor: this.props.ui.backgroundColor,
                            borderColor: this.props.ui.foregroundColor,
                        }}
                    >
                        <img src={this.props.ui.photoUrl} className="ssoUser-icon" aria-hidden={true} />
                    </div>
                </div>
                {nameFirstLine}
                {nameSecondLine}
                {icons.arrowDown()}
            </div>
        );
    }
}
