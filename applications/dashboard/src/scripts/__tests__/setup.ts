/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

const dashboardTestsContext = (require as any).context("../", true, /.test.(ts|tsx)$/);
dashboardTestsContext.keys().forEach(dashboardTestsContext);
