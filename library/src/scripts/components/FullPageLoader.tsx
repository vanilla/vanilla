/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { LoadingComponentProps } from "react-loadable";

interface IProps extends Partial<LoadingComponentProps> {}

/**
 * A smart loading component. Takes up the full page and only displays in certain scenarias.
 */
export default class FullPageLoader extends React.Component<IProps> {
    public static defaultProps: IProps = {
        pastDelay: true,
    };

    public render() {
        const { pastDelay } = this.props;

        if (pastDelay) {
            return <div className="fullPageLoader" />;
        } else {
            return null;
        }
    }
}
