// For a detailed explanation regarding each configuration property, visit:
// https://jestjs.io/docs/en/configuration.html

// @ts-check

const fs = require("fs");
const path = require("path");
const glob = require("glob");

const VANILLA_ROOT = __dirname;

let addonRootDirs = [];
let addonModuleDirs = [path.join(VANILLA_ROOT, "node_modules"), path.join(VANILLA_ROOT, "library/node_modules")];
let addonModuleMaps = {
    [`^@library/(.*)$`]: path.join(VANILLA_ROOT, "library/src/scripts/$1"),
};
let packageDirectoryMaps = {};

function scanAddons(addonDir) {
    const keys = glob
        .sync(path.join(VANILLA_ROOT, addonDir + "/*"))
        .map(dir => dir.replace(path.join(VANILLA_ROOT, addonDir + "/"), ""));

    keys.forEach(key => {
        let root = path.join(VANILLA_ROOT, addonDir, key);
        if (fs.existsSync(path.join(root, "src/scripts/entries")) && key !== "vanilla") {
            addonRootDirs.push(root);
            const nodeModules = path.join(root, "node_modules");
            addonModuleDirs.push(nodeModules);
            addonModuleMaps[`^@${key}/(.*)$`] = path.join(VANILLA_ROOT, addonDir, key, "src/scripts/$1");
        }
    });
}

function scanPackages(packagesDir) {
    const keys = glob
    // Get the directory contents
    .sync(path.join(VANILLA_ROOT, packagesDir + "/*"))
    // Filter out any files
    .filter((path) => fs.lstatSync(path).isDirectory())
    // Rectify the paths
    .map(dir => dir.replace(path.join(VANILLA_ROOT, packagesDir + "/"), ""));

    keys.forEach((key) => {
        // Split into groups of prefix and package name
        const nameArray = key.split(new RegExp("(vanilla).(.*)")).filter((group) => group.length);
        packageDirectoryMaps[`^@${nameArray[0]}/${nameArray[1]}/(.*)$`] = path.join(VANILLA_ROOT, packagesDir,key, "$1");
    });
}

scanAddons("applications");
scanAddons("plugins");
scanPackages("packages");

