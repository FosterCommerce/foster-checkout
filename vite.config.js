import { resolve } from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig({
	build: {
		emptyOutDir: true,
		lib: {
			entry: resolve(import.meta.dirname, 'src/web/assets/checkout/js/main.js'),
			fileName: () => 'alpine.js',
			formats: ['es'],
		},
		minify: false,
		outDir: 'src/web/assets/checkout/dist/js',
		rollupOptions: {
			output: {
				inlineDynamicImports: true,
			},
		},
		sourcemap: false,
	},
});
