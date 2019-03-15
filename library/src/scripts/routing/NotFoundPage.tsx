/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import DocumentTitle from "@library/routing/DocumentTitle";
import { sprintf } from "sprintf-js";

export default class NotFoundPage extends React.PureComponent<IProps> {
    public static defaultProps = {
        type: "Page",
    };

    public render() {
        return (
            <div className="Center SplashInfo">
                <DocumentTitle title={this.title} />
                <div>{this.message}</div>
            </div>
        );
    }

    private get title() {
        return this.props.title || sprintf(t("%s Not Found"), t(this.props.type));
    }

    private get message() {
        return (
            this.props.message ||
            sprintf(t("The %s you were looking for could not be found."), t(this.props.type.toLowerCase()))
        );
    }
}

interface IProps {
    type: string;
    title?: string;
    message?: string;
}
