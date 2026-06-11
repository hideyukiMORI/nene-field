import { useReportQuery, type ReportDetail } from '@/entities/report'

export interface ReportDetailState {
  report: ReportDetail | undefined
  isLoading: boolean
  isError: boolean
  isNotFound: boolean
  refetch: () => void
}

export function useReportDetail(id: string): ReportDetailState {
  const query = useReportQuery(id)

  return {
    report: query.data,
    isLoading: query.isLoading,
    isError: query.isError,
    isNotFound: query.error?.status === 404,
    refetch: () => {
      void query.refetch()
    },
  }
}
