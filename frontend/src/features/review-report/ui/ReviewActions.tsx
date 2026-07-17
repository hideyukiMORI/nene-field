import { useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Button, Field, InlineAlert, Stack, Textarea } from '@/shared/ui'
import { useReviewReport } from '../model/use-review-report'

const MAX_COMMENT = 1000

export function ReviewActions({ reportId }: { reportId: string }) {
  const { t } = useTranslation()
  const { approve, reject, isPending, errorKey } = useReviewReport(reportId)
  const [rejecting, setRejecting] = useState(false)
  const [comment, setComment] = useState('')
  const [commentError, setCommentError] = useState(false)

  const submitReject = (): void => {
    if (comment.trim() === '') {
      setCommentError(true)
      return
    }
    setCommentError(false)
    reject(comment.trim())
  }

  return (
    <Stack gap="md">
      {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}

      {rejecting ? (
        <Stack gap="sm">
          <Field
            label={t('report.review.commentLabel')}
            htmlFor="reject-comment"
            error={commentError ? t('report.review.commentRequired') : undefined}
          >
            <Textarea
              id="reject-comment"
              value={comment}
              maxLength={MAX_COMMENT}
              placeholder={t('report.review.commentPlaceholder')}
              onChange={(event) => {
                setComment(event.target.value)
              }}
            />
          </Field>
          <div className="flex gap-2">
            <Button variant="danger" onClick={submitReject} disabled={isPending}>
              {t('report.review.reject')}
            </Button>
            <Button
              variant="secondary"
              onClick={() => {
                setRejecting(false)
                setCommentError(false)
              }}
              disabled={isPending}
            >
              {t('common.actions.cancel')}
            </Button>
          </div>
        </Stack>
      ) : (
        <div className="flex gap-2">
          <Button
            onClick={() => {
              approve()
            }}
            disabled={isPending}
          >
            {t('report.review.approve')}
          </Button>
          <Button
            variant="secondary"
            onClick={() => {
              setRejecting(true)
            }}
            disabled={isPending}
          >
            {t('report.review.reject')}
          </Button>
        </div>
      )}
    </Stack>
  )
}
