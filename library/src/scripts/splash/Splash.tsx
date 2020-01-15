/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import IndependentSearch from "@library/features/search/IndependentSearch";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Container from "@library/layout/components/Container";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import FlexSpacer from "@library/layout/FlexSpacer";
import Heading from "@library/layout/Heading";
import { PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import { useSplashContainerDivRef } from "@library/splash/SplashContext";
import { splashClasses, splashVariables } from "@library/splash/splashStyles";
import { ColorValues } from "@library/styles/styleHelpersColors";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { titleBarClasses, titleBarVariables } from "@library/headers/titleBarStyles";

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

interface IProps {
    action?: React.ReactNode;
    title?: string; // Often the message to display isn't the real H1
    className?: string;
    styleOverwrite?: ISplashStyleOverwrite;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export default function Splash(props: IProps) {
    const device = useDevice();
    const ref = useSplashContainerDivRef();

    const { action, className, title } = props;
    const styleOverwrite = props.styleOverwrite || {};

    const varsTitleBar = titleBarVariables();
    const classesTitleBar = titleBarClasses();
    const classes = splashClasses(styleOverwrite);
    const vars = splashVariables(styleOverwrite);

    return (
        <div
            ref={ref}
            className={classNames(className, classes.root, {
                [classesTitleBar.negativeSpacer]: varsTitleBar.options.integrateWithSplash,
            })}
        >
            <div
                className={classNames(
                    classes.outerBackground(
                        styleOverwrite.outerBackgroundImage ? styleOverwrite.outerBackgroundImage : undefined,
                    ),
                )}
            />
            {((styleOverwrite.backgrounds && styleOverwrite.backgrounds.useOverlay) || vars.backgrounds.useOverlay) && (
                <div className={classes.backgroundOverlay} />
            )}
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
                                hideSearchButton={device === Devices.MOBILE || device === Devices.XS}
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
