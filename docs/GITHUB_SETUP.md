# GitHub Repository Setup

## Recommended metadata

**Repository name**

```text
jazireh-astronomy
```

**Description**

```text
A source-available Persian RTL astronomy platform built for a real client with React, Three.js, PHP, and MySQL.
```

**Visibility**

```text
Public
```

**License selector during repository creation**

```text
None
```

Do not select MIT, Apache-2.0, GPL, or another standard open-source license. This package already includes a custom `LICENSE` file.

## Topics

```text
react
vite
tailwindcss
threejs
react-three-fiber
php
mysql
astronomy
rtl
persian
full-stack
source-available
portfolio
```

## Recommended GitHub settings

- Default branch: `main`
- Disable Issues unless you want public questions.
- Disable Discussions.
- Disable Wiki.
- Enable secret scanning and push protection when available.
- Enable private vulnerability reporting.
- Use `docs/screenshots/homepage-overview.png` as the social preview image.
- Add the deployed website URL only after a real public deployment exists.

## Repository creation

Create an empty public repository without GitHub-generated README, `.gitignore`, or license files. Then push the contents of this prepared folder.

```bash
git init
git branch -M main
git add .
git status
git commit -m "feat: publish Jazireh Astronomy portfolio source"
git remote add origin <your-repository-url>
git push -u origin main
```

Before `git add .`, verify that `backend/.env`, `frontend/.env`, `frontend/dist`, and `frontend/node_modules` do not exist in the prepared folder.

## Suggested About section

```text
Real-client Persian astronomy platform • React, Three.js, PHP, MySQL • Public source review, proprietary use terms
```

## Suggested first release

Do not create a release until the repository is pushed and the GitHub Actions workflow is green. A suitable first tag is:

```text
portfolio-v1.0.0
```
