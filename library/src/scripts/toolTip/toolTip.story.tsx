/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { globalVariables } from "@library/styles/globalStyleVars";
import { color } from "csx";
import { StoryContent } from "@library/storybook/StoryContent";
import SearchContext from "@library/contexts/SearchContext";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { WarningIcon } from "@library/icons/common";

const story = storiesOf("Components", module);

story.add("Tool Tips", () => {
    const globalVars = globalVariables();
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
                                    <WarningIcon />
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
});
