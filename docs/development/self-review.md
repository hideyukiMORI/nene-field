# Self-Review Checklist

Run the relevant checklist before creating a PR.

## Backend API

- [ ] All new endpoints are in `docs/openapi/openapi.yaml`
- [ ] Every tenanted query includes `organization_id` in WHERE clause
- [ ] Role authorization is enforced in the handler or use case
- [ ] Audit event is recorded for every mutation (in same DB transaction)
- [ ] `composer check` is green (PHPUnit + PHPStan + CS-Fixer)
- [ ] No PII in API responses beyond what is documented
- [ ] Storage paths never appear in responses

## Report Lifecycle

- [ ] State transitions match `docs/explanation/domain-model.md`
- [ ] Approved reports cannot be edited
- [ ] Rejected → draft transition is correct
- [ ] Attachment upload only allowed on draft / rejected states

## AI Summary

- [ ] Only `body` is sent to AI API; no metadata
- [ ] AI call is wrapped in try/catch; submission does not fail on AI error
- [ ] `ai_summary_enabled` is checked before calling external API
- [ ] AuditEvent recorded when summary is generated or cleared

## Frontend

- [ ] Form works on 375px viewport (mobile)
- [ ] No fetch calls outside `src/shared/api/client.ts`
- [ ] `npm run check` is green

## Documentation

- [ ] New identifiers added to `docs/terms.md`
- [ ] New feature described in `docs/explanation/features.md`
- [ ] New page described in `docs/explanation/pages.md`
- [ ] Roadmap updated if phase changes
