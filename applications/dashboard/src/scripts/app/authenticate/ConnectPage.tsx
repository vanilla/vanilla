/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import { t } from "@dashboard/application";
import { log, logError } from "@dashboard/utility";
import DocumentTitle from "@dashboard/components/DocumentTitle";
import apiv2 from "@dashboard/apiv2";
import LinkUserFail from "@dashboard/app/authenticate/components/LinkUserFail";
import { IRequiredComponentID, uniqueIDFromPrefix } from "@dashboard/componentIDs";
import classNames from "classnames";
import { getMeta } from "@dashboard/application";
import gdn from "@dashboard/gdn";
import SsoUser from "@dashboard/app/authenticate/components/ssoUser";
import { authenticatorsSet } from "@dashboard/app/state/actions/authenticateActions";

interface IProps {}

interface IState {
    step: string;
    linkUser: any;
}

export default class ConnectPage extends React.Component<IProps, IState> {
    public static defaultProps = {};

    constructor(props) {
        super(props);
        const metaState = gdn.getMeta("state") || {};
        const authenticate = metaState.authenticate || {};

        this.state = {
            ...authenticate,
            step: authenticate.step || "fail",
        };
    }

    public render() {
        const stepClasses = classNames("authenticateConnect", "authenticateConnect-" + this.state.step);
        const linkUser = this.state.linkUser || {};

        let pageTitle;
        let content;

        switch (this.state.step) {
            case "linkUser":
                pageTitle = t("Your %s Account").replace("%s", linkUser.authenticator.name);
                content = <SsoUser ssoUser={linkUser.ssoUser} ui={linkUser.authenticator.ui} />;

                log(t("content: "), content);
                // content += <LinkUserRegister {...this.state.authenticate} />;
                break;
            //     case "password":
            //         content += ssoUser;
            //         content += <LinkUserPassword {...this.state.authenticate} />;
            //         break;
            //     case "error":
            //         // Handle errors
            //         content = "Do Error";
            //     // break;
            default:
                // Fail, unable to recover
                pageTitle = t("Error Signing In");
                content = <LinkUserFail />;
        }

        log(t("ConnectPage.state: "), this.state);

        return (
            <div className="authenticateConnect">
                <DocumentTitle title={pageTitle}>
                    <h1 className="isCentered">{pageTitle}</h1>
                </DocumentTitle>
                <div className="authenticateUserCol">
                    <div className={stepClasses}>{content}</div>
                </div>
            </div>
        );
    }
}
