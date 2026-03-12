import stylistic from "@stylistic/eslint-plugin";

export default [
	{
		ignores: ["vendor/**"],
	},
	{
		files: ["src/web/assets/checkout/dist/js/alpine.js"],
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
