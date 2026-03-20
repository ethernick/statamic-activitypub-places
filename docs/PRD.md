# Product Requirements Document: ActivityPubPlaces

**Document Owner**: ethernick
**Status**: Discovery / Drafting

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

## 6. Requirements

### Must Have (P0)
- **Place Collection Configurability**: Define mapping to one or multiple Statamic collections (like other Object Types) with "Enabled" and "Federate" states.
- **Location Fieldtype UI**: OSM search UI with a required selector for "Destination Collection" upon saving a new Place.
- **`Arrive` Activity Support**: When an entry is first published with a Place attached, generate an `Arrive` Activity in addition to the `Create` Activity. The `Arrive` Activity acts as the check-in and should *only* be generated on initial creation (not on subsequent updates or deletions).
- **places.pub Extensibility**: Map OSM Nodes/Ways/Relations to `https://places.pub/(node|way|relation)/ID` format.
- **Paginated Actor Profile Extension**: Inject a `places` property pointing to an ActivityPub Collection endpoint, strictly paginated by recency, with logic to filter out non-public/non-federated Place collections.

### Should Have (P1)
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

## 9. Work Log / Session History
*Consult `docs/session/` for detailed logs of specific development sessions.*
