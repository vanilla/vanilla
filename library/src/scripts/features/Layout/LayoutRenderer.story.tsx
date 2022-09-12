import React from "react";
import { addComponent } from "@library/utility/componentRegistry";
import { fakeDiscussions } from "@library/features/discussions/DiscussionList.story";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import {
    IHydratedLayoutSpec,
    IHydratedLayoutWidget,
    LayoutDevice,
} from "@library/features/Layout/LayoutRenderer.types";
import { LayoutRenderer } from "@library/features/Layout/LayoutRenderer";

export default {
    title: "Widgets/LayoutRenderer",
};

function Header(props) {
    return (
        <header
            style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                minHeight: 75,
                fontWeight: "bold",
                textTransform: "uppercase",
                fontSize: 16,
                margin: "8px",
                background: "rgba(125, 125, 255, .8)",
                borderRadius: 4,
                border: "solid 1px rgba(0, 0, 0, .2)",
            }}
        >
            {props.title}
        </header>
    );
}
function TwoCol(props) {
    return (
        <main
            style={{
                margin: "8px",
                width: "60%",
                float: "left",
            }}
        >
            {props.children}
        </main>
    );
}
function OneCol(props) {
    return (
        <aside
            style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                minHeight: 75,
                fontWeight: "bold",
                textTransform: "uppercase",
                fontSize: 16,
                margin: "8px",
                background: "rgba(125, 255, 125, .8)",
                borderRadius: 4,
                border: "solid 1px rgba(0, 0, 0, .2)",
                width: "calc(40% - 32px)",
                float: "left",
            }}
        >
            {props.children}
        </aside>
    );
}
function Panel(props) {
    if (props.makeComponentFail) {
        let boom;
        boom();
    }
    return (
        <section
            style={{
                display: "flex",
                flexDirection: "column",
                alignItems: "center",
                justifyContent: "center",
                minHeight: 275,
                fontWeight: "bold",
                fontSize: 16,
                margin: "8px",
                background: "rgba(255, 255, 255, .8)",
                borderRadius: 4,
                border: "solid 1px rgba(0, 0, 0, .2)",
                width: "calc(100% - 16px)",
            }}
        >
            {props.title}
            {props.children && props.children}
        </section>
    );
}
addComponent("header", Header);
addComponent("TwoCol", TwoCol);
addComponent("OneCol", OneCol);
addComponent("panel", Panel);

const sampleHomeLayout: IHydratedLayoutWidget[] = [
    {
        $reactComponent: "header",
        $reactProps: {
            title: "Page Header",
        },
    },
    {
        $reactComponent: "TwoCol",
        $reactProps: {
            children: [
                {
                    $reactComponent: "DiscussionList",
                    $reactProps: { discussions: fakeDiscussions },
                },
            ],
        },
    },
    {
        $reactComponent: "OneCol",
        $reactProps: {
            children: [
                {
                    $reactComponent: "panel",
                    $reactProps: {
                        title: "Some aside content here",
                    },
                },
            ],
        },
    },
];

function LayoutStory(props: IHydratedLayoutSpec) {
    const source = JSON.stringify(props.layout, null, 2);

    return (
        <>
            <StoryHeading depth={3}>Source</StoryHeading>
            <pre
                style={{
                    margin: "8px 0 24px",
                    padding: 16,
                    background: "#efefef",
                    maxHeight: "40vh",
                    maxWidth: "100%",
                    overflow: "auto",
                }}
            >
                <code>{source}</code>
            </pre>
            <StoryHeading depth={3}>Result</StoryHeading>
            <div style={{ marginBottom: 16 }}>
                <LayoutRenderer {...props} />
            </div>
        </>
    );
}

export function Default() {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Layout Component</StoryHeading>
            <StoryParagraph>
                The <code>&lt;Layout/&gt;</code> component lets you render a collection of registered react components
                based on a schema that defines the component name and their props.
            </StoryParagraph>
            <StoryParagraph>
                The basic component specification has a <code>layout</code> array where you list the components that
                will be displayed.
            </StoryParagraph>
            <StoryParagraph>
                Each component has a <code>$reactComponent</code> property that is the name of the component that was
                registered using <code>addComponent()</code>. Props for the component reside in the{" "}
                <code>$reactProps</code> property. Both of these properties are required to render the component
            </StoryParagraph>
            <StoryHeading depth={2}>Basic Example</StoryHeading>
            <StoryParagraph>This example renders the same component, each with different props.</StoryParagraph>
            <LayoutStory
                layout={[
                    { $reactComponent: "panel", $reactProps: { title: "Vanilla" } },
                    { $reactComponent: "panel", $reactProps: { title: "Forums" } },
                ]}
            />
        </StoryContent>
    );
}

