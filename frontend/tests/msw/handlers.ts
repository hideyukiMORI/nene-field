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
]
