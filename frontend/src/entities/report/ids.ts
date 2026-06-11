declare const reportIdBrand: unique symbol

export type ReportId = string & { readonly [reportIdBrand]: 'ReportId' }

export function toReportId(value: string): ReportId {
  return value as ReportId
}
