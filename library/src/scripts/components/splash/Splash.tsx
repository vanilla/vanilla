/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import Heading from "@library/components/Heading";
import Container from "@library/components/layouts/components/Container";
import { PanelWidget, PanelWidgetHorizontalPadding } from "@library/components/layouts/PanelLayout";
import { withDevice } from "@library/contexts/DeviceContext";
import { IDeviceProps } from "@library/components/DeviceChecker";
import IndependentSearch from "@library/components/IndependentSearch";
import { splashStyles, splashVariables } from "@library/styles/splashStyles";
import { buttonClasses } from "@library/styles/buttonStyles";

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
        const buttons = buttonClasses();
        const { title, className } = this.props;
        return (
            <div className={classNames(className, classes.root)}>
                <div className={classes.fullBackground} />
                <Container className={classes.container}>
                    <div className={classes.innerContainer}>
                        <PanelWidgetHorizontalPadding>
                            {title && <Heading title={title} className={classes.title} />}
                            <div className={classes.searchContainer}>
                                <IndependentSearch
                                    className={classes.search}
                                    buttonClass={buttons.transparent}
                                    theme={vars.search}
                                    isLarge={true}
                                />
                            </div>
                        </PanelWidgetHorizontalPadding>
                    </div>
                </Container>
            </div>
        );
    }
}

export default withDevice(Splash);
