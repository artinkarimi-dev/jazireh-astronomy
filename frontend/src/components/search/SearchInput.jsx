import { Search, X } from 'lucide-react'

export default function SearchInput({ value, onChange, onSubmit, loading = false }) {
  return (
    <form onSubmit={onSubmit} className="surface-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:p-5">
      <div className={`search-field min-w-0 flex-1 ${value ? 'has-clear' : ''}`}>
        <input
          value={value}
          onChange={(event) => onChange(event.target.value)}
          className="input-field search-input"
          placeholder="جستجو در اخبار، ویدیوها، تصویر روز، جزیره دیلی و اجرام..."
          aria-label="جستجوی سایت"
          autoComplete="off"
        />
        <Search className="search-icon h-4 w-4" />
        {value && (
          <button
            type="button"
            onClick={() => onChange('')}
            className="search-input-clear"
            aria-label="پاک کردن جستجو"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </div>
      <button type="submit" className="primary-btn sm:w-auto" disabled={loading}>
        <Search className="h-4 w-4" />
        {loading ? 'در حال جستجو' : 'جستجو'}
      </button>
    </form>
  )
}
