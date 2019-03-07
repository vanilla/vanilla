require("ts-node").register({project: "build/tsconfig.json"});

module.exports = require("./tswebpack").default;