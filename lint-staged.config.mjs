import path from 'path';

/**
 * Convert the absolute paths of the staged files to relative paths inside the Docker container,
 * so that the linter can work properly inside of it.
 *
 * The downside of this approach is that the files with error messages will be shown with the paths inside the container
 * and IDE will not be able to open with a click through from the error messages itself.
 */
const localPathToRelativeInsideWpEnvContainer = (stagedFiles) => {
	return stagedFiles.map(filePath => path.relative(process.cwd(), filePath)).join(' ');
};

export default {
	'*.php': (stagedFiles) => `npm run lint -- ${localPathToRelativeInsideWpEnvContainer(stagedFiles)}`,
};
