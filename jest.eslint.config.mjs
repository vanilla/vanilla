export default {
    runner: "jest-runner-eslint",
    displayName: "lint",
    testMatch: [
        "<rootDir>/library/src/scripts/**/*.ts",
        "<rootDir>/library/src/scripts/**/*.tsx",
        "<rootDir>/plugins/*/src/scripts/**/*.ts",
        "<rootDir>/plugins/*/src/scripts/**/*.tsx",
        "<rootDir>/applications/*/src/scripts/**/*.ts",
        "<rootDir>/applications/*/src/scripts/**/*.tsx",
    ],
};
