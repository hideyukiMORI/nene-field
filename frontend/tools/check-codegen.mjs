// Freshness gate for the generated OpenAPI types (Issue #146).
//
// Regenerates schema.gen.ts into a temp file and fails if it differs from the
// committed one — i.e. someone changed docs/openapi/openapi.yaml without running
// `npm run codegen`, or hand-edited the generated file.
//
// Why this exists: in #142 the contract advertised a `tags` filter the API never
// implemented, and that claim had been **copied into the generated types** as
// `listReports.parameters.query.tags?: string[]`. It was caught by a human running
// grep, not by a gate — every check was green while the generated file disagreed
// with reality. `composer openapi` (#147) now proves the contract is *valid*; this
// proves the generated client is *current with it*. Valid and current are different
// claims and need different gates.
//
// Placed in `check` rather than as a standalone CI step because regeneration here is
// deterministic and hermetic: no network, and the generator version is pinned by
// package-lock.json (measured — two consecutive runs are byte-identical). A gate that
// can go red for reasons unrelated to the change does not belong in `check`; this one
// cannot. The only way it reddens is the drift it exists to catch.
import { execFileSync } from 'node:child_process'
import { mkdtempSync, readFileSync, rmSync } from 'node:fs'
import { tmpdir } from 'node:os'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const dirname = path.dirname(fileURLToPath(import.meta.url))
const SPEC = path.resolve(dirname, '../../docs/openapi/openapi.yaml')
const COMMITTED = path.resolve(dirname, '../src/shared/api/schema.gen.ts')

const tmp = mkdtempSync(path.join(tmpdir(), 'nene-field-codegen-'))
const fresh = path.join(tmp, 'schema.gen.ts')

try {
  // Distinguish "the generator could not run" from "the output drifted". Without this
  // split, a contract that fails to parse exits non-zero with a raw Buffer dump from
  // execFileSync, which reads like a drift failure and sends the reader to the wrong
  // fix. Found while negative-testing this gate: an invalid-YAML mutation made it exit
  // 1 for the wrong reason, and the test looked like it had passed.
  try {
    execFileSync('openapi-typescript', [SPEC, '-o', fresh], { stdio: 'pipe' })
  } catch (error) {
    const detail = [error.stderr, error.stdout]
      .map((buffer) => buffer?.toString().trim())
      .filter(Boolean)
      .join('\n')
    console.error(
      'Could not generate types from docs/openapi/openapi.yaml — this is a broken contract,\n' +
        'not stale output. Fix the contract (`npm run openapi:lint` explains why), then re-run.\n' +
        (detail || String(error.message)),
    )
    process.exit(2)
  }

  if (readFileSync(fresh, 'utf8') !== readFileSync(COMMITTED, 'utf8')) {
    console.error(
      'schema.gen.ts is stale: it does not match what docs/openapi/openapi.yaml generates.\n' +
        'Run `npm run codegen` and commit the result.',
    )
    process.exit(1)
  }
} finally {
  rmSync(tmp, { recursive: true, force: true })
}
