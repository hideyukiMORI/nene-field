import { useReportListQuery, type ReportSummary } from '@/entities/report'

export interface ListReportsState {
  reports: ReportSummary[]
  total: number
  isLoading: boolean
  isError: boolean
  refetch: () => void
}

/** Reports list workflow. v1 lists the first page; filters land in a follow-up. */
export function useListReports(): ListReportsState {
  const query = useReportListQuery({ limit: 20, offset: 0 })

  return {
    reports: query.data?.items ?? [],
    total: query.data?.total ?? 0,
    isLoading: query.isLoading,
    isError: query.isError,
    refetch: () => {
      void query.refetch()
    },
  }
}
