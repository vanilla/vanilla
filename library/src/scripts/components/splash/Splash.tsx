/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import splashStyles from "@library/styles/splashStyles";
import Heading from "@library/components/Heading";
import { ColorHelper } from "csx";
import { BackgroundImageProperty } from "csstype";
import Container from "@library/components/layouts/components/Container";
import { PanelWidget } from "@library/components/layouts/PanelLayout";
import { withDevice } from "@library/contexts/DeviceContext";
import { IDeviceProps } from "@library/components/DeviceChecker";
import Search from "@library/components/Search";

interface ISplashStyles {
    colors?: {
        fg?: ColorHelper;
        bg?: ColorHelper;
        primary?: ColorHelper;
    };
    backgroundImage?: BackgroundImageProperty;
    fullWidth?: boolean;
    transparentButton?: boolean;
}

interface IProps extends IDeviceProps {
    title: string; // Often the message to display isn't the real H1
    className?: string;
    styles?: ISplashStyles;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export class Splash extends React.Component<IProps> {
    public static defaultProps = {
        styles: {},
    };
    public render() {
        const classes = splashStyles();
        const { title, className, styles } = this.props;
        return (
            <div className={classNames("splash", className, classes.root)}>
                <Container className="splash-container">
                    <PanelWidget>{title && <Heading title={title} />}</PanelWidget>
                    <Search />
                </Container>
            </div>
        );
    }
}

export default withDevice(Splash);
