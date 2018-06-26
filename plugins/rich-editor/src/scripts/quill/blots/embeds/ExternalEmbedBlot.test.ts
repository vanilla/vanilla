/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Parchment from "parchment";
import Quill from "quill/core";
import { IEmbedData } from "@dashboard/embeds";
import ExternalEmbedBlot, { IEmbedValue } from "./ExternalEmbedBlot";
import { expect } from "chai";
import sinon from "sinon";
import "@dashboard/app/user-content/embeds/image";
import ErrorBlot from "./ErrorBlot";
import LoadingBlot from "@rich-editor/quill/blots/embeds/LoadingBlot";

const imageData: IEmbedValue = {
    data: {
        type: "image",
        url: "",
        name: "Pizza gif",
        body: null,
        photoUrl: null,
        height: 500,
        width: 500,
        attributes: {},
    },
    loaderData: {
        type: "image",
    },
};

describe("ExternalEmbedBlot", () => {
    let quill: Quill;

    before(() => {
        Quill.register("formats/embed-external", ExternalEmbedBlot, true);
    });

    beforeEach(() => {
        document.body.innerHTML = `<div id="editorRoot"></div>`;
        quill = new Quill("#editorRoot");
    });

    it("starts with a loading blot", () => {
        const insert = [
            {
                insert: {
                    [ExternalEmbedBlot.blotName]: imageData,
                },
            },
        ];

        quill.setContents(insert);
        expect(quill.root.querySelectorAll(".embedLoader-loader")).to.have.length(1);
    });

    describe("Async Creation", () => {
        it("can take a callback for when it finishes loading", done => {
            const dataPromise = new Promise(resolve => {
                setTimeout(resolve(imageData), 1);
            });
            const data: IEmbedValue = {
                dataPromise: dataPromise as any,
                loaderData: {
                    type: "image",
                },
            };

            const spy = sinon.spy();

            const externalEmbed = Parchment.create(ExternalEmbedBlot.blotName, data) as ExternalEmbedBlot;
            externalEmbed.registerLoadCallback(spy);
            quill.scroll.insertBefore(externalEmbed);

            // This is pretty ugly, but there's no other workaround unless all of quill can work with async.
            setImmediate(() => {
                sinon.assert.calledOnce(spy);
                done();
            });
        });
    });
});
