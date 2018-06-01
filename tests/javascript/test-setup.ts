// require all modules ending in "_test" from the
// current directory and all subdirectories
const dashboardTestsContext = (require as any).context(
    "../../applications/dashboard/src/scripts",
    true,
    /.test.(ts|tsx)$/,
);
dashboardTestsContext.keys().forEach(dashboardTestsContext);

const editorTestsContext = (require as any).context("../../plugins/rich-editor/src/scripts", true, /.test.(ts|tsx)$/);
editorTestsContext.keys().forEach(editorTestsContext);
