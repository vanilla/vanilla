/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css } from "@emotion/css";
import FrameHeader from "@library/layout/frame/FrameHeader";

interface IProps {
    onClose: () => void;
    onBack?: () => void;
    title: React.ReactNode;
}

export function ProductMessagesHeader(props: IProps) {
    return (
        <FrameHeader
            className={classes.root}
            title={props.title}
            onBackClick={props.onBack}
            closeFrame={props.onClose}
        />
    );
}

const classes = {
    root: css({
        paddingTop: 16,
        paddingBottom: 16,
        color: "#fff",
        background: `linear-gradient(to right, #1471A9, #A72559)`,
        position: "relative",
        zIndex: 1,
        borderBottom: "none",
        "& *": {
            color: "#fff !important",
            borderColor: "#fff !important",
        },
        "& h2:focus-visible": {
            boxShadow: "none",
        },
    }),
};
