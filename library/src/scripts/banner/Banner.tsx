/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import IndependentSearch from "@library/features/search/IndependentSearch";
import { ButtonPresets, ButtonTypes } from "@library/forms/buttonStyles";
import Container from "@library/layout/components/Container";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import FlexSpacer from "@library/layout/FlexSpacer";
import Heading from "@library/layout/Heading";
import { useBannerContainerDivRef } from "@library/banner/BannerContext";
import { bannerClasses, bannerVariables, SearchBarPresets, presetsBanner } from "@library/banner/bannerStyles";
import { t, assetUrl } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { titleBarClasses, titleBarVariables } from "@library/headers/titleBarStyles";
import { DefaultBannerBg } from "@library/banner/DefaultBannerBg";

interface IProps {
    action?: React.ReactNode;
    title?: string; // Often the message to display isn't the real H1
    description?: React.ReactNode;
    className?: string;
    backgroundImage?: string;
    contentImage?: string;
}

/**
 * A component representing a single crumb in a breadcrumb component.
 */
export default function Banner(props: IProps) {
    const device = useDevice();
    const ref = useBannerContainerDivRef();

    const { action, className, title } = props;

    const varsTitleBar = titleBarVariables();
    const classesTitleBar = titleBarClasses();
    const classes = bannerClasses();
    const vars = bannerVariables();
    const { options } = vars;

    let imageElementSrc = props.contentImage || vars.imageElement.image || null;
    imageElementSrc = imageElementSrc ? assetUrl(imageElementSrc) : null;
    const description = props.description ?? vars.description.text;

    return (
        <div
            ref={ref}
            className={classNames(className, classes.root, {
                [classesTitleBar.negativeSpacer]: varsTitleBar.fullBleed.enabled,
            })}
        >
            <div className={classNames(classes.outerBackground(props.backgroundImage ?? undefined))}>
                {!props.backgroundImage && !vars.outerBackground.image && <DefaultBannerBg />}
            </div>
            {vars.backgrounds.useOverlay && <div className={classes.backgroundOverlay} />}
            <Container fullGutter>
                <div className={imageElementSrc ? classes.imagePositioner : ""}>
                    <div className={classes.contentContainer}>
                        <div className={classes.titleWrap}>
                            <FlexSpacer className={classes.titleFlexSpacer} />
                            {title && (
                                <Heading className={classes.title} depth={1} isLarge>
                                    {title}
                                </Heading>
                            )}
                            <div className={classNames(classes.text, classes.titleFlexSpacer)}>{action}</div>
                        </div>
                        {!options.hideDesciption && description && (
                            <div className={classes.descriptionWrap}>
                                <p className={classNames(classes.description, classes.text)}>{description}</p>
                            </div>
                        )}
                        {!options.hideSearch && (
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
                                        device === Devices.MOBILE ||
                                        device === Devices.XS ||
                                        presetsBanner().button.preset === ButtonPresets.HIDE
                                    }
                                    contentClass={classes.content}
                                    valueContainerClasses={classes.valueContainer}
                                    iconContainerClasses={classes.iconContainer}
                                    resultsAsModalClasses={classes.resultsAsModal}
                                />
                            </div>
                        )}
                    </div>
                    {imageElementSrc && (
                        <div className={classes.imageElementContainer}>
                            <img className={classes.imageElement} src={imageElementSrc}></img>
                        </div>
                    )}
                </div>
            </Container>
        </div>
    );
}
