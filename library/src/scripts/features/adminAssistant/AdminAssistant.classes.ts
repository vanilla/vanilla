import { css } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ColorVar } from "@library/styles/CssVar";

export const AdminAssistantClasses = {
    root: css({
        position: "fixed",
        bottom: 24,
        right: 24,
        zIndex: 1060,
        maxHeight: "80vh",
    }),
    rootButton: css({
        background: ColorsUtils.var(ColorVar.Primary),
        color: "#fff",
        height: 40,
        width: 40,
        borderRadius: "50%",
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        boxShadow: "0 2px 4px rgba(0, 0, 0, 0.2)",
    }),
    buttonIcon: css({
        marginRight: 6,
    }),
    versionTag: css({
        marginTop: 12,
    }),
    detailDropdownContent: css({
        "&&&": {
            // Beat out the built-in width styling
            maxWidth: "calc(100vw - 32px)",
            width: "750px",
        },
    }),
    messagesDropdownContent: css({
        "&&&": {
            // Beat out the built-in width styling
            maxWidth: "calc(100vw - 32px)",
            width: "400px",
        },
    }),
    dropdownRoot: css({
        "&&": {
            marginBottom: 12,
            border: "none",
            transform: "translate3d(0, 0, 0)", // Force GPU acceleration for smoother transitions
        },
    }),
    frameBody: css({
        minHeight: "360px",
        maxHeight: "calc(100vh - 220px)",
    }),
};
