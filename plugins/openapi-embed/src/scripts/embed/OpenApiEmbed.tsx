/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState, useRef, useEffect } from "react";
import { IBaseEmbedProps, FOCUS_CLASS } from "@library/embeddedContent/embedService";
import { SwaggerUIBundle } from "swagger-ui-dist";
import "./openapi-embed.scss";
import { formatUrl, getMeta } from "@library/utility/appUtils";

interface IProps extends IBaseEmbedProps {}

export function OpenApiEmbed(props: IProps) {
    const [savedUrl, setSavedUrl] = useState("http://dev.vanilla.localhost/api/v2/openapi/v3");
    const [url, setUrl] = useState("");
    const swaggerRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        swaggerRef.current!.addEventListener("submit", e => {
            e.stopPropagation();
            e.preventDefault();
        });
    });

    useEffect(() => {
        SwaggerUIBundle({
            domNode: swaggerRef.current,
            plugins: [SwaggerUIBundle.plugins.DownloadUrl],
            presets: [SwaggerUIBundle.presets.apis],
            requestInterceptor: (request: Request) => {
                request.headers["x-transient-key"] = getMeta("TransientKey");
                return request;
            },
            // docExpansion: "none",
            deepLinking: false,
            url: formatUrl("/api/v2/open-api/v3" + window.location.search),
            validatorUrl: null,
            onComplete: () => {
                console.log("complete");
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
                    .filter(item => item !== null);
                console.log("found headings", headings);
                props.syncBackEmbedValue({ headings });
            },
        });
    }, [savedUrl]);

    return (
        // <EmbedContent type="OpenApi" inEditor={props.inEditor}>
        <div className="u-excludeFromPointerEvents" className={FOCUS_CLASS}>
            {/* <TextInput value={url} onChange={e => setUrl(e.target.value)} className={FOCUS_CLASS} /> */}
            {/* <Button onClick={() => setSavedUrl(url)}>Check API definition</Button> */}
            <div ref={swaggerRef} />
            {/* {savedUrl && <SwaggerUI url={savedUrl} docExpansion="list" />} */}
        </div>
        // </EmbedContent>
    );
}
