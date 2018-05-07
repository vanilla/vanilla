/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import { IEmbedData, renderEmbed } from "@core/embeds";
import FocusableEmbedBlot from "./FocusableEmbedBlot";
import { setData, getData } from "@core/dom";
import LoadingBlot from "../Embeds/LoadingBlot";

const DATA_KEY = "__loading-data__";

export default abstract class AsyncLoadableEmbedBlot extends FocusableEmbedBlot {
    /**
     * @throws Always throws an error because this blot must be created asynchronously.
     */
    public static create(data: any): any {
        const node = LoadingBlot.create(data);
        setData(node, DATA_KEY, data);
        return node;
    }

    public static async createAsync(data: Promise<any>): Promise<AsyncLoadableEmbedBlot> {
        // Typescript still doesn't have abstract static methods. :(
        // https://github.com/Microsoft/TypeScript/issues/14600
        throw new Error("You must implement the createAsync method whe subclassing AsyncLoadableEmbedBlot.");
    }

    public static value(node) {
        return getData(node, "data");
    }

    constructor(domNode) {
        super(domNode);
        const loadingData = getData(domNode, DATA_KEY, null);

        if (loadingData) {
            // This is intentionally a floating promise. We want to immediately return the loading blot if this was created using ExternalEmbedBlot.create(), in which case a loading blot will be returned immediately, but will be replaced with a final blot later.
            // tslint:disable-next-line:no-floating-promises
            this.statics.createAsync(loadingData).then(blot => {
                this.replaceWith(blot);
            });
        }
    }
}
