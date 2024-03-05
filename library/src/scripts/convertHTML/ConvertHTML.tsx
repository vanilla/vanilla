/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { t } from "@library/utility/appUtils";
import { deserializeHtml as convertToRich2 } from "@library/vanilla-editor/VanillaEditor.loadable";
import registerQuill from "@rich-editor/quill/registerQuill";
import { useQuery } from "@tanstack/react-query";
import Quill, { QuillOptionsStatic } from "quill/core";
import { useMemo } from "react";
import { useParams } from "react-router-dom";

export interface ConvertHtmlRouteParams {
    format: string;
    recordType: string;
    recordID: string;
}

export function ConvertHTMLImpl(props: ConvertHtmlRouteParams) {
    const { format, recordType, recordID } = props;
    const { data } = useQuery({
        queryKey: ["convertHTML", recordType, recordID],
        queryFn: async ({ queryKey }) => {
            const [_, type, id] = queryKey;
            const response = await apiv2.get(`${type}s/${id}`);
            return response.data;
        },
    });

    const formattedBody = useMemo<string>(() => {
        // this page only supports conversion to rich formats
        const formatRegEx = new RegExp("rich", "i");
        if (!format.match(formatRegEx)) {
            return t(`ERROR: URL param for "format" must be one of ["rich", "rich2"]`);
        }

        if (data) {
            // parse the HTML string into DOM nodes to process into valid HTML
            const parser = new DOMParser();
            const parsedHTML = parser.parseFromString(data.body, "text/html");
            // reformat for brightcove to kaltura video conversion
            const parsedBody = processLiVideo(parsedHTML.body);
            // get the new html string
            const html = parsedBody.firstChild?.parentElement?.innerHTML ?? "";
            // deserialize the html into the requested format
            const bodyJSON = format.toLowerCase() === "rich" ? convertToRich(html) : convertToRich2(html);
            // return the json as a string
            return JSON.stringify(bodyJSON);
        }

        return "";
    }, [format, data]);

    return (
        <div id="formatted-body" data-testid="formatted-body">
            {formattedBody}
        </div>
    );
}

function processLiVideo(node: Node) {
    const parentEl = node.cloneNode();

    node.childNodes.forEach((child) => {
        if (child.nodeName.toLowerCase() === "li-video") {
            const liVidEl = child.cloneNode(true) as Element;
            const vid = liVidEl.getAttribute("vid");
            const textContent = document.createTextNode(`li-video vid="${vid}"_BrightCoverVideo_To_Kaltura li-video`);
            const mark = document.createElement("code");
            mark.appendChild(textContent);
            parentEl.appendChild(mark);
        } else {
            const processedEl = processLiVideo(child);
            parentEl.appendChild(processedEl);
        }
    });

    return parentEl;
}

function convertToRich(html: string) {
    const textArea = document.createElement("textarea");
    registerQuill();
    const options: QuillOptionsStatic = {
        theme: "vanilla",
        modules: {
            syntax: {
                highlight: () => {}, // Unused but required to satisfy
                // https://github.com/quilljs/quill/blob/1.3.7/modules/syntax.js#L43
                // We have overridden the highlight method ourselves.
            },
        },
    };
    const quill = new Quill(textArea, options);
    quill.clipboard.dangerouslyPasteHTML(html);
    return quill.getContents().ops;
}

export default function ConvertHTML() {
    const params = useParams<ConvertHtmlRouteParams>();
    return <ConvertHTMLImpl {...params} />;
}
