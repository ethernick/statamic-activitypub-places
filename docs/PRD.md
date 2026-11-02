# Product Requirements Document: ActivityPubPlaces

**Document Owner**: ethernick
**Status**: Release Candidate (Phases 2 & 3 Complete)

---

## 1. Executive Summary
The `ActivityPubPlaces` addon for Statamic introduces spatial, geographical context to ActivityPub enabled content (Notes, Articles, etc.). By integrating OpenStreetMap (OSM) lookups and standardizing federated references to `places.pub`, authors can easily append locations to their content (e.g., tagging multiple locations like "Top 10 Coffee Shops"). Crucially, it introduces the `Arrive` activity to represent a single "Check-in". The addon allows authors to aggregate these locations into configurable collections (e.g., "Places to Go", "Favorite Restos"), which can optionally form a paginated, public "Places" endpoint on the Actor profile.

## 2. Problem Statement
**Current State**: ActivityPub content in the Statamic ecosystem lacks native tools for tagging, storing, and federating geographical data (AS2 `Place` and `Arrive` activities).
**User Impact**: Authors cannot easily share check-ins or location lists with the fediverse without manually crafting complex JSON.
**Opportunity**: Create a standardized, user-friendly control panel experience that leverages OSM for discovery, supports the `Arrive` Activity for check-ins, and utilizes `places.pub` IDs to ensure compatibility without becoming a unified places server itself.

## 3. Goals & Success Metrics
### Goals
- Allow authors to append one or multiple locations to an Entry, and actively broadcast an `Arrive` activity for check-ins.
- Provide a smooth Control Panel lookup experience via OSM with "Near Me", generic text search, and zoom-out capabilities.
- Support multiple Place collections with configurable routing from the UI (e.g. "Save to..." dropdown).
- Configure opt-in, paginated Actor Profile endpoints for specific Place collections to keep private check-ins out of the public directory.
- Successfully federate location objects using `places.pub` ID references.

## 4. Target Users
- **Content Authors**: Want an easy, native UI in Statamic to search for locations, build lists, and check-in dynamically.
- **Fediverse Followers**: Want rich, actionable location data and check-ins attached to entries in their timelines.

## 5. Solution Overview
**Proposed Approach**: 
1. **Places Configuration**: Like other Object Types, Places will have settings specifying custom naming, whether they are "Enabled", and whether they "Federate". Authors can define multiple collections (e.g., `places_visited`, `places_wishlist`). 
2. **Authoring UX (Fieldtype/UI)**: A custom backend component that searches OSM. Because there can be multiple collections, the UI must include a "Save place to Collection..." dropdown.
3. **Actor Federation Layer**: A paginated endpoint on the Actor Profile that curates places. This is *not* an auto-add of all places; it requires an explicit opt-in or routing rule so private places aren't leaked.
4. **Check-in (`Arrive`) Activity**: In addition to standard Creates/Updates, the addon will support dispatching an `Arrive` activity pointing to the Place, acting as the fediverse-standard "Check-in".
5. **Control Panel Navigation**: A new "Places" child item in the CP sidebar under "ActivityPub" (beside Inbox/Questions), designed to list and organize places, show profile visibility, and provide quick links to open locations in OSM/Google Maps. The UI will provide options for "New Check-In" and "New Place".
6. **Extensible Settings & Mastodon Compatibility**: 
   - Connect with Core refactor to register a "Places" tab in the ActivityPub Settings page.
   - **Mastodon Compatibility (Check-in vs Place)**: Instead of an automated checkbox on all places, the UI provides two distinct creation paths: 
     - **New Check-in**: Saves the Place and instantly generates a standard, Mastodon-compatible `Note` activity with an OpenStreetMap deep-link footer.
     - **New Place**: Saves the Place quietly (no `Note` generated) for future use or silent curation.
7. **Interactive Inbox Experience**: Automatically renders a live Leaflet map with full pan/zoom capabilities in the inbox stream for any note containing geographical data.
8. **Web Views**: Ship default frontend Antlers views/templates that define how a Place looks on the web. Statamic developers can easily override these views in their own site's theme if they want a custom design.

## 6. Requirements

### Must Have (P0)
- **Place Collection Configurability**: Define mapping to one or multiple Statamic collections (like other Object Types) with "Enabled" and "Federate" states.
- **Location Fieldtype UI**: OSM search UI with a required selector for "Destination Collection" upon saving a new Place.
- **`Arrive` Activity Support**: When an entry is first published with a Place attached, generate an `Arrive` Activity in addition to the `Create` Activity. The `Arrive` Activity acts as the check-in and should *only* be generated on initial creation (not on subsequent updates or deletions).
- **places.pub Extensibility**: Map OSM Nodes/Ways/Relations to `https://places.pub/(node|way|relation)/ID` format.
- **Paginated Actor Profile Extension**: Inject a `places` property pointing to an ActivityPub Collection endpoint, strictly paginated by recency, with logic to filter out non-public/non-federated Place collections.

