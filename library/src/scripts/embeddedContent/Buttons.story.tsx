/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { StoryTiles } from "@library/storybook/StoryTiles";
import Button from "@library/forms/Button";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";
import { StoryParagraph } from "@library/storybook/StoryParagraph";

import { globalVariables } from "@library/styles/globalStyleVars";
import { unit } from "@library/styles/styleHelpers";
import { ButtonPreset, buttonUtilityClasses } from "@library/forms/buttonStyles";
import { CheckCompactIcon, CloseCompactIcon, ComposeIcon } from "@library/icons/common";
import { storyWithConfig } from "@library/storybook/StoryContext";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { formElementsVariables } from "@library/forms/formElementStyles";

export default {
    title: "Buttons",
};

function StoryButton() {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Buttons</StoryHeading>
            <StoryParagraph>
                Buttons use a{" "}
                <strong>
                    <code>baseClass</code>
                </strong>{" "}
                to specify the type of button you want. The types are available through the enum{" "}
                <strong>
                    <code>ButtonTypes</code>
                </strong>{" "}
                and if you want to do something custom and not overwrite the base button styles, use
                <strong>
                    {" "}
                    <code>ButtonTypes.CUSTOM</code>
                </strong>
                .
            </StoryParagraph>
            <StoryTiles>
                <StoryTileAndTextCompact type="titleBar" text={"Standard"}>
                    <Button>Standard</Button>
                </StoryTileAndTextCompact>

                <StoryTileAndTextCompact text={"Primary"}>
                    <Button baseClass={ButtonTypes.PRIMARY}>Primary</Button>
                </StoryTileAndTextCompact>

                <StoryTileAndTextCompact type="titleBar" text={"For Title Bar (Sign in Button)"}>
                    <Button baseClass={ButtonTypes.TRANSPARENT}>Transparent</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact type="titleBar" text={"For Title Bar (Register)"}>
                    <Button baseClass={ButtonTypes.TRANSLUCID}>Translucid</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={"Simple text button"}>
                    <Button baseClass={ButtonTypes.TEXT}>Text</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={"Text with primary color"}>
                    <Button baseClass={ButtonTypes.TEXT_PRIMARY}>Text Primary</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact
                    text={`Icon (${unit(globalVariables().buttonIcon.size)} x ${unit(
                        globalVariables().buttonIcon.size,
                    )})`}
                >
                    <Button baseClass={ButtonTypes.ICON} title={"Icon"}>
                        <CloseCompactIcon />
                    </Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact
                    text={`Icon Compact (${unit(globalVariables().icon.sizes.default)}px x ${unit(
                        globalVariables().icon.sizes.default,
                    )})`}
                >
                    <Button baseClass={ButtonTypes.ICON_COMPACT}>
                        <CheckCompactIcon />
                    </Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact
                    text={`Icon Compact (Disabled) (${unit(globalVariables().icon.sizes.default)}px x ${unit(
                        globalVariables().icon.sizes.default,
                    )})`}
                >
                    <Button disabled baseClass={ButtonTypes.ICON_COMPACT}>
                        <CheckCompactIcon />
                    </Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact
                    text={
                        "If you don't want to fight against existing styles and write your own custom button, use the custom class."
                    }
                >
                    <Button baseClass={ButtonTypes.CUSTOM}>Custom</Button>
                </StoryTileAndTextCompact>
            </StoryTiles>
            <StoryHeading>Disabled Buttons</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact text={"Most common button (Disabled)"}>
                    <Button disabled>Standard (Disabled)</Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={"Call to action (Disabled)"}>
                    <Button disabled baseClass={ButtonTypes.PRIMARY}>
                        Primary (Disabled)
                    </Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={"Simple text button (Disabled)"}>
                    <Button disabled baseClass={ButtonTypes.TEXT}>
                        Text (Disabled)
                    </Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={"Text with primary color (Disabled)"}>
                    <Button disabled baseClass={ButtonTypes.TEXT_PRIMARY}>
                        Text Primary (Disabled)
                    </Button>
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact
                    text={`Icon (Disabled) (${unit(globalVariables().buttonIcon.size)} x ${unit(
                        globalVariables().buttonIcon.size,
                    )})`}
                >
                    <Button disabled baseClass={ButtonTypes.ICON} title={"Icon"}>
                        <CloseCompactIcon />
                    </Button>
                </StoryTileAndTextCompact>
            </StoryTiles>

            <StoryHeading>Button With Icon</StoryHeading>
            <StoryParagraph>
                {"You can just add an icon in with the text of your button. It's worth noting however, that there might be\n" +
                    'a "compact" version of your icon that doesn\'t have as much padding that will look better.'}
            </StoryParagraph>
            <StoryTiles>
                <StoryTileAndTextCompact text={"Icon and Text Example"}>
                    <Button baseClass={ButtonTypes.STANDARD}>
                        <ComposeIcon className={buttonUtilityClasses().buttonIconRightMargin} />
                        {"Icon and Text"}
                    </Button>
                </StoryTileAndTextCompact>
            </StoryTiles>
        </StoryContent>
    );
}

export const Standard = storyWithConfig({}, () => <StoryButton />);
export const PresetsOutline = storyWithConfig(
    {
        themeVars: {
            global: {
                buttonPreset: {
                    style: ButtonPreset.OUTLINE,
                },
            },
            button: {
                primary: {
                    colors: {
                        fg: globalVariables().mainColors.primary,
                        bg: "#fff",
                    },
                },
            },
        },
    },
    () => <StoryButton />,
);

export const PresetsSolid = storyWithConfig(
    {
        themeVars: {
            global: {
                buttonPreset: {
                    style: ButtonPreset.SOLID,
                },
            },
        },
    },
    () => <StoryButton />,
);

export const NoBorderRadius = storyWithConfig(
    {
        themeVars: {
            global: {
                borderType: {
                    formElements: {
                        buttons: {
                            radius: 0,
                        },
                    },
                },
            },
        },
    },
    () => <StoryButton />,
);

export const FullBorderRadius = storyWithConfig(
    {
        themeVars: {
            global: {
                borderType: {
                    formElements: {
                        buttons: {
                            radius: formElementsVariables().sizing.height / 2,
                        },
                    },
                },
            },
        },
    },
    () => <StoryButton />,
);
