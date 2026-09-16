import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'

const appSource = await readFile(new URL('../src/App.jsx', import.meta.url), 'utf8')
const apiSource = await readFile(new URL('../src/lib/api.js', import.meta.url), 'utf8')
const { getApodDisplay } = await import('../src/lib/apodLocalization.js')

test('public Phase 1 routes are registered in the React router', () => {
  for (const route of ['/', '/sky', '/explore', '/news', '/apod', '/videos', '/events', '/topics', '/radar', '/about', '/contact']) {
    assert.match(appSource, new RegExp(`path="${route.replace('/', '\\/')}"`))
  }
})

test('frontend API client only resolves supported WordPress REST endpoints', () => {
  for (const endpoint of ['home', 'site', 'news', 'objects', 'planets', 'apod', 'videos', 'events']) {
    assert.match(apiSource, new RegExp(`\\$\\{WORDPRESS_API_URL\\}/${endpoint}`))
  }
  assert.match(apiSource, /throw new Error\(`Unsupported API path:/)
})

test('GET request coalescing and session cache remain enabled for provider-backed endpoints', () => {
  assert.match(apiSource, /const GET_INFLIGHT = new Map\(\)/)
  assert.match(apiSource, /GET_INFLIGHT\.has\(key\)/)
  assert.match(apiSource, /window\.sessionStorage\.setItem/)
  assert.match(apiSource, /path === '\/api\/planets'/)
  assert.match(apiSource, /path === '\/api\/apod'/)
})

test('APOD Persian localization only renders when source hashes match', () => {
  const display = getApodDisplay({
    titleOriginal: 'NASA title',
    contentOriginal: 'NASA explanation',
    excerptOriginal: 'NASA excerpt',
    titleFa: 'عنوان فارسی',
    summaryFa: 'خلاصه فارسی',
    contentFa: 'متن فارسی معتبر',
    hasPersianEditorial: true,
    translationStatus: 'auto_ready',
    sourceHash: 'a'.repeat(64),
    translationSourceHash: 'a'.repeat(64),
  })

  assert.equal(display.hasPersianEditorial, true)
  assert.equal(display.title, 'عنوان فارسی')
  assert.equal(display.content, 'متن فارسی معتبر')
})

test('APOD stale or failed translations fall back to current NASA original', () => {
  for (const item of [
    {
      translationStatus: 'manual_ready',
      sourceHash: 'a'.repeat(64),
      translationSourceHash: 'b'.repeat(64),
    },
    {
      translationStatus: 'failed',
      sourceHash: 'a'.repeat(64),
      translationSourceHash: 'a'.repeat(64),
    },
  ]) {
    const display = getApodDisplay({
      titleOriginal: 'NASA current title',
      contentOriginal: 'NASA current explanation',
      excerptOriginal: 'NASA current excerpt',
      titleFa: 'عنوان قدیمی',
      summaryFa: 'خلاصه قدیمی',
      contentFa: 'متن قدیمی',
      hasPersianEditorial: true,
      ...item,
    })

    assert.equal(display.hasPersianEditorial, false)
    assert.equal(display.title, 'NASA current title')
    assert.equal(display.content, 'NASA current explanation')
    assert.match(display.warning, /NASA/)
  }
})
