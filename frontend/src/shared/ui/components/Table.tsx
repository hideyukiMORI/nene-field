import type { HTMLAttributes, ReactNode, TdHTMLAttributes, ThHTMLAttributes } from 'react'
import { cn } from '@/shared/lib/cn'

/**
 * Thin table primitives (design handoff §3.6). Fixed layout; the consumer sets a
 * min-width on <Table> and wraps in <TableWrap> for horizontal scroll on narrow
 * viewports. Clickable rows pass `interactive` for hover/active row tints.
 */

export function TableWrap({ children }: { children: ReactNode }) {
  return <div className="overflow-x-auto">{children}</div>
}

export function Table({ className, children, ...rest }: HTMLAttributes<HTMLTableElement>) {
  return (
    <table className={cn('w-full table-fixed text-sm', className)} {...rest}>
      {children}
    </table>
  )
}

export function Th({ className, children, ...rest }: ThHTMLAttributes<HTMLTableCellElement>) {
  return (
    <th
      className={cn(
        'border-b border-border px-3 py-2.5 text-left text-xs font-semibold tracking-wide text-fg-faint',
        className,
      )}
      {...rest}
    >
      {children}
    </th>
  )
}

interface TrProps extends HTMLAttributes<HTMLTableRowElement> {
  interactive?: boolean
  selected?: boolean
}

export function Tr({
  interactive = false,
  selected = false,
  className,
  children,
  ...rest
}: TrProps) {
  return (
    <tr
      className={cn(
        'border-b border-border-2',
        selected && 'bg-row-hover',
        interactive && 'cursor-pointer hover:bg-row-hover active:bg-row-active',
        className,
      )}
      {...rest}
    >
      {children}
    </tr>
  )
}

export function Td({ className, children, ...rest }: TdHTMLAttributes<HTMLTableCellElement>) {
  return (
    <td className={cn('px-3 py-2.5 align-middle', className)} {...rest}>
      {children}
    </td>
  )
}
