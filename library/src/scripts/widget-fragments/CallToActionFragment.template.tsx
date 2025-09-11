import CallToAction from "@vanilla/injectables/CallToActionFragment";
import Components from "@vanilla/injectables/Components";
import Utils from "@vanilla/injectables/Utils";
import React from "react";

export default function CallToActionFragment(props: CallToAction.Props) {
    const { title, description, alignment, textColor, button, secondButton, background } = props;
    const { color: bgColor, image: bgImage, imageUrlSrcSet: bgImageSrcSet } = background || {};
    const hasActions = button || secondButton;
    return (
        <Components.LayoutWidget
            className={`callToActionFragment__root ${bgColor ? "hasBackgroundColor" : ""}`}
            style={{ "--background-color": bgColor, "--alignment": alignment } as React.CSSProperties}
        >
            <div className={"callToActionFragment__container"}>
                {(bgImage || bgImageSrcSet) && (
                    <div className={"callToActionFragment_image"}>
                        <img
                            src={bgImage}
                            alt={title}
                            {...(bgImageSrcSet && { srcSet: Utils.createSourceSetValue(bgImageSrcSet) })}
                        />
                    </div>
                )}
                <div className={"callToActionFragment_copy"} style={{ "--color": textColor } as React.CSSProperties}>
                    <h2>{title}</h2>
                    <p>{description}</p>
                </div>
                {hasActions && (
                    <div className={"callToActionFragment_actions"}>
                        {button && button?.url && (
                            <Components.LinkButton buttonType={button?.type ?? "primary"} to={button.url}>
                                {button.title}
                            </Components.LinkButton>
                        )}
                        {secondButton && secondButton?.url && (
                            <Components.LinkButton buttonType={secondButton?.type ?? "standard"} to={secondButton.url}>
                                {secondButton.title}
                            </Components.LinkButton>
                        )}
                    </div>
                )}
            </div>
        </Components.LayoutWidget>
    );
}
