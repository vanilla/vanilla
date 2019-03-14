/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import Heading from "../layout/Heading";
import Container from "../layout/components/Container";
import { PanelWidget, PanelWidgetHorizontalPadding } from "../layout/PanelLayout";
import { withDevice } from "../layout/DeviceContext";
import { IDeviceProps, Devices } from "../layout/DeviceChecker";
import IndependentSearch from "../features/search/IndependentSearch";
import { splashStyles, splashVariables } from "splashStyles";
import { buttonClasses, ButtonTypes } from "@library/styles/buttonStyles";
import { t } from "../dom/appUtils";

interface IProps extends IDeviceProps {
    title: string; // Often the message to display isn't the real H1
    className?: string;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export class Splash extends React.Component<IProps> {
    public render() {
        const classes = splashStyles();
        const buttons = buttonClasses();
        const { title, className } = this.props;
        return (
            <div className={classNames(className, classes.root)}>
                <div className={classes.outerBackground} />
                <Container>
                    <div className={classes.innerContainer}>
                        <PanelWidgetHorizontalPadding>
                            {title && <Heading title={title} className={classes.title} />}
                            <div className={classes.searchContainer}>
                                <IndependentSearch
                                    className={classes.searchContainer}
                                    buttonClass={classes.searchButton}
                                    buttonBaseClass={ButtonTypes.TRANSPARENT}
                                    isLarge={true}
                                    placeholder={t("Search Articles")}
                                    inputClass={classes.input}
                                    iconClass={classes.icon}
                                    buttonLoaderClassName={classes.buttonLoader}
                                    hideSearchButton={this.props.device === Devices.MOBILE}
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
