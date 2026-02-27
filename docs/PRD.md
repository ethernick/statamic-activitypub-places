# Product Requirements Document: ActivityPubPlaces

## 1. Executive Summary
This addon extends the Statamic ActivityPub ecosystem to support location-based federated objects (e.g., Check-ins, Places). It integrates geospatial data into the Fediverse, enabling authors to share physical locations, trips, or venue reviews.

## 2. Strategic Context
### Vision
To bring location awareness to Fediverse interactions on Statamic, rivaling platforms that feature check-ins and shared mapping, utilizing open mapping standards.

### Goals
- Support `Place` objects and location-based Activities (e.g., Check-ins).
- Manage and federate Collections of places (e.g., "My favorite coffee shops").
- Leverage open source and decentralized mapping services.

## 3. Architecture
### Key Components
- **Geospatial Objects**: Parsing and generating location-based ActivityPub objects.
- **Collections**: Storing and sharing `Collection` and `OrderedCollection` of Places.
- **Map Integration**: UI integration with OpenStreetMaps, CoMaps, or equivalent open services.
- **CP Extensibility**: Expanding the core "New" button to allow "New Check-in", and rendering maps in the Inbox stream.

## 4. Work Log / Session History
*Consult `docs/session/` for detailed logs of specific development sessions.*
