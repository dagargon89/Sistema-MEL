import js from "@eslint/js";
import globals from "globals";
import reactHooks from "eslint-plugin-react-hooks";
import reactRefresh from "eslint-plugin-react-refresh";
import tseslint from "typescript-eslint";

export default tseslint.config(
  { ignores: ["dist", "src/lib/mock/db.json"] },
  {
    extends: [js.configs.recommended, ...tseslint.configs.recommended],
    files: ["**/*.{ts,tsx}"],
    languageOptions: {
      ecmaVersion: 2022,
      globals: globals.browser,
    },
    plugins: {
      "react-hooks": reactHooks,
      "react-refresh": reactRefresh,
    },
    rules: {
      ...reactHooks.configs.recommended.rules,
      "react-refresh/only-export-components": ["warn", { allowConstantExport: true }],
      "@typescript-eslint/no-unused-vars": ["warn", { argsIgnorePattern: "^_" }],
      // Regla dura del design system (doc 08 §2.4 / §7): lima nunca lleva texto blanco.
      "no-restricted-syntax": [
        "error",
        {
          selector:
            "Literal[value=/(?=.*\\bbg-accent\\b)(?=.*\\btext-(white|inverse)\\b).*/]",
          message:
            "Prohibido: texto blanco/inverse sobre bg-accent (lima). Usa text-accent-text (doc 08 §2.4).",
        },
      ],
    },
  },
);
