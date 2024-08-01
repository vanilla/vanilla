/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { useRef, useEffect } from "react";
import { t } from "@vanilla/i18n";
import { delegateEvent, removeDelegatedEvent } from "@vanilla/dom-utils";

export default function BlurContainer(props: { children: React.ReactNode }) {
    const buttonLabelShow = t("Show");
    const buttonLabelHide = t("Hide");

    const containerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const outerContainers = containerRef.current?.querySelectorAll(".moderationImageAndButtonContainer");

        outerContainers?.forEach((container) => {
            if (container.querySelector("button") === null) {
                const toggleButton = document.createElement("button");
                toggleButton.classList.add("toggleButton");
                toggleButton.textContent = buttonLabelShow;
                container.insertBefore(toggleButton, container.firstChild);
            }
        });
    }, [buttonLabelShow]);

    useEffect(() => {
        const eventHash = delegateEvent("click", ".toggleButton", (event) => {
            const target = event.target as HTMLButtonElement;
            const parentNode = target.parentNode;

            const postImages = parentNode && parentNode.querySelectorAll(".moderationContainer");
            postImages?.forEach((image) => {
                if (image.classList.contains("blur")) {
                    target.textContent = buttonLabelHide;
                } else {
                    target.textContent = buttonLabelShow;
                }
                image.classList.toggle("blur");
            });
        });

        return () => {
            removeDelegatedEvent(eventHash);
        };
    }, [buttonLabelHide, buttonLabelShow]);

    return (
        <div ref={containerRef} className="reportListUserContent">
            {props.children}
        </div>
    );
}
