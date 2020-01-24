/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { VanillaSwaggerLayout } from "@openapi-embed/embed/VanillaSwaggerLayout";
import { useEffect, useRef, useState } from "react";
import "./swaggerStyles.scss";

interface ISwaggerHeading {
    text: string;
    ref: string;
    level: number;
}

function notEmpty<TValue>(value: TValue | null | undefined): value is TValue {
    return value !== null && value !== undefined;
}

function VanillaPlugin() {
    // Create the plugin that provides our layout component
    return {
        components: {
            VanillaSwaggerLayout: VanillaSwaggerLayout,
        },
    };
}

async function importSwagger() {
    const imported = await import(/* webpackChunkName: "swagger-ui-react" */ "swagger-ui-react/swagger-ui");
    return imported.default;
}

export function useSwaggerUI(specUrl: string) {
    const [headings, setHeadings] = useState<ISwaggerHeading[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const swaggerRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        importSwagger().then(SwaggerUIConstructor => {
            if (swaggerRef.current) {
                setIsLoading(false);
                SwaggerUIConstructor({
                    domNode: swaggerRef.current,
                    plugins: [VanillaPlugin()],
                    layout: "VanillaSwaggerLayout",
                    deepLinking: false,
                    url: specUrl,
                    onComplete: () => {
                        const opblocks = swaggerRef.current!.querySelectorAll(".opblock-tag");
                        const headings = Array.from(opblocks)
                            .map(blockNode => {
                                const text = blockNode.getAttribute("data-tag");
                                const ref = blockNode.id;

                                if (!text || !ref) {
                                    return null;
                                }
                                return {
                                    text,
                                    ref,
                                    level: 2,
                                };
                            })
                            .filter(notEmpty);
                        setHeadings(headings);
                    },
                });
            }
        });
    }, [specUrl]);

    useEffect(() => {
        swaggerRef.current!.addEventListener("submit", e => {
            e.stopPropagation();
            e.preventDefault();
        });
    }, []);

    return { swaggerRef, headings, isLoading };
}
