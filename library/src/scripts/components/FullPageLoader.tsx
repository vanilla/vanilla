/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import { style } from "typestyle";
import classNames from "classnames";

export enum LoaderStyle {
    FULL = "fullPageLoader",
    MEDIUM = "mediumLoader",
    FIXED_SIZE = "fixedSizeLoader",
}

interface IProps {
    minimumTime?: number;
    loaderStyle?: LoaderStyle;
    height?: number;
    width?: number;
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

        const sizeClass = this.props.loaderStyle || LoaderStyle.FULL;
        const styleClass = style({
            height: this.props.height,
            width: this.props.width,
        });

        return (
            <React.Fragment>
                <div className={classNames(sizeClass, styleClass)} aria-hidden="true" />
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
