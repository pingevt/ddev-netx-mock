# ddev-netx-mock

A [ddev](https://ddev.com) add-on that runs the [NetX mock API](https://github.com/pingevt/netx-mock-api) next to your Drupal site, so you can develop the [`netx_dam`](https://github.com/pingevt/netx_dam) module without a NetX instance.

> **Not NetX.** The mock is built from NetX's public docs and is not a source of truth. Response shapes, limits and error cases can differ from the real service. Verify anything important against a real NetX.

## Install

```bash
ddev add-on get pingevt/ddev-netx-mock
ddev restart
```

Until the repo is published, install from a local checkout: `ddev add-on get /path/to/ddev-netx-mock`.

## Use

Enable the module, then create the Drupal side:

```bash
ddev drush en netx_dam netx_dam_media netx_dam_explorer netx_dam_push netx_dam_webhooks
ddev netx-mock-seed
```

`netx-mock-seed` is safe to run again. It creates, only if missing:

- a Key `netx_mock_token` holding the mock's API token
- a NetX source `mock` pointing at the mock (`netx_dam`)
- a media type `netx_asset` (`netx_dam_media`)
- a Key `netx_mock_webhook` and the webhook secret for the source (`netx_dam_webhooks`)

Then browse to `/admin/content/netx-dam/mock/explore`.

To send your site a webhook from the mock:

```bash
ddev netx-mock-trigger ASSET_CREATE 101
ddev netx-mock-trigger FOLDER_CREATE 6
```

## Addresses

| From | URL |
|------|-----|
| The web container (what the module uses) | `http://netx-mock:3000` |
| Your browser, with the demo dashboard | `https://<project>.ddev.site:3031/dashboard` (http on 3030) |

The router ports 3030 and 3031 are shared by every ddev project on your machine, so two projects can't use this add-on at the same time unless you change them in `.ddev/docker-compose.netx-mock.yaml`.

## Configuration

Set these in `.ddev/.env.netx-mock` (or your shell) and `ddev restart`:

| Variable | Default | Purpose |
|----------|---------|---------|
| `NETX_MOCK_IMAGE` | `ghcr.io/pingevt/netx-mock-api:latest` | Which image to run |
| `NETX_MOCK_SOURCE_ID` | `mock` | Source id the mock's seed webhook posts to (`/netx-dam/webhook/<id>`) |

Uploads and webhook subscriptions are kept in the `netx-mock-data` volume, removed with `ddev delete`. For the mock's own settings see its README.

## Remove

```bash
ddev add-on remove netx-mock
```

## Notes

- The mock's seed webhook subscription posts to `http://web/...` inside ddev's network. Real NetX requires HTTPS, so that is one place the mock is more lenient.
- Tested on ddev 1.25.2 against a fresh Drupal 11.4 site with `netx_dam` from `main`.

## To do

- Add the add-on test suite (bats) and a GitHub Actions workflow.
- Pick a license.
- Add the `ddev-get` topic once published.
