# ADR 0009: Personal Data & Cross-Border AI Transmission (APPI)

## Status

accepted

## Context

Daily reports contain personal information under 個人情報保護法 (APPI): worker
names, locations, client names, and free-text that may describe third parties or
include health/incident details (要配慮個人情報). ADR 0007 already makes AI summary
opt-in and minimizes what is sent (report `body` only).

Two gaps remained:

1. **Cross-border transfer.** The default AI backend is an OpenAI-compatible API,
   which is typically hosted outside Japan. Sending personal data abroad triggers
   APPI §28 (越境移転) obligations, which ADR 0007 did not address.
2. **A consolidated APPI stance** — outsourcing supervision (§25), security control
   measures (§23), sensitive-information handling (§2-3), and data-subject rights
   (§32–35) — was scattered across requirements rather than stated as a decision.

The operator-confirmed posture is: keep AI opt-in, and **warn on cross-border
transfer** rather than hard-blocking it.

## Decision

1. **AI stays opt-in and disabled by default** (reaffirms ADR 0007). With AI off,
   no report text leaves the installation.
2. **Cross-border warning.** When the configured AI endpoint resolves to a service
   outside Japan, the admin settings UI **MUST** show a clear warning before AI can
   be enabled, stating that this is a §28 cross-border transfer and that meeting the
   §28 basis (data-subject consent or an adequate-system arrangement) plus the duty
   to provide information to the data subject is the **operator's** responsibility.
3. **Outsourcing supervision (§25).** External AI use is documented as outsourcing
   the handling of personal data; the operator supervises the processor.
4. **Sensitive information (§2-3).** No field requires 要配慮個人情報. The operator
   guide and template-design UI warn against collecting health/incident personal
   data without a lawful basis and consent.
5. **Security control measures (§23).** Tenant isolation, JWT RBAC (ADR 0004),
   authenticated-only file access, and bcrypt password hashing are the baseline.
6. **Data-subject rights (§32–35).** The operator can delete a user and associated
   personal data; deletion is an operator-initiated, audited action (reconciled with
   retention in ADR 0010).

## Consequences

- Operators using an overseas AI endpoint are explicitly informed of their §28
  duties before any data crosses a border; the product does not silently ship PII
  abroad.
- A future local/self-hosted AI backend (ADR 0007, Phase 4) removes the cross-border
  concern entirely.
- The admin settings UI gains a country/endpoint check and a warning state (Phase 3
  implementation).

## Related

- Issue: `#3`
- PR: `#000`
- Binding doc: `docs/explanation/legal-compliance.md` §4
- Related: ADR 0004 (multi-tenancy & roles), ADR 0007 (AI summary policy), ADR 0008, ADR 0010
- Supersedes: none
- Superseded by: none
