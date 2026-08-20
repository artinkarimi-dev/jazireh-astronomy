import { useEffect } from 'react'
import { siteConfig } from '../config/site'

function setMeta(selector, attribute, value) {
  const element = document.querySelector(selector)
  if (!element) return () => {}
  const previous = element.getAttribute(attribute) || ''
  element.setAttribute(attribute, value)
  return () => element.setAttribute(attribute, previous)
}

export default function usePageMeta(title, description = siteConfig.description) {
  useEffect(() => {
    const previousTitle = document.title
    const fullTitle = title ? `${title} | ${siteConfig.fullName}` : siteConfig.fullName
    document.title = fullTitle

    const restore = [
      setMeta('meta[name="description"]', 'content', description),
      setMeta('meta[property="og:title"]', 'content', fullTitle),
      setMeta('meta[property="og:description"]', 'content', description)
    ]

    return () => {
      document.title = previousTitle
      restore.forEach((callback) => callback())
    }
  }, [description, title])
}
