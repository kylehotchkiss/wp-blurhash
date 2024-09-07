# WP Blurhash

Saves the blurhash of uploaded images to the `blurhash` ACF field in a Wordpress attachment, and exposes it to GraphQL as `acfBlurhash`. This allows statically rendering the page with a lightweight image preview and then loading the actual image on top of it. My use case for this is loading higher-quality JPGs on my photography site than you normally would because of the loading time. 

## Requirements

* Modern PHP environment (only tested on my Dockerized wordpress instance as I don't use an actual remote for my site)
* Advanced Custom Fields
* WP-GraphQL & WP-GraphQL ACF plugins to expose to headless frontned

## Installation - Zip File
Download the latest released zip file in this repo and add to `wp-content`. 

## Installation - Composer

Add to your Wordpress site's composer.json

```
{
  "type": "package",
  "package": {
    "name": "kylehotchkiss/wp-blurhash",
    "version": "1.0.0",
    "type": "wordpress-plugin",
    "dist": {
      "type": "zip",
      "url": "https://github.com/kylehotchkiss/wp-blurhash/releases/download/v1.0.0/wp-blurhash.zip"
    }
  }
}
```

```$ composer require kylehotchkiss/wp-blurhash```

## Applying to existing images

* Go to `Media` > `Blurhash` in WP Admin. Keep clicking the "Generate 50 more" buttons until it's done.

## Support

No support offered. Feel free to fork or PR bugfixes, but this plugin is functionally finalized for me so I'm not interested in new functionality.
