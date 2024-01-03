const { DefaultReporter } = require("@jest/reporters");

class Reporter extends DefaultReporter {
    constructor() {
        super(...arguments);
    }

    /**
     * Custom reporting that prevents the console output from being printed when a test passes.
     */
    printTestFileHeader(_testPath, config, result) {
        const console = result.console;

        if (result.numFailingTests === 0 && !result.testExecError) {
            result.console = null;
        }

        super.printTestFileHeader(...arguments);

        result.console = console;
    }
}

module.exports = Reporter;
