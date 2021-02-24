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

interface IProps {
    to: string;
    textCTA: string;
    title: string;
    imageUrl?: string;
    description?: string;
    options?: DeepPartial<ICallToActionOptions>;
}

export function CallToAction(props: IProps) {
    const imageItemRef = useRef<HTMLDivElement | null>(null);
    const imageItemMeasure = useMeasure(imageItemRef, false, true);
    const options = callToActionVariables(props.options).options;
    const ctaClasses = callToActionClasses(props.options);
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
                            <img className={ctaClasses.image} src={props.imageUrl} alt={props.title} />
                        </div>
                    </div>
                )}
                <div className={ctaClasses.content}>
                    <Heading renderAsDepth={3} className={ctaClasses.title}>
                        {props.title}
                    </Heading>

                    {props.description && <div className={ctaClasses.description}>{props.description}</div>}
                    <LinkAsButton baseClass={options.linkButtonType} to={props.to}>
                        {t(props.textCTA)}
                    </LinkAsButton>
                </div>
            </div>
        </div>
    );
}
