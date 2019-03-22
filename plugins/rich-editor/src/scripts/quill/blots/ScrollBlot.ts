/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import BaseScrollBlot from "quill/blots/scroll";
import { Blot } from "quill/core";
import Block, { BlockEmbed } from "quill/blots/block";
import ContainerBlot from "quill/blots/container";

export default class ScrollBlot extends BaseScrollBlot {
    /**
     * Determine if a blot is a line.
     *
     * @param blot The blot to check.
     * @param index The index relative to the blot to start at.
     * @param length The length inside of the blot to search.
     */
    private isLine(blot: Blot, index: number, length: number): blot is Block | BlockEmbed {
        if (blot instanceof ContainerBlot) {
            const childContainers = blot.descendants(
                (childBlot: Blot) => childBlot instanceof ContainerBlot,
                index,
                length,
            );
            const hasChildContainers = childContainers.length > 0;
            if (hasChildContainers) {
                return false;
            } else {
                return blot instanceof Block || blot instanceof BlockEmbed;
            }
        }

        return false;
    }

    /**
     * Get all of the line type blots in a particular range.
     *
     * @override Overridden to handle nested containers properly.
     * @param index
     * @param length
     * @param blot
     */
    public lines(
        blotIndex: number = 0,
        blotLength: number = Number.MAX_VALUE,
        blot: ContainerBlot = this,
    ): ContainerBlot[] {
        let lines: ContainerBlot[] = [];
        let lengthLeft = blotLength;
        blot.children.forEachAt(blotIndex, blotLength, (child: Blot, childIndex, childLength) => {
            if (this.isLine(child, childIndex, lengthLeft)) {
                lines.push(child as ContainerBlot);
            } else if (child instanceof ContainerBlot) {
                const additionalLines = this.lines(childIndex, lengthLeft, child);
                lines = lines.concat(additionalLines);
            }
            lengthLeft -= childLength;
        });
        return lines;
    }
}
