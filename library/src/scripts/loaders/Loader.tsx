/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { styleUnit } from "@library/styles/styleUnit";
import { TLength } from "@library/styles/styleShim";
import { t } from "@library/utility/appUtils";
import { loaderClasses } from "@library/loaders/loaderStyles";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import { Property } from "csstype";
import ConditionalWrap from "@library/layout/ConditionalWrap";
import classNames from "classnames";
import { css } from "@emotion/css";

interface IProps {
    minimumTime?: number;
    loaderStyleClass?: string;
    size?: number;
    padding?: Property.Padding<TLength>;
    small?: boolean;
}

interface IState {
    showLoader: boolean;
}

/**
 * A smart loading component. Takes up the full page and only displays in certain scenarios.
 */
export default class Loader extends React.Component<IProps, IState> {
    public state: IState = {
        showLoader: false,
    };

    public render() {
        if (this.props.minimumTime && this.props.minimumTime > 0 && !this.state.showLoader) {
            return null;
        }
        const styleClass = this.props.small
            ? loaderClasses().smallLoader
            : this.props.loaderStyleClass || loaderClasses().fullPageLoader;
        return (
            <React.Fragment>
                <ConditionalWrap
                    condition={!!this.props.padding || !!this.props.size}
                    className={classNames(
                        this.props.padding && css({ padding: styleUnit(this.props.padding) }),
                        this.props.size && loaderClasses().loaderContainer(this.props.size),
                    )}
                >
                    <div className={styleClass} aria-hidden="true" />
                    <ScreenReaderContent>
                        <p>{t("Loading")}</p>
                    </ScreenReaderContent>
                </ConditionalWrap>
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
