/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useMemo, useRef } from "react";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@vanilla/i18n";
import Heading from "@library/layout/Heading";
import { callToActionClasses } from "@library/widgets/CallToAction.styles";
import { callToActionVariables, ICallToActionOptions } from "@library/widgets/CallToAction.variables";
import { DeepPartial } from "redux";
import { useMeasure } from "@vanilla/react-utils/src";
import classNames from "classnames";
import { ButtonTypes } from "../forms/buttonTypes";
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { useSection } from "@library/layout/LayoutContext";
import { createSourceSetValue, ImageSourceSet } from "@library/utility/appUtils";
import { cx } from "@emotion/css";

interface ICTALink {
    to: string;
    textCTA: string;
    linkButtonType?: ButtonTypes;
}

interface IProps {
    to: string;
    shouldUseButton?: boolean;
    textCTA: string;
    title: string;
    otherCTAs?: ICTALink[];
    imageUrl?: string;
    description?: string | React.ReactNode;
    desktopOnly?: boolean;
    backgroundImage?: string;
    backgroundImageSrcSet?: ImageSourceSet;
    options?: DeepPartial<ICallToActionOptions>;
    className?: string;
    customCTA?: React.ReactNode;
}

export function CallToAction(props: IProps) {
    const imageItemRef = useRef<HTMLDivElement | null>(null);
    const imageItemMeasure = useMeasure(imageItemRef, { watchRef: true });
    const options = callToActionVariables.useAsHook(props.options).options;
    const ctaClasses = callToActionClasses.useAsHook(props.options);
    const shouldUseButton = props.shouldUseButton ?? true;

    let multipleLinks: any = undefined;
    if (props.otherCTAs) {
        let ctaLinks = [{ textCTA: props.textCTA, to: props.to }];
        props.otherCTAs.forEach((ctaLink) => {
            ctaLinks.push(ctaLink);
        });
        multipleLinks = ctaLinks.map((ctaLink: ICTALink, index) => {
            const linkButtonType = ctaLink.linkButtonType ?? options.linkButtonType;
            return (
                <div key={"cta-link-" + index} className={ctaClasses.link}>
                    <LinkAsButton
                        buttonType={linkButtonType}
                        to={ctaLink.to}
                        className={cx(ctaClasses.button, { [ctaClasses.compactButton]: options.compactButtons })}
                    >
                        {t(ctaLink.textCTA)}
                    </LinkAsButton>
                </div>
            );
        });
    }

    const isFullWidth = useSection().isFullWidth;
    const showContent = props.desktopOnly && !isFullWidth ? false : true;

    const backgroundFromOption = props.options?.box?.background;

    const backgroundUrlSrcSet = useMemo(() => {
        return backgroundFromOption?.imageSrcSet
            ? { srcSet: createSourceSetValue(backgroundFromOption.imageSrcSet) }
            : {};
    }, [backgroundFromOption]);

    const backgroundImageProps = {
        src: backgroundFromOption?.image,
        ...backgroundUrlSrcSet,
    };

    const isLink = !shouldUseButton && props.to !== "";
    const AnchorOrDiv = isLink ? "a" : "div";
    const anchorOrDivProps = shouldUseButton ? {} : { href: props.to };

    return (
        <>
            {showContent && (
                <AnchorOrDiv
                    {...anchorOrDivProps}
                    className={cx(ctaClasses.root, props.className, { [ctaClasses.containerLink]: !!isLink })}
                >
                    <div className={ctaClasses.container}>
                        {backgroundFromOption?.image && (
                            <img className={ctaClasses.image} {...backgroundImageProps} role="presentation" />
                        )}
                        {backgroundFromOption?.image && props.options?.useOverlay && (
                            <div className={cx(ctaClasses.absoluteFullParentSize, ctaClasses.backgroundOverlay)} />
                        )}
                        {props.imageUrl && (
                            <div
                                ref={imageItemRef}
                                className={classNames(
                                    ctaClasses.imageContainer,
                                    ctaClasses.imageWidthConstraint(imageItemMeasure.height),
                                )}
                            >
                                <div className={ctaClasses.imageContainerWrapper}>
                                    <img
                                        className={ctaClasses.image}
                                        src={props.imageUrl}
                                        alt={t(props.title)}
                                        loading="lazy"
                                    />
                                </div>
                            </div>
                        )}
                        <div className={ctaClasses.content}>
                            <Heading renderAsDepth={3} className={ctaClasses.title}>
                                {t(props.title)}
                            </Heading>
                            {props.description && (
                                <div
                                    className={cx(ctaClasses.description, {
                                        [ctaClasses.descriptionPadding]: shouldUseButton,
                                    })}
                                >
                                    {typeof props.description === "string" ? t(props.description) : props.description}
                                </div>
                            )}
                            {shouldUseButton &&
                                props.to &&
                                !multipleLinks &&
                                (props.customCTA ? (
                                    props.customCTA
                                ) : (
                                    <LinkAsButton
                                        buttonType={options.linkButtonType}
                                        to={props.to}
                                        className={ctaClasses.button}
                                    >
                                        {t(props.textCTA ?? "")}
                                    </LinkAsButton>
                                ))}
                            {shouldUseButton && multipleLinks && (
                                <div className={ctaClasses.linksWrapper}>{multipleLinks}</div>
                            )}
                        </div>
                    </div>
                </AnchorOrDiv>
            )}
        </>
    );
}
