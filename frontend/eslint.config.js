import nene2 from '@hideyukimori/nene2-standards'
import js from '@eslint/js'
import eslintConfigPrettier from 'eslint-config-prettier'
import importPlugin from 'eslint-plugin-import'
import jsxA11y from 'eslint-plugin-jsx-a11y'
import reactHooks from 'eslint-plugin-react-hooks'
import reactRefresh from 'eslint-plugin-react-refresh'
import globals from 'globals'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import tseslint from 'typescript-eslint'
import { canonicalOff } from './eslint.canonical-off.js'

const dirname = path.dirname(fileURLToPath(import.meta.url))

// Entity internals are private to the slice — only entities/{r}/index.ts is public.
const entityInternalFiles = [
  './src/entities/*/api-types.ts',
  './src/entities/*/mapper.ts',
  './src/entities/*/queries.ts',
  './src/entities/*/mutations.ts',
  './src/entities/*/query-keys.ts',
  './src/entities/*/ids.ts',
  './src/entities/*/model.ts',
  './src/entities/*/enum.ts',
  './src/entities/*/session.ts',
]

// Hard dependency direction (frontend-standards §3): no upward arrows.
//
// `app/` gets the entity-internal zone but NOT the `shared/api` one: it legitimately
// owns transport-adjacent wiring (`auth-gate.tsx` reads `hasAuthToken`, `providers.tsx`
// maps `AppError`), unlike features/pages. Added with #149 — the shells used to live in
// an unlisted `widgets/` layer, which put them outside every zone and let two
// entity-internal imports through (`@/entities/auth/{enum,session}`). Layers absent from
// this list are not "clean", they are unmeasured; keep every `src/` layer represented.
const importZones = [
  { target: './src/app', from: entityInternalFiles },
  { target: './src/features', from: entityInternalFiles },
  { target: './src/features', from: './src/shared/api' },
  { target: './src/pages', from: entityInternalFiles },
  { target: './src/pages', from: './src/shared/api' },
  { target: './src/shared/ui', from: './src/entities' },
  { target: './src/shared/ui', from: './src/features' },
  { target: './src/shared/ui', from: './src/shared/api' },
]

