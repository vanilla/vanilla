import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { addComponent } from "@library/utility/componentRegistry";
import { IWidgetContainerProps, WidgetContainer, widgetPropsResolver } from "@library/features/homepages/Widget";

export default {
    title: "Widgets/Widget Container",
    parameters: {
        chromatic: {
            viewports: [1400],
        },
    },
};

/**
 * A basic dummy component for story purposes.
 *
 * @param title
 * @constructor
 */
function Dummy({ title }: { title: string }) {
    return (
        <div
            style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                minHeight: 75,
                fontWeight: "bold",
                textTransform: "uppercase",
                fontSize: 16,
                margin: "8px",
                background: "rgba(255, 255, 255, .8)",
                borderRadius: 4,
                border: "solid 1px rgba(0, 0, 0, .2)",
            }}
        >
            {title}
        </div>
    );
}
addComponent("Dummy", Dummy);

function DummyContainer(props) {
    return (
        <div style={{ background: "rgba(0, 0, 0, .05)", padding: 8 }}>
            <div style={{ fontSize: 12, textTransform: "uppercase" }}>Container</div>
            {props.children}
        </div>
    );
}
addComponent("DummyContainer", DummyContainer);

function ThreePanels(props) {
    return (
        <div style={{ display: "flex", justifyContent: "space-between" }}>
            <aside style={{ flex: 1 }}>{props.left}</aside>
            <main style={{ flex: 2 }}>{props.main}</main>
            <aside style={{ flex: 1 }}>{props.right}</aside>
        </div>
    );
}
addComponent("ThreePanels", ThreePanels, {
    widgetResolver: widgetPropsResolver(["left", "right", "main"]),
});

function WidgetContainerStory(props: IWidgetContainerProps) {
    const source = JSON.stringify(props, null, 2);

    return (
        <>
            <StoryHeading depth={3}>Source</StoryHeading>
            <pre style={{ margin: "8px 0 24px", padding: 16, background: "#efefef" }}>
                <code>{source}</code>
            </pre>
            <StoryHeading depth={3}>Result</StoryHeading>
            <div style={{ marginBottom: 16 }}>
                <WidgetContainer {...props} />
            </div>
        </>
    );
}

export function BasicExample() {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Widget Containers</StoryHeading>
            <StoryParagraph>
                The <code>WidgetContainer</code> component lets you mount dynamic components based on a JSON object that
                defines the components and their props. The purpose of this is to be able to store custom component
                layouts on the server and then fetch them via API.
            </StoryParagraph>
            <StoryParagraph>
                The basic component specification has a <code>components</code> array where you list the components that
                will be displayed.
            </StoryParagraph>
            <StoryParagraph>
                Each component has a <code>$type</code> property that is the name of the component that was registered
                using <code>addComponent()</code>. The rest of the properties get passed along to the component as
                props.
            </StoryParagraph>
            <StoryHeading depth={2}>Basic Example</StoryHeading>
            <StoryParagraph>This example renders two components, each with different props.</StoryParagraph>
            <WidgetContainerStory
                components={[
                    { $type: "Dummy", title: "Hello World" },
                    { $type: "Dummy", title: "This is a Title" },
                ]}
            />
        </StoryContent>
    );
}

export function NestedComponents() {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Nesting Components</StoryHeading>
            <StoryParagraph>
                If you want to render a component that has child components itself you can specify the children in the
                <code>children</code> property, just like react. This property is an array of widget options itself. It
                takes the exact same form as the main <code>components</code> array.
            </StoryParagraph>
            <StoryHeading depth={2}>Basic Component Nesting</StoryHeading>
            <StoryParagraph>This is example shows a component nested inside a container.</StoryParagraph>
            <WidgetContainerStory
                components={[{ $type: "DummyContainer", children: [{ $type: "Dummy", title: "Nested" }] }]}
            />
            <StoryHeading depth={2}>Deeper Component Nesting</StoryHeading>
            <StoryParagraph>
                You can nest components as deeply as you want, just by continually specifying their children with the{" "}
                <code>components</code> property.
            </StoryParagraph>
            <WidgetContainerStory
                components={[
                    {
                        $type: "DummyContainer",
                        children: [
                            { $type: "Dummy", title: "Nested 1 Level" },
                            {
                                $type: "DummyContainer",
                                children: [{ $type: "Dummy", title: "Nested 2 Levels" }],
                            },
                            {
                                $type: "DummyContainer",
                                children: [
                                    {
                                        $type: "DummyContainer",
                                        children: [{ $type: "Dummy", title: "Nested 3 Levels" }],
                                    },
                                ],
                            },
                        ],
                    },
                ]}
            />
            <StoryParagraph>
                Providing clever utility containers are the key to making our system flexible for themers.
            </StoryParagraph>
        </StoryContent>
    );
}

export function CustomResolvers() {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Custom Component Resolvers</StoryHeading>
            <StoryParagraph>
                By default, the widget container can only recognize the default <code>children</code> components.
                However, React supports passing components to any property. If you want to support component properties
                then you will need to add your own resolver using the <code>widgetResolver</code> mount option when
                calling <code>addComponent()</code>.
            </StoryParagraph>
            <StoryHeading depth={2}>Custom Resolver Example</StoryHeading>
            <StoryParagraph>
                In this example, lets say we have a three panel layout component where the content of each panel is
                defined by a <code>left</code>, <code>right</code>, and <code>main</code> props.
            </StoryParagraph>
            <WidgetContainerStory
                components={[
                    {
                        $type: "ThreePanels",
                        left: { $type: "Dummy", title: "Left" },
                        right: { $type: "Dummy", title: "Right" },
                        main: { $type: "DummyContainer", children: { $type: "Dummy", title: "Main" } },
                    },
                ]}
            />
            <StoryParagraph>
                Heads up! This example also has some custom code in the <code>addComponent()</code> code. Check out the
                source to see the full picture.
            </StoryParagraph>
            <StoryParagraph>
                Notice in the above example that the left, right, and main specify just a single widget instead of an
                array. The <code>resolveComponents()</code> function can take a single component as well as an array of
                them.
            </StoryParagraph>
        </StoryContent>
    );
}
