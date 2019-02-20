/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import { style } from "typestyle";
import classNames from "classnames";
import { loaderClasses } from "@library/styles/loaderStyles";

interface IProps {
    minimumTime?: number;
    loaderStyleClass?: string;
    height?: number;
    width?: number;
    theme?: object;
}

interface IState {
    showLoader: boolean;
}

/**
 * A smart loading component. Takes up the full page and only displays in certain scenarios.
 */
export default class FullPageLoader extends React.Component<IProps, IState> {
    public state: IState = {
        showLoader: false,
    };

    public render() {
        if (this.props.minimumTime && this.props.minimumTime > 0 && !this.state.showLoader) {
            return null;
        }

        return (
            <React.Fragment>
                <div
                    className={this.props.loaderStyleClass || loaderClasses(this.props.theme).fullPageLoader}
                    aria-hidden="true"
                />
                <h1 className="sr-only">{t("Loading")}</h1>
            </React.Fragment>
        );
    }

    private timeout: NodeJS.Timeout;

    public componentDidMount() {
        const duration = this.props.minimumTime || 150;
        this.timeout = setTimeout(() => {
            this.setState({ showLoader: true });
        }, duration);
    }

    public componentWillUnmount() {
        this.timeout && clearTimeout(this.timeout);
    }
}
