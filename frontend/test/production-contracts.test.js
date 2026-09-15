import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'

const appSource = await readFile(new URL('../src/App.jsx', import.meta.url), 'utf8')
const apiSource = await readFile(new URL('../src/lib/api.js', import.meta.url), 'utf8')

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
