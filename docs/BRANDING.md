# Branding and Attribution

## Official Jazireh identity

The supplied Jazireh logo is stored in `frontend/public/brand` in optimized formats for the interface, browser icons, Apple touch icons, and the web app manifest.

The official channel URL is:

```text
https://www.youtube.com/@Jazireh
```

Repeated values are centralized in:

```text
frontend/src/config/site.js
```

## Client-channel placement

The official channel is promoted in several product-relevant locations:

- header call to action;
- mobile navigation;
- dedicated homepage spotlight;
- videos page;
- footer channel card.

These placements belong to the product identity and help visitors move naturally from the website to the channel's long-form and video content.

## Developer attribution

Developer promotion appears only once in the global footer:

```text
طراحی و توسعه وب توسط آرتین کریمی
```

This keeps the credit visible and persistent without competing with the client brand or interrupting primary calls to action.

The destination is controlled through the frontend environment:

```env
VITE_DEVELOPER_URL=https://github.com/artinkarimi-dev
```

Replace it later with a portfolio, LinkedIn profile, contact page, or professional website. The component does not need to be edited.

The link uses a descriptive accessible label and `rel="sponsored nofollow noopener noreferrer"` because the placement was granted as promotional consideration.
