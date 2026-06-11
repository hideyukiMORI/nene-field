declare const userIdBrand: unique symbol

/** Branded user id — no bare string for resource ids across layers (§7). */
export type UserId = string & { readonly [userIdBrand]: 'UserId' }

export function toUserId(value: string): UserId {
  return value as UserId
}
