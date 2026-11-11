import js from '@eslint/js';
import globals from 'globals';

export default [
    { ignores: ['node_modules/**', 'vendor/**', 'public/**', 'storage/**', 'playwright-report/**', 'test-results/**'] },
    js.configs.recommended,
    {
        files: ['resources/js/**/*.js'],
        languageOptions: { globals: globals.browser },
    },
    {
        files: ['**/*.mjs', 'vite.config.js', 'playwright.config.js', 'eslint.config.js', 'tests/e2e/**/*.js', 'scripts/**/*.js'],
        languageOptions: { globals: globals.node },
    },
];
