import { IDeveloperProfileDetails } from "@dashboard/developer/profileViewer/DeveloperProfile.types";
import { useDeveloperProfile } from "@dashboard/developer/profileViewer/DeveloperProfile.context";
import { DeveloperProfileSpanDetails } from "@dashboard/developer/profileViewer/DeveloperProfile.SpanDetails";
import { MODAL_CONTAINER_ID } from "@library/modal/mountModal";
import { mountPortal, useMeasure } from "@vanilla/react-utils";
import { useRef } from "react";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { globalVariables } from "@library/styles/globalStyleVars";
import { css } from "@emotion/css";

const globalVars = globalVariables();
const classes = {
    container: css({
        position: "fixed",
        overflow: "auto",
        zIndex: 100000,
        background: "#fff",
        padding: "8px 16px 16px",
        borderTop: singleBorder(),
    }),
    forceSized: css({
        width: "100%",
        height: 1,
    }),
};

export function DeveloperProfileDetailsPanel(props: { profile: IDeveloperProfileDetails }) {
    const { selectedSpan, setSelectedSpan } = useDeveloperProfile();
    const { profile } = props.profile;
    const containerRef = useRef<HTMLDivElement | null>(null);
    const containerMeasure = useMeasure(containerRef, false, false);
    return (
        <>
            <div ref={containerRef} className={classes.forceSized}>
                {selectedSpan &&
                    mountPortal(
                        <div
                            className={classes.container}
                            style={{
                                left: containerMeasure.left,
                                right: containerMeasure.right,
                                width: containerMeasure.width,
                                bottom: 0,
                                maxHeight: 300,
                                overflow: "auto",
                            }}
                        >
                            <DeveloperProfileSpanDetails
                                span={selectedSpan}
                                fullSize
                                allSpans={Object.values(profile.spans)}
                                onClose={() => {
                                    setSelectedSpan(null);
                                }}
                            />
                        </div>,
                        MODAL_CONTAINER_ID,
                        true,
                    )}
            </div>
        </>
    );
}
