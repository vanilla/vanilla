/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React, { useLayoutEffect, useRef } from "react";

export function useDomNodeAttachment(domNodes?: Node[], intoRef?: React.RefObject<any>): React.RefObject<any> {
    const ownRef = useRef<HTMLElement | null>();
    const ref = intoRef ?? ownRef;

    useLayoutEffect(() => {
        if (domNodes && ref.current) {
            domNodes.forEach((node) => {
                ref.current?.appendChild(node);
            });
        }
    }, [domNodes]);

    return ref;
}

interface IProps extends React.HTMLAttributes<HTMLDivElement> {
    nodes: Node[];
}

export function DomNodeAttacher(_props: IProps) {
    const { nodes, ...props } = _props;
    const ref = useDomNodeAttachment(nodes);
    return <div {...props} ref={ref} />;
}
