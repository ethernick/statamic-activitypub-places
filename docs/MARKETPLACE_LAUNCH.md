# Statamic Marketplace Launch & Marketing Blueprint: ActivityPub Places

This document defines the creative direction, visual specifications, screenshot guidelines, demonstration storyboards, and creator onboarding copy for launching **ActivityPub Places** on the **Statamic Marketplace** and **Packagist**.

---

## 🎨 1. Marketplace Hero Banner Specifications

The hero banner is the primary showcase image for the Statamic Marketplace listing, social sharing cards (Open Graph), and community promotions.

### Visual Architecture & Specs
- **Canvas Size**: `1200 × 630 px` (Standard 1.91:1 ratio, 2x retina export).
- **Background Theme**:
  - Dark Mode Cartography Obsidian: Base `#0B0F19` with a subtle emerald-to-indigo radial gradient (`#064E3B` dark emerald radiating from left / `#1E1B4B` deep indigo from right, at 30% opacity).
  - Micro-grid / topo contour overlay: Subtle contour lines or map grid pattern at 10% opacity.
- **Logos & Typography**:
  - **Left Visual**: Statamic Radnor "S" logo connecting via a glowing dotted path to the **OpenStreetMap green pin** and the **ActivityPub decentralized nodes** symbol.
  - **Title**: **ActivityPub Places** (`52px`, Bold, `#FFFFFF`).
  - **Subtitle**: **Geographic Context, OpenStreetMap Discovery & Fediverse Check-Ins** (`22px`, `#94A3B8`).
  - **Feature Badges**:
    - `Statamic 5 & 6 Ready` (Emerald dot)
    - `OpenStreetMap & Nominatim` (Green pin)
    - `Arrive & Note Dual-Federation` (Indigo node)
- **Right Visual Anchor**:
  - Angled perspective mockup of the Statamic CP **LocationSearch** drawer:
    - Interactive dark-mode Leaflet map showing a selected cafe in Portland or Vancouver.
    - Floating venue card with address, coordinates, and a glowing "Check In" button.

---

## 📸 2. Control Panel Screenshot Capture Punch-List

Capture these 4 high-resolution screenshots (`1920 × 1080 px` or `1440 × 900 px` at 2x) using clean, production-like data:

### Screenshot 1: The Places Management Dashboard (`01-places-dashboard.png`)
- **Route**: `/cp/activitypub/places`
- **What it Proves**: Seamless venue curation and interactive map overview right inside the Statamic Control Panel.
- **Data Setup**:
  - 4–5 saved venues with realistic titles, addresses, and OSM categories (e.g., "Timbertrain Coffee", "Powell's City of Books", "Stanley Park Seawall").
  - Leaflet map view loaded with distinct venue pins and interactive popup.
- **Callout Tag**: *"Visualize, organize, and manage all your site's places in one unified dashboard."*

### Screenshot 2: OpenStreetMap Discovery & Pin Placement Drawer (`02-checkin-discovery.png`)
- **Route**: Inside LocationSearch drawer / modal.
- **What it Proves**: Instant Nominatim search and click-to-pin reverse geocoding without external API keys.
- **Data Setup**:
  - Active search term ("Coffee") showing instant results with address and category icons.
  - Interactive map preview highlighting polygon boundaries.
- **Callout Tag**: *"Instant OpenStreetMap discovery with polygon previews and precise pin-dropping."*

### Screenshot 3: The Federated Check-In Modal (`03-checkin-post.png`)
- **Route**: CheckInStack drawer.
- **What it Proves**: Easy one-click broadcasting to Mastodon with actor selection and custom commentary.
- **Data Setup**:
  - Actor selected: `@editor@yourdomain.com`.
  - Content: "Quick espresso stop before the afternoon session! ☕"
  - Selected place: "Revolver Coffee".
  - Live preview chicklet showing the OpenStreetMap deep link.
- **Callout Tag**: *"Check in and broadcast native Arrive activities and Mastodon-compatible notes."*

### Screenshot 4: Interactive Leaflet Map in the ActivityPub Inbox (`04-inbox-place.png`)
- **Route**: `/cp/activitypub/inbox`
- **What it Proves**: Inbound location-tagged federated posts render rich, interactive maps directly in the stream.
- **Data Setup**:
  - Incoming federated note from `@alice@mastodon.social` tagged with location coordinates.
  - Rendered Leaflet map pin and address summary inside the note card.
- **Callout Tag**: *"Engage with location-aware federated posts and check-ins without leaving the CP."*

---

## 🎬 3. 15-Second Animated Demo / Video Storyboard

A fast-paced walkthrough demonstrating the end-to-end check-in workflow:

| Second | Scene | Action |
| :---: | :--- | :--- |
| **00:00 – 00:04** | **CP Dashboard** | Click "New Check-In" button in Statamic CP. Drawer slides out smoothly. |
| **00:04 – 00:08** | **OSM Search** | Type "Granville Island", select result. Map centers and drops pin instantly. |
| **00:08 – 00:11** | **Broadcast** | Type note: *"Morning stroll at the market!"* -> Click **Publish Check-In**. |
| **00:11 – 00:15** | **Federation Proof** | Cut to Mastodon web UI: Note appears in feed with live OpenStreetMap link. |

---

## ✍️ 4. Marketplace Listing Copy Pack

### Short Description (Marketplace Card)
> "Add OpenStreetMap venue discovery, rich location tagging, and federated ActivityPub check-ins to Statamic."

### Price:
> **$10** (Early Adopter "Labs" Pricing — Expected to scale to $29–$39 as fediverse spatial specs finalize).

### Version:
> **v0.3.0** (Labs)

### Overview Description

> Bring spatial intelligence to your Statamic site and connect your physical adventures with the Fediverse.
> 
> **ActivityPub Places** integrates global OpenStreetMap venue search and interactive Leaflet maps directly into the Statamic authoring experience. Publish native ActivityStreams `Arrive` check-ins or location-enriched blog entries that seamlessly federate to Mastodon, Pixelfed, and beyond.
>
> ### Why You'll Love It
> - **Zero-Config Geocoding**: Search millions of global venues via OpenStreetMap Nominatim with zero third-party billing or API keys required.
> - **Dual-Mode Federation**: Publish native `Arrive` activities for modern ActivityPub apps while automatically generating markdown-formatted notes for Mastodon.
> - **Frontend-Ready Antlers Tags**: Drop mobile-friendly Leaflet maps onto any page with one simple tag: `{{ activitypub_place:map }}`.
> - **Built for Statamic 5 & 6**: Ships dual Vue 2 and Vue 3 Control Panel bundles for total compatibility.
