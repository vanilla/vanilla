/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { sprintf } from "sprintf-js";
import { t } from "@library/application";
import DocumentTitle from "@dashboard/components/DocumentTitle";

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
