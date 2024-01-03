import { SortedTagCloud } from "@dashboard/components/panels/SortedTagCloud";
import React from "react";

export default {
    title: "Filters/Tag",
};

const groupedTags = [
    {
        id: "customFieldOne",
        label: "Custom Field One",
        tags: ["Value One", "Value Two", "Value Three", "Value Four", "Value Five", "Value Six", "Value Seven"],
    },
    {
        id: "customFieldTwo",
        label: "Custom Field Two which goes on and on and on and on because its so long and and lengthy and redundant",
        tags: ["Value One"],
    },
    {
        id: "customFieldThree",
        label: "Custom Field Three",
        tags: [
            "A really long value which one can assume will never be the case but it may be the case so we should design for it anyways just incase it becomes the case",
        ],
    },
];

const narrowStyles = {
    display: "block",
    margin: "auto",
    maxWidth: 480,
    width: "100%",
};

export function ReadOnly() {
    return (
        <div style={narrowStyles}>
            <SortedTagCloud groupedTags={groupedTags} />
        </div>
    );
}

export function Delible() {
    return (
        <div style={narrowStyles}>
            <SortedTagCloud groupedTags={groupedTags.map((groupTag) => ({ ...groupTag, onRemove: () => null }))} />
        </div>
    );
}
