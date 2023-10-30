import React, { useContext, useRef } from "react";

const BoundsContext = React.createContext({
    boundsRef: React.createRef<HTMLDivElement | null>(),
});

export function VanillaEditorBoundsContext(props: { children: React.ReactNode }) {
    const ownRef = useRef<HTMLDivElement | null>(null);

    return (
        <BoundsContext.Provider
            value={{
                boundsRef: ownRef,
            }}
        >
            <div style={{ position: "relative" }} id="vanilla-editor-bounds-context" ref={ownRef}>
                {props.children}
            </div>
        </BoundsContext.Provider>
    );
}

export function useVanillaEditorBounds() {
    return useContext(BoundsContext);
}
