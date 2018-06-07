/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import HistoryModule from "./HistoryModule";
import Quill, { Blot } from "quill/core";
import Delta from "quill-delta";
import ExternalEmbedBlot from "./blots/embeds/ExternalEmbedBlot";
import chai, { expect } from "chai";
import { IEmbedData } from "@dashboard/embeds";
import Parchment from "parchment";

chai.config.truncateThreshold = 0;

const stubEmbedData: IEmbedData = {
    type: "stub",
    url: "",
    attributes: [],
};

const newLineDelta = { insert: "\n" };
const boldDelta = { insert: "bold", attributes: { bold: true } };
const HISTORY_DELAY = 0;

type InsertionCommand = () => void;

function sleep(duration) {
    return new Promise(resolve => {
        setTimeout(resolve, duration);
    });
}

describe("HistoryModule", () => {
    let quill: Quill;
    let historyModule: HistoryModule;

    before(() => {
        Quill.register("formats/embed-external", ExternalEmbedBlot, true);
        Quill.register("modules/history", HistoryModule, true);
    });

    beforeEach(() => {
        document.body.innerHTML = `<div>
            <div class="richEditor">
                <div id="quill"></div>
            </div>
        </div>`;

        quill = new Quill("#quill");
        historyModule = new HistoryModule(quill, { delay: HISTORY_DELAY });
    });

    const testInsertionCommands = async (commands: InsertionCommand[]) => {
        const results = commands.map(command => {
            command();
            return quill.getContents().ops;
        });

        const redoResults: any[] = [];

        redoResults.push(results.pop());
        historyModule.undo();

        results.forEach((result, index) => {
            console.log("undo " + index);
            expect(quill.getContents().ops, `Undo #${index} failed.`).deep.equals(result);
            redoResults.push(results.pop());
            historyModule.undo();
        });

        const middleOps = quill.getContents().ops;
        await sleep(10);

        redoResults.reverse().forEach((redoResult, index) => {
            console.log("redo " + index);
            historyModule.redo();
            expect(quill.getContents().ops, `Redo #${index} failed.`).deep.equals(redoResult);
        });
    };

    // Testing what should be the normal undo functionality for sanity.
    it("provides a consistent undo/redo stack with non-async blots", async () => {
        await testInsertionCommands([
            () => quill.insertText(0, "test", Quill.sources.USER),
            () => quill.insertText(4, "bold", "bold", "true", Quill.sources.USER),
        ]);
    });

    // Testing what should be the normal undo functionality for sanity.
    it("provides a consistent undo/redo stack with async blots", async () => {
        await testInsertionCommands([
            () => quill.insertText(0, "test", Quill.sources.USER),
            () => quill.insertEmbed(0, ExternalEmbedBlot.blotName, Promise.resolve(stubEmbedData), Quill.sources.USER),
            () => quill.insertText(quill.scroll.length(), "bold", "bold", "true", Quill.sources.USER),
            () => quill.insertText(quill.scroll.length(), "bold", "bold", "true", Quill.sources.USER),
            () => quill.insertText(quill.scroll.length(), "bold", "bold", "true", Quill.sources.USER),
            () => quill.insertEmbed(0, ExternalEmbedBlot.blotName, Promise.resolve(stubEmbedData), Quill.sources.USER),
            () => quill.insertText(quill.scroll.length(), "bold", "bold", "true", Quill.sources.USER),
        ]);
    });
});
