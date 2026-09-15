import WidgetState from './WidgetState'

export default function ObservatoryCard({
  eyebrow,
  title,
  description,
  status = 'error',
  message,
  updatedAt,
  source,
  sourceUrl,
  children,
  actions,
  className = '',
}) {
  return (
    <article className={`surface-card observatory-card p-5 sm:p-6 ${className}`}>
      <div className="observatory-card-head">
        <div>
          {eyebrow && <span className="eyebrow">{eyebrow}</span>}
          <h3 className="mt-2 card-title">{title}</h3>
          {description && <p className="mt-3 text-sm leading-7 text-slate-400">{description}</p>}
        </div>
        <WidgetState status={status} message={message} updatedAt={updatedAt} />
      </div>

      <div className="observatory-card-body">{children}</div>

      {(source || actions) && (
        <div className="observatory-card-footer">
          {sourceUrl ? (
            <a className="text-xs font-bold text-sky-200 transition hover:text-white" href={sourceUrl} target="_blank" rel="noreferrer">
              {source}
            </a>
          ) : (
            <span className="text-xs font-bold text-slate-500">{source}</span>
          )}
          {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
        </div>
      )}
    </article>
  )
}