### Should Have (P1)
- **Extend Settings for Plugins (Dependency)**: Refactor ActivityPubCore to allow addons to inject tabs into the Settings page.
- **Places Settings Tab**: Include a customizable template/format to generate the Mastodon-compatible `Note` text used during "Check-ins".
- **Dedicated CP "Places" Section**: Child nav item in the CP sidebar (under ActivityPub) to list/organize places, provide external map links, and trigger "New Check-in" or "New Place" workflows.
- **Frontend Overridable Templates (Web)**: Ship default Antlers views that display Places and related notes, ensuring they adhere to Statamic's standard fallback/override system for developers.
- **Fuzzy/Fallback Locations**: When OSM fails or users want privacy, allow defining custom radius and dropping precise coordinates ("zoom out").

### Nice to Have (P2)
- Oembed/Map Image generation for fallback visualization on non-compliant AP clients.

## 7. Technical Considerations
- **Non-Destructive Compliance**: Changes must be tested with `ProvidesSandbox`.
- **`Arrive` verb routing**: Need to ensure the Inbox/Outbox dispatcher smoothly handles `Arrive` vs `Create` activities for the connected Note/Article.
- **OSM API Constraints**: Ensure we comply with Nominatim/OSM usage policies (proper user-agent, caching responses locally via the Places collection).
- **Extensibility**: The Actor Profile collections mapping should be built modularly, anticipating other addons (like `ActivityPubLongFormat` series) adding their own collections in the future.

## 8. Resolved Questions
- **How is a Check-in (`Arrive`) structured?** It is not a distinct Entry Type. It is stored in the Activities collection with the "Arrive" action. Upon initial creation of an entry (Note/Article) with a location, two activities are generated in the outbox: `Create` and `Arrive`. `Arrive` is never regenerated on subsequent edits.

---

## 9. Commercial Strategy & "Labs" Versioning Policy

### Pricing Tier: Early Adopter "Labs" Price ($10)
- **Marketplace Listing Price**: **$10** (Early Adopter "Labs" pricing).
- **Price Escalation Expectations**: Emerging Fediverse spatial standards (AS2 `Place`, `Arrive` check-in federation, `places.pub` URI resolution) are still evolving across remote platforms. As standards crystallize and external mobile/client support matures, pricing will transition to standard commercial rates ($29–$39). Early adopters retain lifetime license access.
- **Semantic Versioning Strategy**: To clearly indicate to developers that spatial federation is an active "Labs / Experimental" extension, releases will remain in the **`v0.3.x`** series (e.g., `v0.3.0`). Major v1.0 semver will be reserved for when W3C/FEP spatial conventions freeze.
- **Actor Multi-User Model**: Inherits multi-user limits from `ActivityPubCore` (Single Actor free / Multi-Actor unlocked via Core Pro $79).

---

## 10. Session History & Release Milestones


### 2026-09-21: Phase 2 & Phase 3 Marketplace Readiness Execution
- **Audited & Resolved Technical Gaps:**
  - Resolved `LocationSearch.test.js` Leaflet mocking gap (added `L.layerGroup` and `map.on` mocks; 5/5 Vitest tests green).
  - Hardened `ActivityPubPlacesInstall` to resolve paths via `ActivityPubUtils::blueprintPath()` for full sandbox compatibility.
  - Added `FreshInstallationTest.php` verifying zero-config installation in isolation (24/24 PHPUnit tests passing).
- **Package Metadata Overhaul (`composer.json`):**
  - Added Packagist/Marketplace compliant metadata: `license: MIT`, `type: statamic-addon`, targeted fediverse/geolocation keywords, author profile, and issue/doc URLs. Validated with `composer validate`.
- **Marketplace Showcase README Overhaul (`README.md`):**
  - Added official Statamic 5/6, PHP, OpenStreetMap, Fediverse, and MIT badges.
  - Authored comprehensive Feature Grid, quick start installer guide, Mermaid architecture diagram, CP screenshot layout, and Antlers tag reference.
