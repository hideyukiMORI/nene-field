import { useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Button, Field, InlineAlert, Modal, Textarea } from '@/shared/ui'
import { useReviewReport } from '../model/use-review-report'

const MAX_COMMENT = 1000

export type ReviewMode = 'approve' | 'reject' | null

interface ReviewModalsProps {
  reportId: string
  /** Optional context shown for quick review (e.g. AI summary). */
  context?: string | null
  mode: ReviewMode
  onClose: () => void
  /** Called after a successful approve/reject (parent fires pulse/toast/advance). */
  onReviewed: (mode: 'approve' | 'reject') => void
}

/**
 * Approve / reject modals (design handoff §5.3). Approve takes an optional
 * comment; reject requires one (the submit button stays disabled until filled).
 */
export function ReviewModals({ reportId, context, mode, onClose, onReviewed }: ReviewModalsProps) {
  const { t } = useTranslation()
  const { approve, reject, isPending, errorKey } = useReviewReport(reportId)
  const [comment, setComment] = useState('')

  // Reset the comment when a new modal opens (React's "adjust state during
  // render on prop change" pattern — avoids a setState-in-effect).
  const openKey = `${mode ?? ''}-${reportId}`
  const [prevKey, setPrevKey] = useState(openKey)
  if (openKey !== prevKey) {
    setPrevKey(openKey)
    setComment('')
  }

  if (mode === null) return null

  const isReject = mode === 'reject'
  const trimmed = comment.trim()

  const submit = (): void => {
    if (isReject) {
      if (trimmed === '') return
      reject(trimmed, () => {
        onReviewed('reject')
      })
    } else {
      approve(trimmed === '' ? undefined : trimmed, () => {
        onReviewed('approve')
      })
    }
  }

  return (
    <Modal
      open
      onClose={onClose}
      title={t(isReject ? 'report.review.rejectTitle' : 'report.review.approveTitle')}
      closeLabel={t('common.actions.close')}
      footer={
        <>
          <Button variant="ghost" onClick={onClose} disabled={isPending}>
            {t('common.actions.cancel')}
          </Button>
          <Button
            variant={isReject ? 'danger' : 'success'}
            onClick={submit}
            disabled={isPending || (isReject && trimmed === '')}
          >
            {t(isReject ? 'report.review.reject' : 'report.review.approve')}
          </Button>
        </>
      }
    >
      <div className="flex flex-col gap-4">
        {errorKey !== null && <InlineAlert variant="error">{t(errorKey)}</InlineAlert>}
        {context !== null && context !== undefined && context !== '' && (
          <div className="rounded-input bg-ai-soft px-4 py-3 text-sm text-fg">
            <span className="text-xs font-semibold text-ai">AI</span>
            <p className="mt-1">{context}</p>
          </div>
        )}
        <Field
          label={t(isReject ? 'report.review.commentLabel' : 'report.review.commentOptional')}
          htmlFor="review-comment"
        >
          <Textarea
            id="review-comment"
            value={comment}
            maxLength={MAX_COMMENT}
            placeholder={isReject ? t('report.review.commentPlaceholder') : undefined}
            onChange={(event) => {
              setComment(event.target.value)
            }}
          />
        </Field>
      </div>
    </Modal>
  )
}
