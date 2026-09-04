import stylistic from "@stylistic/eslint-plugin";

export default [
	{
		ignores: ["vendor/**", "src/web/assets/checkout/dist/**", "vite.config.js"],
	},
	{
		files: ["src/web/assets/checkout/js/*.js"],
		plugins: {
			"@stylistic": stylistic,
		},
		rules: {
			curly: ["error", "all"],
			"id-length": ["error", { min: 2, exceptions: ["T"] }],
			"@stylistic/curly-newline": ["error", "always"],
			"@stylistic/indent": ["error", "tab"],
			"@stylistic/no-mixed-spaces-and-tabs": "error",
		},
	},
];
