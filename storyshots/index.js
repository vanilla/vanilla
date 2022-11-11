import initStoryshots from "@storybook/addon-storyshots";

initStoryshots({
    framework: "react",
    configPath: "./build/.storybook",
    test: ({ story, context }) => {
        story.render();
    },
});
