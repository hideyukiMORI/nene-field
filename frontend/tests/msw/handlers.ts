import { http, HttpResponse } from 'msw'

const BASE = 'https://nene-field.dev/problems'

function user(email: string, role = 'admin') {
  return {
    user_id: 'u-1',
    organization_id: 'org-1',
    name: '管理者',
    email,
    role,
    is_active: true,
    created_at: '2026-06-01 00:00:00',
    updated_at: '2026-06-01 00:00:00',
  }
}

export function reportDetail(status = 'submitted') {
  return {
    report_id: 'r-1',
    organization_id: 'org-1',
    user_id: 'u-1',
    user_name: '田中太郎',
    title: '現場A 報告',
    body: '本文です',
    work_date: '2026-06-11',
    status,
    tags: [],
    submitted_at: '2026-06-11 09:00:00',
    attachments: [
      {
        attachment_id: 'a-1',
        filename: 'photo.png',
        mime_type: 'image/png',
        file_size: 2048,
        sha256: 'abc',
        created_at: '2026-06-11 08:30:00',
      },
    ],
    created_at: '2026-06-11 08:00:00',
    updated_at: '2026-06-11 08:00:00',
  }
}

export function userDto(overrides: Partial<Record<string, unknown>> = {}) {
  return {
    user_id: 'u-1',
    organization_id: 'org-1',
    name: '田中太郎',
    email: 'tanaka@example.com',
    role: 'approver',
    is_active: true,
    created_at: '2026-06-01 00:00:00',
    updated_at: '2026-06-01 00:00:00',
    ...overrides,
  }
}

export function auditEventDto(overrides: Partial<Record<string, unknown>> = {}) {
  return {
    event_id: 'e-1',
    organization_id: 'org-1',
    entity_type: 'Report',
    entity_id: 'r-1',
    event_name: 'report.approved',
    actor_id: 'u-1',
    actor_name: '管理者',
    before: { status: 'submitted' },
    after: { status: 'approved' },
    request_id: 'req-1',
    occurred_at: '2026-06-11 10:00:00',
    ...overrides,
  }
}

export function templateDto() {
  return {
    template_id: 't-1',
    organization_id: 'org-1',
    name: '日報（標準）',
    description: '標準テンプレート',
    fields: [
      { name: 'summary', label: '作業内容', type: 'textarea', required: true },
      { name: 'weather', label: '天候', type: 'select', required: false, options: ['晴れ', '雨'] },
    ],
    is_default: true,
    created_at: '2026-06-01 00:00:00',
    updated_at: '2026-06-01 00:00:00',
  }
}

/** Default handlers mirroring the OpenAPI contract for the auth + reports slice. */
export const handlers = [
  http.post('/auth/login', async ({ request }) => {
    const body = (await request.json()) as { email: string; password: string }
    if (body.password === 'wrong') {
      return HttpResponse.json(
        { type: `${BASE}/unauthorized`, title: 'Unauthorized' },
        { status: 401 },
      )
    }
    return HttpResponse.json({ token: 'test-jwt', user: user(body.email) })
  }),

  http.get('/reports', () =>
    HttpResponse.json({
      items: [
        {
          report_id: 'r-1',
          user_id: 'u-1',
          user_name: '田中太郎',
          title: '現場A 報告',
          work_date: '2026-06-11',
          status: 'submitted',
          created_at: '2026-06-11 08:00:00',
        },
      ],
      limit: 20,
      offset: 0,
      total: 1,
    }),
  ),

  http.post('/reports', () => HttpResponse.json(reportDetail('draft'), { status: 201 })),

  http.get('/templates', () => HttpResponse.json({ items: [templateDto()] })),

  http.post('/templates', () => HttpResponse.json(templateDto(), { status: 201 })),

  http.get('/templates/:id', () => HttpResponse.json(templateDto())),

  http.put('/templates/:id', () => HttpResponse.json(templateDto())),

  http.delete('/templates/:id', () => new HttpResponse(null, { status: 204 })),

  http.get('/users', () =>
    HttpResponse.json({
      items: [
        userDto({
          user_id: 'u-1',
          name: '田中太郎',
          email: 'tanaka@example.com',
          role: 'approver',
        }),
        userDto({ user_id: 'u-2', name: '佐藤花子', email: 'sato@example.com', role: 'admin' }),
      ],
      limit: 100,
      offset: 0,
      total: 2,
    }),
  ),

  http.post('/users', () => HttpResponse.json(userDto({ user_id: 'u-new' }), { status: 201 })),

  http.get('/users/:id', () => HttpResponse.json(userDto())),

  http.put('/users/:id', () => HttpResponse.json(userDto())),

  http.delete('/users/:id', () => new HttpResponse(null, { status: 204 })),

  http.get('/audit-events', () =>
    HttpResponse.json({
      items: [
        auditEventDto({ event_id: 'e-1', event_name: 'report.approved', entity_type: 'Report' }),
        auditEventDto({ event_id: 'e-2', event_name: 'user.created', entity_type: 'User' }),
      ],
      limit: 20,
      offset: 0,
      total: 2,
    }),
  ),

  http.get('/export/csv', () =>
    HttpResponse.arrayBuffer(new TextEncoder().encode('﻿report_id\r\n').buffer, {
      headers: { 'Content-Type': 'text/csv; charset=utf-8' },
    }),
  ),

  http.get('/audit-events/export', () =>
    HttpResponse.arrayBuffer(new TextEncoder().encode('﻿event_id\r\n').buffer, {
      headers: { 'Content-Type': 'text/csv; charset=utf-8' },
    }),
  ),

  http.get('/reports/:id', () => HttpResponse.json(reportDetail())),

  http.post('/reports/:id/submit', () => HttpResponse.json(reportDetail('submitted'))),

  http.post('/reports/:id/approve', () => HttpResponse.json(reportDetail('approved'))),

  http.post('/reports/:id/reject', () => HttpResponse.json(reportDetail('rejected'))),

  http.get('/reports/:id/attachments/:attachmentId', () =>
    HttpResponse.arrayBuffer(new TextEncoder().encode('binary').buffer, {
      headers: { 'Content-Type': 'image/png' },
    }),
  ),
]
