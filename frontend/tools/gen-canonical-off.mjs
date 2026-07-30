/**
 * `eslint.canonical-off.js` の生成器（Issue #140・配布 config のゲート導入段）。
 *
 * hub 裁定 2026-07-30「全艦統一 A = canonical を **off 形**で展開」に対応する台帳を作る。
 * 手で列挙してはいけない（hub 条件①）ので、この生成器が唯一の作成手段。
 *
 *   使い方: frontend/ で `node tools/gen-canonical-off.mjs`
 *
 * なぜ runtime 導出（`Object.keys(canonical).map(r => [r, 'off'])`）にしないのか:
 * canonical が将来ルールを追加したとき、runtime 導出だと **新しい負債まで黙って off に
 * 吸収される**。生成物を凍結しておけば、追加分は未列挙のまま canonical の severity で
 * 発火し、差分としてレビューに出る。
 */
import { readFileSync, writeFileSync } from 'node:fs'
import nene2 from '@hideyukimori/nene2-standards'

// `exports` に './package.json' が無いので import 解決ではなく直読み。
const standardsVersion = JSON.parse(
  readFileSync('node_modules/@hideyukimori/nene2-standards/package.json', 'utf8'),
).version

// i18n 軸は展開しない（`jsx-a11y` の再定義衝突・fleet#189 摩擦2）ため台帳にも入れない。
const axes = {
  base: nene2.base,
  fsd: nene2.fsd,
  api: nene2.api,
  styling: nene2.stylingWith(),
}

/** rule id -> それを定義している軸名の集合 */
const owner = {}
for (const [axisName, blocks] of Object.entries(axes)) {
  for (const block of blocks) {
    for (const ruleId of Object.keys(block.rules ?? {})) {
      ;(owner[ruleId] ??= new Set()).add(axisName)
    }
  }
}

const ruleIds = Object.keys(owner).sort()

// prettier（singleQuote: true・quoteProps 既定 = as-needed）が出す形に合わせて書き出し、
// 生成物が `npm run format` を通る状態を保つ。ほとんどのルール id は `-` か `/` を含む
// のでクォートが要るが、`curly` / `indent` / `quotes` / `semi` のような core ルールは
// 識別子として妥当なので prettier はクォートを外す。
const isBareKey = (id) => /^[A-Za-z_$][A-Za-z0-9_$]*$/.test(id)
const entries = ruleIds
  .map((id) => {
    const key = isBareKey(id) ? id : `'${id}'`
    return `  ${key}: 'off', // ${[...owner[id]].sort().join('+')}`
  })
  .join('\n')

const header = [
  '// 自動生成物 — 手で編集しない。',
  '//',
  '// nene2-standards の canonical が定義する全ルールを off へ落とした台帳（Issue #140）。',
  '// hub 裁定 2026-07-30「全艦統一 A = canonical を off 形で展開」の実体。',
  '//',
  '// 負債は lint では出ない。conformance / gate-integrity が「実効 severity < canonical」を',
  '// red として報告し、週次 rollup で可視化する。enforce 昇格は、この台帳から行を削っていく作業。',
  '//',
  '// 生成コマンド（frontend/ で実行）: node tools/gen-canonical-off.mjs',
  `// 生成時点の nene2-standards: ${standardsVersion}`,
  `// 展開軸: ${Object.keys(axes).join(' / ')}（i18n は未展開＝fleet#189 摩擦2）`,
  `// ルール数: ${ruleIds.length}`,
  '',
  'export const canonicalOff = {',
].join('\n')

writeFileSync('eslint.canonical-off.js', `${header}\n${entries}\n}\n`)

const perAxis = Object.fromEntries(
  Object.entries(axes).map(([name, blocks]) => [
    name,
    new Set(blocks.flatMap((b) => Object.keys(b.rules ?? {}))).size,
  ]),
)
console.log(`eslint.canonical-off.js を生成: ${ruleIds.length} ルール`)
console.log(`軸別: ${JSON.stringify(perAxis)}`)
