export const organizationKeys = {
  all: ['organizations'] as const,
  detail: (id: string) => ['organizations', 'detail', id] as const,
}
