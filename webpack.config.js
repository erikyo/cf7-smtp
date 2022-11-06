const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		'smtp-settings': path.resolve(
			process.cwd(),
			`src/cf7-smtp-settings.js`
		),
	},
};
