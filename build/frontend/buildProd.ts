import webpack, { Stats } from "webpack";
import { makeProdConfig } from "./makeProdConfig";

void makeProdConfig().then(config => {
    const compiler = webpack(config);
    const logger = console;
    compiler.run((err: Error, stats: Stats) => {
        if (err) {
            logger.error("The build encountered an error:" + err);
        }

        logger.log(
            stats.toString({
                chunks: false, // Makes the build much quieter
                modules: false,
                colors: true, // Shows colors in the console
            }),
        );
    });
});
