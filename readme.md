# WP Blurhash

Saves the blurhash of uploaded images to the `blurhash` ACF field in a Wordpress attachment, and exposes it to GraphQL as `acfBlurhash`. This allows statically rendering the page with a lightweight image preview and then loading the actual image on top of it. My use case for this is loading higher-quality JPGs on my photography site than you normally would because of the loading time. 

