/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEffect, useRef, useState } from "react";
import { VanillaSwaggerPlugin } from "@library/features/swagger/VanillaSwaggerPlugin";
import { DEEP_LINK_ATTR } from "@library/features/swagger/VanillaSwaggerDeepLink";
import { replaceDeepLinkScrolling } from "@library/features/swagger/replaceDeepLinkScrolling";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";

export interface ISwaggerHeading {
    text: string;
    ref: string;
    level: number;
}

async function importSwagger() {
    const imported = await import(/* webpackChunkName: "swagger-ui-react" */ "@library/features/swagger/SwaggerUI");
    return imported.SwaggerUI;
}

function notEmpty<TValue>(value: TValue | null | undefined): value is TValue {
    return value !== null && value !== undefined;
}

export function useSwaggerUI(_options: { url?: string; spec?: object; [key: string]: any }) {
    const { url, spec } = _options;
    const { topOffset } = useScrollOffset();
    const [headings, setHeadings] = useState<ISwaggerHeading[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const swaggerRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        importSwagger().then(SwaggerUIConstructor => {
            replaceDeepLinkScrolling(SwaggerUIConstructor, topOffset);
            if (swaggerRef.current) {
                setIsLoading(false);
                SwaggerUIConstructor({
                    domNode: swaggerRef.current,
                    plugins: [VanillaSwaggerPlugin()],
                    layout: "VanillaSwaggerLayout",
                    ..._options,
                    deepLinking: true,
                    onComplete: () => {
                        const opblocks = swaggerRef.current!.querySelectorAll(".opblock-tag, .opblock");
                        const headings = Array.from(opblocks)
                            .map(blockNode => {
                                if (blockNode.classList.contains("opblock-tag")) {
                                    const text = blockNode.getAttribute("data-tag");
                                    const ref = blockNode.querySelector(`[${DEEP_LINK_ATTR}]`)?.getAttribute("data-id");

                                    if (!text || !ref) {
                                        return null;
                                    }
                                    return {
                                        text: text.replace(/\u200B/g, ""),
                                        ref,
                                        level: 2,
                                    };
                                } else {
                                    const methodText =
                                        blockNode.querySelector(".opblock-summary-method")?.textContent ?? "";
                                    const pathText =
                                        blockNode.querySelector(".opblock-summary-path")?.textContent ?? "";
                                    const ref = blockNode.querySelector(`[${DEEP_LINK_ATTR}]`)?.getAttribute("data-id");
                                    if (!methodText || !pathText || !ref) {
                                        return null;
                                    }
                                    return {
                                        text: (methodText + " " + pathText).replace(/\u200B/g, ""),
                                        ref,
                                        level: 3,
                                    };
                                }
                            })
                            .filter(notEmpty);
                        setHeadings(headings);
                    },
                });
            }
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [spec, url]);

    return { swaggerRef, headings, isLoading };
}
