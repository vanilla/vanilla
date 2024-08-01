/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import TruncatedText from "@library/content/TruncatedText";
import { css } from "@emotion/css";

export default {
    title: "Content/Truncation",
};

const sampleText =
    "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake. Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake. Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake. Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.";

export function StringNoProps() {
    return <TruncatedText>{sampleText}</TruncatedText>;
}

export function String2Lines() {
    return <TruncatedText lines={2}>{sampleText}</TruncatedText>;
}

export function StringMaxCharCount140() {
    return <TruncatedText maxCharCount={140}>{sampleText}</TruncatedText>;
}

export function StringMaxHeight100px() {
    const maxH = css({
        maxHeight: 100,
    });
    return (
        <TruncatedText useMaxHeight={true} className={maxH}>
            {sampleText}
        </TruncatedText>
    );
}

export function NodeNoProps() {
    return (
        <TruncatedText>
            <div>{sampleText}</div>
        </TruncatedText>
    );
}

export function Node2Lines() {
    return (
        <TruncatedText lines={2}>
            <div>{sampleText}</div>
        </TruncatedText>
    );
}

export function NodeMaxHeight100px() {
    const maxH = css({
        maxHeight: 100,
    });
    return (
        <TruncatedText useMaxHeight={true} className={maxH}>
            <div>{sampleText}</div>
        </TruncatedText>
    );
}
