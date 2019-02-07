/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import Heading from "@library/components/Heading";
import { ColorHelper } from "csx";
import { BackgroundImageProperty } from "csstype";
import Container from "@library/components/layouts/components/Container";
import { PanelWidget, PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";
import { withDevice } from "@library/contexts/DeviceContext";
import { IDeviceProps } from "@library/components/DeviceChecker";
import Search from "@library/components/Search";
import { splashStyles, splashVariables } from "@library/styles/splashStyles";

interface IProps extends IDeviceProps {
    title: string; // Often the message to display isn't the real H1
    className?: string;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export class Splash extends React.Component<IProps> {
    public render() {
        const vars = splashVariables();
        const classes = splashStyles();
        const { title, className } = this.props;
        return (
            <div className={classNames(className, classes.root)}>
                <div className={classes.fullBackground} />
                <Container className={classes.container}>
                    <div className={classes.innerContainer}>
                        <PanelWidgetHorizontalPadding>
                            <PanelWidget>{title && <Heading title={title} className={classes.title} />}</PanelWidget>
                            <Search className={classes.search} theme={vars.search} />
                        </PanelWidgetHorizontalPadding>
                    </div>
                </Container>
            </div>
        );
    }
}

export default withDevice(Splash);