export default tseslint.config(
  {
    ignores: [
      'dist',
      'node_modules',
      'coverage',
      'storybook-static',
      'playwright-report',
      'test-results',
      'src/shared/api/schema.gen.ts',
      'eslint.canonical-off.js',
      // Local screenshot scratch scripts. `.gitignore:10` already declares these debris,
      // but ESLint does not read .gitignore, so leftovers in a working tree fail
      // `npm run check` with parser errors while CI (clean checkout) stays green — the
      // gate disagrees with itself depending on who runs it. Keep the two lists in sync.
      'shot*.mjs',
    ],
  },

  // ───────────────────────────────────────────────────────────────────────────
  // フリート統一規約の配布 config（@hideyukimori/nene2-standards）— Issue #140
  //
  // 導入段は hub 裁定 2026-07-30「全艦統一 A = canonical を **off 形**で展開」に従う。
  // canonical のルールは下の台帳で一律 off になるので、この配線は **今日の lint 結果を
  // 1 件も変えない**。変わるのは conformance / gate-integrity が field を
  // 「測れない（unknown）」から「測れる（red）」へ動かすこと。負債は lint ではなく
  // conformance の red と週次 rollup で見える。enforce 昇格は台帳から行を削る作業。
  //
  // ⚠️ i18n 軸は展開しない。canonical の `nene2/i18n/jsx-a11y-strict` が `jsx-a11y`
  //    プラグインを再定義し、field の自前登録（下のブロック）と衝突して config 自体が
  //    読めなくなる（fleet#189 摩擦2・field で exit 2 を実測）。
  //
  // ⚠️ canonical を **field 自身のブロックより前**に置いている。flat config は後勝ちなので、
  //    この順序だと field 固有のゲート（Tailwind arbitrary value 禁止・import ゾーン・
  //    restrict-template-expressions の allowNumber など）が canonical に置換されない。
  //    canonical を後ろに置くと、ルール単位の後勝ちで **field の自前パターンが黙って失効する**
  //    （fleet#189 摩擦6。field は `no-restricted-syntax` に自前 1 パターンを持つ）。
  // ───────────────────────────────────────────────────────────────────────────
  ...nene2.base,
  ...nene2.fsd,
  ...nene2.api,
  ...nene2.stylingWith(),
  {
    name: 'nene2/introduction-off-baseline',
    // 生成コマンド（frontend/ で実行）: node tools/gen-canonical-off.mjs
    // 手で列挙しない（hub 条件①）。台帳の性質・凍結する理由は生成器の冒頭コメント参照。
    rules: canonicalOff,
  },

  {
    extends: [js.configs.recommended, ...tseslint.configs.strictTypeChecked],
    files: ['src/**/*.{ts,tsx}', 'tests/**/*.{ts,tsx}'],
    languageOptions: {
      ecmaVersion: 2023,
      globals: globals.browser,
      parserOptions: {
        // `project` は置かない。canonical（`nene2/base/options`）が `projectService: true`
        // を全ファイルに宣言しており、両者が同じファイルに当たると typescript-eslint が
        // `Enabling "project" does nothing when "projectService" is enabled` を返して
        // src 配下が解析段で全滅する（fleet#189 摩擦4・field 固有）。型情報は
        // projectService 経由で取る。
        //
        // ⚠️ 下の `settings['import/resolver'].typescript.project` は
        //    eslint-import-resolver-typescript の設定で、これとは無関係。外すと
        //    import 解決が壊れるので残す。
        tsconfigRootDir: dirname,
      },
    },
    plugins: {
      'react-hooks': reactHooks,
      'react-refresh': reactRefresh,
      'jsx-a11y': jsxA11y,
      import: importPlugin,
    },
    settings: {
      'import/resolver': {
        typescript: { project: './tsconfig.app.json' },
      },
    },
    rules: {
      ...reactHooks.configs.recommended.rules,
      'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
      ...jsxA11y.configs.recommended.rules,
      '@typescript-eslint/restrict-template-expressions': ['error', { allowNumber: true }],
      'import/no-restricted-paths': ['error', { zones: importZones }],
      'no-restricted-syntax': [
        'error',
        {
          selector: 'JSXAttribute[name.name="className"] Literal[value=/\\[.*\\]/]',
          message: 'Tailwind arbitrary values are forbidden outside shared/ui/theme.',
        },
      ],
    },
  },
  {
    // Tests and test helpers may inspect internals across layer boundaries.
    files: ['**/*.test.ts', '**/*.test.tsx', 'tests/**/*.{ts,tsx}'],
    rules: {
      '@typescript-eslint/no-unsafe-call': 'off',
      '@typescript-eslint/no-unsafe-member-access': 'off',
      '@typescript-eslint/no-unsafe-argument': 'off',
      '@typescript-eslint/no-unsafe-assignment': 'off',
      'import/no-restricted-paths': 'off',
      'react-refresh/only-export-components': 'off',
    },
  },
  {
    // Stories are a catalogue, not app code: the default export is the Storybook
    // meta object, and the named exports are story definitions rather than
    // components, so the Fast Refresh rule does not apply.
    files: ['src/**/*.stories.tsx'],
    rules: {
      'react-refresh/only-export-components': 'off',
    },
  },
  {
    // Playwright specs and the Storybook config live outside the app tsconfig
    // project. Lint them without type information rather than widening the app
    // project to cover tooling.
    //
    // `projectService: false` is not strictly required here — these are `.ts` and
    // `tsconfig.node.json` already includes them, so canonical's `projectService`
    // does resolve them (measured). It is set to keep the stated intent above true
    // after canonical is wired, and so that a spec added outside that `include`
    // does not start failing at parse time (fleet#189 摩擦5).
    files: ['e2e/**/*.ts', '.storybook/**/*.ts'],
    extends: [js.configs.recommended, ...tseslint.configs.recommended],
    languageOptions: {
      ecmaVersion: 2023,
      globals: { ...globals.browser, ...globals.node },
      parserOptions: { projectService: false },
    },
  },
  {
    // `projectService: false` **is** required for the JavaScript entries here.
    // No tsconfig sets `allowJs` and the root `tsconfig.json` is a solution file
    // (`files: []` + references), so a `.js` / `.mjs` file cannot belong to any TS
    // program. Under canonical's global `projectService: true` they therefore fail
    // at parse time with `not found by the project service` (measured: dropping
    // this line puts `eslint.config.js` and `tools/*.mjs` back to 2 parse errors).
    files: ['vite.config.ts', 'vitest.config.ts', 'eslint.config.js', 'playwright.config.ts'],
    extends: [js.configs.recommended, ...tseslint.configs.recommended],
    languageOptions: {
      ecmaVersion: 2023,
      globals: { ...globals.browser, ...globals.node },
      parserOptions: { projectService: false },
    },
  },
  {
    // The canonical-off generator is tooling, not app code — and being `.mjs` it
    // hits the same project-service limitation as the block above.
    files: ['tools/**/*.mjs'],
    extends: [js.configs.recommended, ...tseslint.configs.recommended],
    languageOptions: {
      ecmaVersion: 2023,
      globals: { ...globals.node },
      parserOptions: { projectService: false },
    },
  },
  eslintConfigPrettier,
)
