/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import IndependentSearch from "@library/features/search/IndependentSearch";
import { buttonClasses, ButtonTypes } from "@library/forms/buttonStyles";
import Container from "@library/layout/components/Container";
import { Devices, IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import FlexSpacer from "@library/layout/FlexSpacer";
import Heading from "@library/layout/Heading";
import { PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import { splashClasses, splashVariables } from "@library/splash/splashStyles";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { ColorValues } from "@library/styles/styleHelpersColors";
import { url } from "csx";

export interface ISplashStyleOverwrite {
    colors?: {
        bg?: ColorValues;
        fg?: ColorValues;
        borderColor?: ColorValues;
    };
    backgrounds?: {
        useOverlay?: boolean;
    };
    outerBackgroundImage?: string;
}

interface IProps extends IDeviceProps {
    action?: React.ReactNode;
    title?: string; // Often the message to display isn't the real H1
    className?: string;
    outerBackgroundImage?: string;
    styleOverwrite?: ISplashStyleOverwrite;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export class Splash extends React.Component<IProps> {
    public render() {
        const { action, className } = this.props;
        const styleOverwrite = this.props.styleOverwrite ? this.props.styleOverwrite : ({} as ISplashStyleOverwrite);

        // window.console.log("styleOverwrite: ", styleOverwrite);

        const classes = splashClasses(styleOverwrite);
        const title = this.props.title;
        const vars = splashVariables({});

        return (
            <div className={classNames(className, classes.root)}>
                <div
                    className={classNames(
                        classes.outerBackground(
                            styleOverwrite.outerBackgroundImage ? styleOverwrite.outerBackgroundImage : undefined,
                        ),
                    )}
                />
                {((styleOverwrite.backgrounds && styleOverwrite.backgrounds.useOverlay) ||
                    vars.backgrounds.useOverlay) && <div className={classes.backgroundOverlay} />}
                <Container>
                    <div className={classes.innerContainer}>
                        <PanelWidgetHorizontalPadding>
                            <div className={classes.titleWrap}>
                                <FlexSpacer className={classes.titleFlexSpacer} />
                                {title && <Heading title={title} className={classes.title} />}
                                <div className={classNames(classes.text, classes.titleFlexSpacer)}>{action}</div>
                            </div>
                            <div className={classes.searchContainer}>
                                <IndependentSearch
                                    buttonClass={classes.searchButton}
                                    buttonBaseClass={ButtonTypes.CUSTOM}
                                    isLarge={true}
                                    placeholder={t("Search")}
                                    inputClass={classes.input}
                                    iconClass={classes.icon}
                                    buttonLoaderClassName={classes.buttonLoader}
                                    hideSearchButton={
                                        this.props.device === Devices.MOBILE || this.props.device === Devices.XS
                                    }
                                    contentClass={classes.content}
                                    valueContainerClasses={classes.valueContainer}
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
