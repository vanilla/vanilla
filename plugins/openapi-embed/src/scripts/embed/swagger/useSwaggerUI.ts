/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useEffect, useRef, useState } from "react";
import "./swaggerStyles.scss";
import { VanillaSwaggerPlugin } from "@openapi-embed/embed/swagger/VanillaSwaggerPlugin";
import { patchSwaggerDeepLinks } from "@openapi-embed/embed/swagger/deepLinkPatch";
import { DEEP_LINK_ATTR } from "@openapi-embed/embed/swagger/VanillaSwaggerDeepLink";

export interface ISwaggerHeading {
    text: string;
    ref: string;
    level: number;
}

function notEmpty<TValue>(value: TValue | null | undefined): value is TValue {
    return value !== null && value !== undefined;
}

async function importSwagger() {
    const imported = await import(/* webpackChunkName: "swagger-ui-react" */ "swagger-ui-react/swagger-ui");
    const swagger = imported.default;
    patchSwaggerDeepLinks(swagger);
    return swagger;
}

export function useSwaggerUI(_options: { url?: string; spec?: object }) {
    const { url, spec } = _options;
    const [headings, setHeadings] = useState<ISwaggerHeading[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const swaggerRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        importSwagger().then(SwaggerUIConstructor => {
            if (swaggerRef.current) {
                setIsLoading(false);
                SwaggerUIConstructor({
                    domNode: swaggerRef.current,
                    plugins: [VanillaSwaggerPlugin()],
                    layout: "VanillaSwaggerLayout",
                    url,
                    spec,
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
    }, [spec, url]);

    useEffect(() => {
        swaggerRef.current!.addEventListener("submit", e => {
            e.stopPropagation();
            e.preventDefault();
        });
    }, []);

    return { swaggerRef, headings, isLoading };
}
