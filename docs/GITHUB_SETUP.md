# GitHub Repository Setup

## Repository metadata

**Name**

```text
jazireh-astronomy
```

**Description**

```text
Source-available Persian RTL astronomy platform for the official Jazireh channel, built with React, PHP, and MySQL.
```

**Visibility**

```text
Public
```

**GitHub license selector**

```text
None
```

Do not add MIT, Apache-2.0, GPL, or another open-source license. The repository already contains a custom `LICENSE` file.

## Suggested topics

```text
react
vite
tailwindcss
react-three-fiber
php
mysql
astronomy
rtl
persian
full-stack
youtube
source-available
portfolio
```

## Recommended settings

- Default branch: `main`
- Enable secret scanning and push protection when available.
- Enable private vulnerability reporting.
- Keep Discussions and Wiki disabled unless they become necessary.
- Use `frontend/public/brand/icon-512.png` as the repository social preview until a current website screenshot is prepared.
- Add the production website URL only after a real deployment exists.
- Do not create a release until GitHub Actions is green.

## Push this prepared version

```bash
git status
git add .
git diff --cached
git commit -m "feat: professionalize Jazireh branding and content platform"
git push origin main
```

Before committing, confirm these paths are absent:

```text
backend/.env
frontend/.env
frontend/node_modules
frontend/dist
```

## Suggested About text

```text
Official Jazireh astronomy platform • Persian RTL • React, PHP, MySQL • Public source review, proprietary use terms
```
