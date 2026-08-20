# Product Vision

Jazireh is being shaped as a Persian scientific portal for people who want more than static articles.

## The Product Problem

Many science websites either behave like conventional blogs or act as isolated utilities. Jazireh aims to combine strong editorial storytelling with recurring scientific media and data-driven touchpoints in one coherent product.

## Target Experience

The intended experience is closer to:

- science magazine
- online observatory
- media hub
- repeat-visit portal

Visitors should be able to read, watch, explore, and check what is new without switching between disconnected products.

## Why The Architecture Fits

The current React + WordPress architecture supports that direction well:

- WordPress gives the client a familiar publishing and operations surface
- React gives the public experience a more modern interactive layer
- custom REST endpoints keep the data model product-specific instead of theme-fragmented
- server-side integrations protect secrets and keep external dependencies behind a controlled backend boundary

## Live-Data Philosophy

Not every feature needs to be truly real time, but the product direction values content that changes often enough to justify returning:

- daily imagery
- latest video and media
- recurring scientific snapshots
- synchronized Community publishing

## Editorial Philosophy

Jazireh is designed to present scientific content in Persian with a premium, readable, RTL-first experience. The goal is credibility and clarity, not novelty for its own sake.
