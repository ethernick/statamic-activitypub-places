# ActivityPub Polls for Statamic

An ActivityPub Core Extention to add federated locations to Statamic

## 🌟 What This Will Do

- ✅ **Publish** Notes with location data to Mastodon, Pixelfed, and the entire fediverse
   - additionally publishes an Arrive activity
- ✅ **Accept** responsese from any ActivityPub server
- ✅ **Federate** automatically with HTTP signatures, and activity delivery
- ✅ **Moderate** with built-in blocking and content filtering

## 📋 Requirements

- Statamic 5.x or higher
- PHP 8.2+ (8.3 recommended)
- Laravel 12.x
- Public domain with HTTPS (required for federation)
- Queue worker running (Laravel queue)
- SQLite or MySQL database

## 🎯 Use Cases

- **Personal Blog** → Share favorite places with Mastodon followers
- **Community Site** → Enable members to interact with fediverse
- **News Site** → Broadcast important locations to federated network
- **Portfolio** → Share places with decentralized audience

## Installation

### WIP: Add Repository to Composer.json

Until this is registered, add the repository to composer.json

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://worktree.ca/ethernick/statamic-activitypub-places.git"
    }
]
```

Once added you can run the following

```bash
$ composer require ethernick/statamic-activitypub-places:dev
```

Note: if you want to install a specific branch, fee free. Update "main" with the target branch name. Where the full version format is dev-<Branch Name>
Run Install

```bash
$ php artisan actvitiypub:places:install
```

This install will:

- setup places
- create/associate the place collection

## Antlers Tags
- `{{ activitypub_place:map }}`: Renders an interactive Leaflet map with a pin at the location. It automatically bundles the necessary Leaflet.js and CSS if they aren't already present.
- `{{ activitypub_place:links }}`: Returns an array of external map links (OSM, Google, Apple).
  - `{{ type }}`: Current options: osm, google, apple
  - `{{url}}`: link to each map provider

Example:
```antlers
<h2>{{ osm_name }}</h2>
<p>{{ address }}</p>
    
{{ activitypub_place:map }}
    
<ul>
{{ activitypub_place:links }}
    <li><a href="{{ url }}">{{ type }}</a></li>
{{ /activitypub_place:links }}
</ul>
```

### Context Awareness
All tags automatically detect the id of the current entry if it matches the expected collection (polls or places). You can also pass an explicit `:id="my_id"` if needed.

### Leaflet Map
The Leaflet map in `{{ activitypub_place:map }}` uses a height of 400px by default. You can wrap it in a container to control the size further.

## 🤖 AI Disclosure

This was written with the help of AI agents including Gemini & Claude. 