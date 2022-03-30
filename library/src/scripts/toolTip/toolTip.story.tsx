/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { globalVariables } from "@library/styles/globalStyleVars";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { Icon } from "@vanilla/icons";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { StoryTextContent } from "@library/storybook/storyData";

export default {
    title: "Components/Tool Tips",
    parameters: {},
};

export const Default = () => {
    return (
        <>
            <div
                style={{
                    height: "calc(100vh - 100px)",
                    minHeight: "500px",
                    width: "100%",
                    border: "solid #1EA7FD 4px",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    textAlign: "center",
                }}
            >
                <StoryContent>
                    <StoryHeading depth={1}>Tool Tips</StoryHeading>
                    <StoryParagraph>Hover over boxes to see tool tips. </StoryParagraph>
                    <div style={{ width: "100px", color: "white", background: "black", margin: "auto" }}>
                        <ToolTip
                            label={
                                "Toto, we're not in Kansas anymoreToto, we're not in Kansas anymoreToto, we're not in Kansas anymore"
                            }
                        >
                            <span
                                tabIndex={0}
                                style={{
                                    width: "100%",
                                    textAlign: "center",
                                    display: "inline-block",
                                }}
                            >
                                Center
                            </span>
                        </ToolTip>
                        <ToolTip label={"This one's an icon"}>
                            <ToolTipIcon>
                                <span style={{ backgroundColor: "#CACACA", width: "20px", height: "20px" }}>
                                    <Icon icon={"status-warning"} size={"compact"} />
                                </span>
                            </ToolTipIcon>
                        </ToolTip>
                    </div>
                </StoryContent>
                <div
                    style={{
                        position: "absolute",
                        top: 0,
                        left: 0,
                        width: "100px",
                        color: "white",
                        background: "black",
                        textAlign: "center",
                    }}
                >
                    <ToolTip
                        label={
                            "Toto, we're not in Kansas anymoreToto, we're not in Kansas anymoreToto, we're not in Kansas anymore"
                        }
                    >
                        <span tabIndex={0} style={{ width: "100%", textAlign: "center", display: "inline-block" }}>
                            Top Left
                        </span>
                    </ToolTip>
                </div>
                <div
                    style={{
                        position: "absolute",
                        top: 0,
                        right: 0,
                        width: "100px",
                        color: "white",
                        background: "black",
                        textAlign: "center",
                    }}
                >
                    <ToolTip
                        label={
                            "Toto, we're not in Kansas anymoreToto, we're not in Kansas anymoreToto, we're not in Kansas anymore"
                        }
                    >
                        <span tabIndex={0} style={{ width: "100%", textAlign: "center", display: "inline-block" }}>
                            Top Right
                        </span>
                    </ToolTip>
                </div>

                <div
                    style={{
                        position: "absolute",
                        bottom: 0,
                        left: 0,
                        width: "100px",
                        color: "white",
                        background: "black",
                        textAlign: "center",
                    }}
                >
                    <ToolTip
                        label={
                            "Toto, we're not in Kansas anymoreToto, we're not in Kansas anymoreToto, we're not in Kansas anymore"
                        }
                    >
                        <span tabIndex={0} style={{ width: "100%", textAlign: "center", display: "inline-block" }}>
                            Bottom Left
                        </span>
                    </ToolTip>
                </div>
                <div
                    style={{
                        position: "absolute",
                        bottom: 0,
                        right: 0,
                        width: "100px",
                        color: "white",
                        background: "black",
                        textAlign: "center",
                    }}
                >
                    <ToolTip
                        label={
                            "Toto, we're not in Kansas anymoreToto, we're not in Kansas anymoreToto, we're not in Kansas anymore"
                        }
                    >
                        <span tabIndex={0} style={{ width: "100%", textAlign: "center", display: "inline-block" }}>
                            Bottom Left
                        </span>
                    </ToolTip>
                </div>
            </div>
        </>
    );
};

export const StackedTooltip = () => {
    return (
        <Modal isVisible={true} size={ModalSizes.LARGE}>
            <StoryTextContent />
            <Modal isVisible={true} size={ModalSizes.MEDIUM}>
                <StoryTextContent />
                <Modal isVisible={true} size={ModalSizes.SMALL}>
                    <ToolTip
                        label={
                            "Toto, we're not in Kansas anymoreToto, we're not in Kansas anymoreToto, we're not in Kansas anymore"
                        }
                    >
                        <span tabIndex={0} style={{ width: "100%", textAlign: "center", display: "inline-block" }}>
                            Hover over me
                        </span>
                    </ToolTip>
                </Modal>
            </Modal>
        </Modal>
    );
};