export function Nesting() {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Rendering Child Components</StoryHeading>
            <StoryParagraph>
                The <code>&lt;Layout/&gt;</code> component will resolve all nested components that are specified in the{" "}
                <code>$reactProps</code> as long as they are both valid and registered.
            </StoryParagraph>
            <StoryHeading depth={2}>Basic Component Nesting</StoryHeading>
            <StoryParagraph>This is example shows a component nested inside a another.</StoryParagraph>
            <LayoutStory
                layout={[
                    {
                        $reactComponent: "panel",
                        $reactProps: {
                            title: "Vanilla",
                            children: { $reactComponent: "panel", $reactProps: { title: "Forums" } },
                        },
                    },
                ]}
            />
            <StoryParagraph>Nesting depth is unrestricted</StoryParagraph>
            <LayoutStory
                layout={[
                    {
                        $reactComponent: "panel",
                        $reactProps: {
                            title: "Depth 0",
                            children: {
                                $reactComponent: "panel",
                                $reactProps: {
                                    title: "Depth 1",
                                    children: {
                                        $reactComponent: "panel",
                                        $reactProps: {
                                            title: "Depth 2",
                                            children: {
                                                $reactComponent: "panel",
                                                $reactProps: {
                                                    title: "Depth 3",
                                                    children: {
                                                        $reactComponent: "panel",
                                                        $reactProps: {
                                                            title: "Depth 4",
                                                            children: {
                                                                $reactComponent: "panel",
                                                                $reactProps: {
                                                                    title: "Depth 5",
                                                                    children: {
                                                                        $reactComponent: "panel",
                                                                        $reactProps: {
                                                                            title: "Depth 6",
                                                                            children: {
                                                                                $reactComponent: "panel",
                                                                                $reactProps: {
                                                                                    title: "You get the idea...",
                                                                                },
                                                                            },
                                                                        },
                                                                    },
                                                                },
                                                            },
                                                        },
                                                    },
                                                },
                                            },
                                        },
                                    },
                                },
                            },
                        },
                    },
                ]}
            />
        </StoryContent>
    );
}

export function Misconfiguration() {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Handling Invalid Configurations</StoryHeading>
                <StoryParagraph>
                    Bad configurations will render and error component in place of the component.
                </StoryParagraph>
                <LayoutStory
                    layout={
                        [
                            {
                                $reactComponent: "unregistered_component",
                                $reactProps: "this is not valid",
                            },
                            null,
                            {
                                $reactComponent: "panel",
                                $reactProps: {
                                    title: "This component will render, but its child should not",
                                    children: [
                                        {
                                            $reactComponent: "unregistered_component",
                                            $reactProps: {
                                                title: "this is not valid",
                                            },
                                        },
                                    ],
                                },
                            },
                        ] as unknown as IHydratedLayoutWidget[]
                    }
                />
            </StoryContent>
        </>
    );
}

export function ErrorBoundaries() {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Error Boundaries</StoryHeading>
                <StoryParagraph>
                    All react components are wrapped in an error boundaries which prevent entire layouts from crashing.
                </StoryParagraph>

                <LayoutStory
                    layout={[
                        {
                            $reactComponent: "panel",
                            $reactProps: {
                                title: "I am a parent component",
                                children: [
                                    {
                                        $reactComponent: "panel",
                                        $reactProps: { title: "We are siblings" },
                                    },
                                    {
                                        $reactComponent: "panel",
                                        $reactProps: {
                                            title: "We are siblings",
                                            makeComponentFail: true, // This will force the component to call an undefined function
                                        },
                                    },
                                    {
                                        $reactComponent: "panel",
                                        $reactProps: { title: "We are siblings" },
                                    },
                                ],
                            },
                        },
                    ]}
                />
            </StoryContent>
        </>
    );
}

export function ExamplePageLayout() {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Example Layout</StoryHeading>
                <StoryParagraph>
                    This below sample should give you an idea of how this component can be used.
                </StoryParagraph>
            </StoryContent>
            <LayoutStory layout={sampleHomeLayout} />
        </>
    );
}

export function DeviceVisibility() {
    return (
        <>
            <StoryContent>
                <StoryHeading depth={1}>Device Specific Visibility</StoryHeading>
                <StoryParagraph>This story tests configuring a device with the visiblity middleware.</StoryParagraph>
            </StoryContent>
            <LayoutStory
                layout={[
                    {
                        $middleware: {
                            visibility: {
                                device: LayoutDevice.ALL,
                            },
                        },
                        $reactComponent: "panel",
                        $reactProps: {
                            title: "This Panel always renders",
                        },
                    },
                    {
                        $middleware: {
                            visibility: {
                                device: LayoutDevice.DESKTOP,
                            },
                        },
                        $reactComponent: "panel",
                        $reactProps: {
                            title: "This Panel renders on desktop only.",
                        },
                    },
                    {
                        $middleware: {
                            visibility: {
                                device: LayoutDevice.MOBILE,
                            },
                        },
                        $reactComponent: "panel",
                        $reactProps: {
                            title: "This Panel renders on mobile only.",
                        },
                    },
                ]}
            />
        </>
    );
}

DeviceVisibility.parameters = {
    chromatic: {
        viewports: [400, 1000],
    },
};