module.exports = {
    // All imported modules in your tests should be mocked automatically
    // automock: false,

    // Stop running tests after `n` failures
    // bail: 0,

    // Respect "browser" field in package.json when resolving modules
    // browser: false,

    // The directory where Jest should store its cached dependency information
    // cacheDirectory: "/private/var/folders/rn/fk19b1_j6ljgc45hvg4pp5400000gp/T/jest_dy",

    // Automatically clear mock calls and instances between every test
    clearMocks: true,

    // Indicates whether the coverage information should be collected while executing the test
    collectCoverage: true,

    // An array of glob patterns indicating a set of files for which coverage information should be collected
    collectCoverageFrom: ['<rootDir>/**/*.tsx', '<rootDir>/**/*.ts', '!<rootDir>/**/*.d.ts'],

    // The directory where Jest should output its coverage files
    coverageDirectory: "coverage/jest",

    // An array of regexp pattern strings used to skip coverage collection
    coveragePathIgnorePatterns: [
      ".github",
      ".vscode",
      ".yarn",
      "/build",
      "/node_modules/",
      "/cache/",
      "/vendor/"
    ],

    // A list of reporter names that Jest uses when writing coverage reports
    coverageReporters: [
    //   "json",
    //   "text",
      "lcov",
    //   "clover"
    ],

    // An object that configures minimum threshold enforcement for coverage results
    // coverageThreshold: null,

    // A path to a custom dependency extractor
    // dependencyExtractor: null,

    // Make calling deprecated APIs throw helpful error messages
    // errorOnDeprecated: false,

    // Force coverage collection from ignored files using an array of glob patterns
    // forceCoverageMatch: [],

    // A path to a module which exports an async function that is triggered once before all test suites
    // globalSetup: null,

    // A path to a module which exports an async function that is triggered once after all test suites
    // globalTeardown: null,

    // A set of global variables that need to be available in all test environments
    // globals: {},

    // The maximum amount of workers used to run your tests. Can be specified as % or a number. E.g. maxWorkers: 10% will use 10% of your CPU amount + 1 as the maximum worker number. maxWorkers: 2 will use a maximum of 2 workers.
    // maxWorkers: "50%",

    // // An array of directory names to be searched recursively up from the requiring module's location
    // moduleDirectories: ["node_modules", ...addonModuleDirs],

    // An array of file extensions your modules use
    moduleFileExtensions: [
        "js",
        "cjs",
        "json",
        "jsx",
        "ts",
        "tsx",
        "node"
    ],

    // A map from regular expressions to module names that allow to stub out resources with a single module
    moduleNameMapper: {
        "\\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga)$":
            "<rootDir>/library/src/scripts/__tests__/fileMock.js",
        "\\.(css|less|scss)$": "<rootDir>/library/src/scripts/__tests__/styleMock.js",
        ...packageDirectoryMaps,
        ...addonModuleMaps,
    },

    // An array of regexp pattern strings, matched against all module paths before considered 'visible' to the module loader
    // modulePathIgnorePatterns: [],

    // Activates notifications for test results
    // notify: false,

    // An enum that specifies notification mode. Requires { notify: true }
    // notifyMode: "failure-change",

    // A preset that is used as a base for Jest's configuration
    // preset: null,

    // Run tests from one or more projects
    // projects: null,

    // Use this configuration option to add custom reporters to Jest
    // reporters: undefined,

    // Automatically reset mock state between every test
    // resetMocks: false,

    // Reset the module registry before running each individual test
    // resetModules: false,

    // A path to a custom resolver
    // resolver: null,

    // Automatically restore mock state between every test
    // restoreMocks: false,

    // The root directory that Jest should scan for tests and modules within
    rootDir: VANILLA_ROOT,

    // A list of paths to directories that Jest should use to search for files in
    // roots: ["<rootDir>", ...addonRootDirs],

    // Allows you to use a custom runner instead of Jest's default test runner
    // runner: "jest-runner",

    // The paths to modules that run some code to configure or set up the testing environment before each test
    // setupFiles: [],

    // A list of paths to modules that run some code to configure or set up the testing framework before each test
    setupFilesAfterEnv: [
        "<rootDir>/jest.setup.js"
    ],

    // A list of paths to snapshot serializer modules Jest should use for snapshot testing
    // snapshotSerializers: [],

    // The test environment that will be used for testing
    testEnvironment: "jest-environment-jsdom",

    // Options that will be passed to the testEnvironment
    // testEnvironmentOptions: {},

    // Adds a location field to test results
    // testLocationInResults: false,

    // The glob patterns Jest uses to detect test files
    testMatch: [
        // "**/__tests__/**/*.[jt]s?(x)",
        "**/?(*.)+(spec).[tj]s?(x)",
    ],

    // An array of regexp pattern strings that are matched against all test paths, matched tests are skipped
    // testPathIgnorePatterns: ["/cloud/"],

    // The regexp pattern or array of patterns that Jest uses to detect test files
    // testRegex: [],

    // This option allows the use of a custom results processor
    // testResultsProcessor: null,

    // This option allows use of a custom test runner
    // testRunner: "jasmine2",

    // This option sets the URL for the jsdom environment. It is reflected in properties such as location.href
    // testURL: "http://localhost",

    // Setting this value to "fake" allows the use of fake timers for functions such as "setTimeout"
    // timers: "real",

    // A map from regular expressions to paths to transformers
    // transform: null,

    // An array of regexp pattern strings that are matched against all source file paths, matched files will skip transformation
    transformIgnorePatterns: [
      "/node_modules/"
    ],

    // An array of regexp pattern strings that are matched against all modules before the module loader will automatically return a mock for them
    // unmockedModulePathPatterns: undefined,

    // Indicates whether each individual test should be reported during the run
    // verbose: null,

    // An array of regexp patterns that are matched against all source file paths before re-running tests in watch mode
    // watchPathIgnorePatterns: [],

    // Whether to use watchman for file crawling
    // watchman: true,
};
