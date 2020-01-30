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
import { useBannerContainerDivRef } from "@library/banner/BannerContext";
import { bannerClasses, bannerVariables } from "@library/banner/bannerStyles";
import { ColorValues } from "@library/styles/styleHelpersColors";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { titleBarClasses, titleBarVariables } from "@library/headers/titleBarStyles";

export interface IBannerStyleOverwrite {
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
    description?: React.ReactNode;
    className?: string;
    styleOverwrite?: IBannerStyleOverwrite;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export default function Banner(props: IProps) {
    const device = useDevice();
    const ref = useBannerContainerDivRef();

    const { action, className, title, description } = props;
    const styleOverwrite = props.styleOverwrite || {};

    const varsTitleBar = titleBarVariables();
    const classesTitleBar = titleBarClasses();
    const classes = bannerClasses(styleOverwrite);
    const vars = bannerVariables(styleOverwrite);

    const isImageBg = vars.options.imageType === "background";

    return (
        <div
            ref={ref}
            className={classNames(className, classes.root, {
                [classesTitleBar.negativeSpacer]: varsTitleBar.fullBleed.enabled,
            })}
        >
            <div
                className={classNames(
                    classes.outerBackground(
                        styleOverwrite.outerBackgroundImage ? styleOverwrite.outerBackgroundImage : undefined,
                    ),
                )}
            />
            {((styleOverwrite.backgrounds && styleOverwrite.backgrounds.useOverlay) || vars.backgrounds.useOverlay) &&
                isImageBg && <div className={classes.backgroundOverlay} />}
            <Container>
                <div className={classes.innerContainer}>
                    <PanelWidgetHorizontalPadding>
                        <div className={classes.titleWrap}>
                            <FlexSpacer className={classes.titleFlexSpacer} />
                            {title && <Heading title={title} className={classes.title} />}
                            <div className={classNames(classes.text, classes.titleFlexSpacer)}>{action}</div>
                        </div>
                        {description && (
                            <div className={classes.descriptionWrap}>
                                <p className={classes.description}>{description}</p>
                            </div>
                        )}
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
