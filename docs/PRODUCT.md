# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

WordPress (theme + plugins, PHP/MySQL), hosted on a Hostinger "Unlimited" plan (native WordPress + MySQL support, NVMe storage, CDN included, daily backups). Chosen over a headless/Next.js or Astro path after explicit comparison, for maintainability, hosting fit, and project size — decision made knowingly against the owner's own frontend specialty (Next.js) in favor of long-term upkeep by non-specialist maintainers and mature plugin coverage for the feature roadmap (donations, prayer requests, radio, audio devotionals).

## Users

Two audiences: existing church members, and members of the public researching or discovering the church online before ever visiting in person (or instead of visiting).

## Product Purpose

Institutional website for IEADC Vila Ipiranga (name confirmed by the user). It exists to be the digital front door of the church: a single place that consolidates schedules, location, events, leadership, teaching content, and ways to connect or give, for both members and newcomers.

## Positioning

Not a competitive product in a commercial sense; its "positioning" is trustworthy, welcoming digital presence for the local church community — the place someone unfamiliar with the church goes to decide whether to visit, and the place a member goes to stay informed and connected.

## Operating Context

- Hosted on Hostinger "Unlimited" web hosting (confirmed plan: unlimited sites, 50GB NVMe storage, free domain year 1, daily backups, CDN, native WordPress installer, Node.js app support also available but unused under the WordPress decision).
- Content is expected to be maintained over time by people who are not necessarily developers (church staff/volunteers), which was an explicit factor in the stack decision.
- Audience skews toward a broad, non-technical congregation, including older visitors — reinforces the accessibility and simplicity requirements below.

## Capabilities and Constraints

Confirmed scope (current):
- Service times and location, with map/directions
- Events and agenda
- About the church and leadership (history, pastors, ministries, mission/vision)
- Articles
- Media (photos/videos)
- Ministries
- A clear path/CTA to "aceitar Jesus" (accept Jesus / conversion path)
- Contact (general contact methods, social links)

Confirmed future roadmap (not yet built, but must not be architecturally blocked):
- Donations — moved out of Phase 1 on 2026-09-18; depends on treasury alignment before it can even be scoped with the pastor
- Prayer requests ("pedidos de oração")
- Radio (live stream)
- "Telepaz" — short audio-format biblical messages/devotionals

Non-functional constraints, explicitly stated by the user: the site must stay **leve, funcional, otimizado, eficaz, user-friendly e acessível** (lightweight, functional, performant, effective, user-friendly, and accessible). These are binding quality bars for every build/design decision, not aspirational language.

Stack constraint: WordPress plugin ecosystem is expected to cover the full roadmap (e.g. GiveWP/WooCommerce-class plugins for donations, form plugins or dedicated prayer-request plugins, audio/radio-stream player plugins) rather than custom development — keep future feature decisions plugin-first to preserve the "leve" constraint and the maintainability rationale behind the stack choice.

## Brand Commitments

Church name confirmed: IEADC Vila Ipiranga. No logo or other brand assets confirmed yet.

## Evidence on Hand

None found in the repository at init time, and confirmed by the user: most content material (copy, imagery, service times, addresses, staff/pastor names, ministry details, etc.) is still being collected from the people responsible at the church and is not yet available. Future work must not fabricate any of this content; use realistic placeholders clearly marked as such until real material is provided, and design in a way that content can be dropped in later without restructuring.

## Product Principles

1. Performance first: the congregation includes varied devices and connection quality; every page must load fast and stay lightweight, per the user's explicit requirement.
2. Accessibility as default, not an add-on: the audience includes elderly and possibly disabled visitors; design and build for inclusive access from the start.
3. Low-maintenance content model: content changes must be doable by non-developer staff through the WordPress admin, without code changes.
4. Clear path to action: a visitor must be able to quickly find service times/location and the "aceitar Jesus" pathway — these are the site's core conversion moments.
5. Grow through plugins, not bespoke engineering: new roadmap features (donations, prayer requests, radio, Telepaz) should be added via vetted, well-maintained WordPress plugins to protect the "leve" and "otimizado" constraints, reserving custom development for cases no plugin covers well.

## Accessibility & Inclusion

WCAG 2.1 AA is the confirmed required standard.
