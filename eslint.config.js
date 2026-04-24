import js from '@eslint/js';
import prettier from 'eslint-config-prettier/flat';
import vue from 'eslint-plugin-vue';
import globals from 'globals';

/** @type {import('eslint').Linter.Config[]} */
export default [
    js.configs.recommended,
    ...vue.configs['flat/recommended'],
    {
        languageOptions: {
            globals: { ...globals.browser },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
        },
    },
    { ignores: ['vendor', 'node_modules', 'public', 'bootstrap/ssr'] },
    prettier,
];
