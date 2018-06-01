/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import Parchment from "parchment";
import Quill, { Blot } from "quill/core";
import { IEmbedData } from "@dashboard/embeds";
import ExternalEmbedBlot from "./ExternalEmbedBlot";
import { expect } from "chai";
import LoadingBlot from "./LoadingBlot";
import { filterQuillHTML } from "../../test-utilities";
import sinon from "sinon";
import { registerEmbed } from "@dashboard/embeds";
import "@dashboard/app/user-content/embeds/image";
import ErrorBlot from "./ErrorBlot";

const imageData: IEmbedData = {
    type: "image",
    url: "/some-image-url.jpg",
    name: "Pizza gif",
    body: null,
    photoUrl: null,
    height: 500,
    width: 500,
    attributes: {},
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
        expect(quill.root.querySelectorAll("." + LoadingBlot.className)).to.have.length(1);
    });

    describe("Async Creation", () => {
        it("can take a callback for when it finishes loading", done => {
            const dataPromise = new Promise(resolve => {
                setTimeout(resolve(imageData), 1);
            });
            const spy = sinon.spy();

            const externalEmbed = Parchment.create(ExternalEmbedBlot.blotName, dataPromise) as ExternalEmbedBlot;
            externalEmbed.registerLoadCallback(spy);
            quill.scroll.insertBefore(externalEmbed);

            // This is pretty ugly, but there's no other workaround unless all of quill can work with async.
            setImmediate(() => {
                sinon.assert.calledOnce(spy);
                done();
            });
        });

        it("can be created asyncrounously for it's inital creation", async () => {
            const dataPromise: Promise<IEmbedData> = new Promise(resolve => {
                setTimeout(resolve(imageData), 1);
            });

            const embed = await ExternalEmbedBlot.createAsync(dataPromise);
            expect(embed).instanceof(ExternalEmbedBlot);
        });

        it("It automatically catches errors and turns them into an Error blot", async () => {
            const dataPromise: Promise<IEmbedData> = Promise.reject("Error!");

            const embed = await ExternalEmbedBlot.createAsync(dataPromise);
            expect(embed).instanceof(ErrorBlot);
        });
    });
});