- **Documentation Suite Authored (`docs/`):**
  - Created `INSTALLATION.md`: System requirements, guided installation, Nominatim User-Agent setup, and frontend Antlers templates.
  - Created `TROUBLESHOOTING.md`: Diagnostic CLI commands and matrix for Nominatim HTTP 403s, Leaflet map container height, and check-in federation.
  - Created `PLACES_GUIDE.md`: Deep architecture guide on dual-name representation (`osm_name` vs editorial `title`), OSM node/way/relation routing, and `Arrive` vs `Create` ActivityStreams topology.
  - Created `HOOKS.md`: Developer guide for extending Places outbox/transform hooks and overriding Leaflet map tags.
  - Created `MARKETPLACE_LAUNCH.md`: Creative specifications for 1200x630 hero banner, 4 screenshot capture briefs, 15-second animated demo storyboard, and marketing copy pack.
- **Task Roadmap Updated (`TASKS.md`):**
  - Restructured roadmap into structured Phases (Phase 1: Feature Dev, Phase 2: Metadata & Documentation, Phase 3: Launch Blueprint, Phase 4: Administrator Execution). Marked Phases 1, 2, and 3 100% complete.

### Session: 2026-09-27 - Statamic 6 Native CP Design System & Panel Alignment
- **Layout Architecture (`PlacesDashboard.v6.vue`)**:
  - Adopted Statamic 6's native wide container `w-full min-w-0 mx-auto max-w-page max-[1220px]:mb-18` matching core CP pages (`cp.utilities/cache`, `cp/collections`).
  - Standardized page header with `text-[25px] leading-[1.25] st-text-legibility font-medium antialiased`, outline pin icon, and responsive `px-2 sm:px-0 py-6 max-md:pb-8 md:py-8` padding.
- **`@container/panel` Architecture**:
  - **Wide Map Panel**: Encapsulated the Leaflet map within `@container/panel` bezel and `data-ui-panel-header` showing location counts and quick deselect trigger.
  - **Place Details Panel (1/3)**: Converted location inspection card into a panel container with type badges and formatted OSM/coordinate details.
  - **Related Activity Stream Panel (2/3)**: Wrapped check-in stream in `@container/panel` with dynamic activity count badge in the panel header.
  - **Empty Selection State**: Upgraded prompt to match panel styling.
- **Verification & Testing**:
  - Rebuilt dual JS and CSS bundles (`npm run build`).
  - Published vendor assets to `public/vendor/ethernick/activitypub-places/`.
  - Added `data-title="Places"` on root view to satisfy CP route accessibility tests.
  - Verified 100% test pass on PHPUnit test suite (24/24 tests, 81 assertions).

### Session: 2026-09-28 - Related Activity Stream Mirroring & Core Component Refactoring
- **Stream Polish & Markdown Support**:
  - Removed object type pills (`Event`, `Note`, `Check-in`) to de-clutter the venue stream.
  - Suppressed Note titles in backend and frontend to match core micro-post inbox conventions.
  - Added unconditional Markdown parsing via `\Statamic\Facades\Markdown::parse()` in `PlaceSearchController::activities`.
  - Explicitly constrained avatar and icon dimensions (`w-10 h-10` / `40px` and `w-7 h-7` / `28px`).
- **Core Component Refactoring Roadmap**:
  - Identified cross-addon architectural pattern to replace ad-hoc stream markup with Core's globally registered `<activity-pub-post-card>` component.
  - Planned migration of `PlacesDashboard.vue` / `PlacesDashboard.v6.vue` to `<activity-pub-post-card :item="activity" :readonly="true" />` once Core publishes the component.

### Session: 2026-10-07 - Unified Map Presentation & Places Dashboard Deduplication
- **Root Cause & Fix for Duplicate Maps**:
  - `ActivityPubPostCard` loads both `inbox-activity-[Type]` and `inbox-note-content` hook loaders.
  - Both `PlaceSummary` and `InboxPlaceMap` were registered on `inbox-note-content`, while `InboxActivityArrive` was registered on `inbox-activity-Arrive`.
  - Deregistered redundant `PlaceSummary` from `inbox-note-content`.
  - Added `note.activity_type !== 'Arrive'` guard to `InboxPlaceMap.vue` so Arrive activity cards do not double-render a second map.
- **Unified Map Card Style**:
  - Updated `InboxActivityArrive.vue` / `.v6.vue` and `PlaceSummary.vue` / `.v6.vue` to adopt the clean card presentation matching `InboxPlaceMap`: header strip with SVG pin, location title, text-variant directions dropdown ("Open Map"), and a 180px interactive Leaflet map.
- **Verification**:
  - Dual build completed (`npm run build`), keeping `package.json` with `"vue": "^2.7.16"` at rest.
  - PHPUnit test suite: 26 passed, 88 assertions.



