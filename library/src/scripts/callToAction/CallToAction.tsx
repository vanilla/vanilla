/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef } from "react";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@vanilla/i18n";
import Heading from "@library/layout/Heading";
import { callToActionClasses } from "@library/callToAction/CallToAction.styles";
import { callToActionVariables, ICallToActionOptions } from "@library/callToAction/CallToAction.variables";
import { DeepPartial } from "redux";
import { useMeasure } from "@vanilla/react-utils/src";
import classNames from "classnames";
import { ButtonTypes } from "../forms/buttonTypes";

interface ICTALink {
    to: string;
    textCTA: string;
    linkButtonType?: ButtonTypes;
}

interface IProps {
    to: string;
    textCTA: string;
    title: string;
    otherCTAs?: ICTALink[];
    imageUrl?: string;
    description?: string;
    options?: DeepPartial<ICallToActionOptions>;
}

export function CallToAction(props: IProps) {
    const imageItemRef = useRef<HTMLDivElement | null>(null);
    const imageItemMeasure = useMeasure(imageItemRef, false, true);
    const options = callToActionVariables(props.options).options;
    const ctaClasses = callToActionClasses(props.options);
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
                    <LinkAsButton buttonType={linkButtonType} to={ctaLink.to}>
                        {t(ctaLink.textCTA)}
                    </LinkAsButton>
                </div>
            );
        });
    }

    return (
        <div className={ctaClasses.root}>
            <div className={ctaClasses.container}>
                {props.imageUrl && (
                    <div
                        ref={imageItemRef}
                        className={classNames(
                            ctaClasses.imageContainer,
                            ctaClasses.imageWidthConstraint(imageItemMeasure.height),
                        )}
                    >
                        <div className={ctaClasses.imageContainerWrapper}>
                            <img className={ctaClasses.image} src={props.imageUrl} alt={props.title} loading="lazy" />
                        </div>
                    </div>
                )}
                <div className={ctaClasses.content}>
                    <Heading renderAsDepth={3} className={ctaClasses.title}>
                        {props.title}
                    </Heading>

                    {props.description && <div className={ctaClasses.description}>{props.description}</div>}
                    {!multipleLinks && (
                        <LinkAsButton buttonType={options.linkButtonType} to={props.to}>
                            {t(props.textCTA)}
                        </LinkAsButton>
                    )}
                    {multipleLinks && <div className={ctaClasses.linksWrapper}>{multipleLinks}</div>}
                </div>
            </div>
        </div>
    );
}
