WP Unique Media
===============

WordPress plugin to prevent duplicate files from being uploaded.

This is a shut-the-fork-up-and-work plugin. This means, if someone tries to upload a duplicate attachment to the media library, the existing file is silently getting selected.

If you need a more verbose approach, please use [Media Deduper](https://wordpress.org/plugins/media-deduper/) by [Cornershop Creative](https://cornershopcreative.com/) instead. Both plugins use the same hashing and storage techniques, so either can be an alternative to the other.

Tested with
-----------
 - [Enable Media Replace](https://wordpress.org/plugins/enable-media-replace/)
 - [Regenerate Thumbnails](https://wordpress.org/plugins/regenerate-thumbnails/)
 - Block Editor

Installation
------------
### Production (Stand-Alone)
 - Head over to [releases](../../releases)
 - Download 'wp-unique-media.zip'
 - Upload and activate it like any other WordPress plugin
 - AutoUpdate will run as long as the plugin is active

### Development
 - cd into your plugin directory
 - $ `git clone git@github.com:mcguffin/wp-unique-media.git`
 - $ `cd wp-unique-media`
 - $ `npm install`
 - $ `gulp`

Development status
------------------
The plugin is currently in alpha stadium. Please don't expect it to be stable or doing no damage. Issues and Pull requests are highly appreciated.

WP-Cron
-------
Media hash values are generated during upload or automatically through a cronjob. Please wait a few minutes after the activation, to let wp-cron do its work.

WPCLI
-----
For the impatient (like me) there is a wp-cli command for initial hash generation ...

```sh
$ wp unique-media-hash
```

... and hashing a single attachment:

```sh
$ wp unique-media-hash --attachment_id=123
```

Roadmap:
-------
0.2.0
 - [ ] Remove autoupdater
 - [ ] Test with [polylang](https://de.wordpress.org/plugins/polylang/) translate media option enabled
 - [ ] boilerplate 2

0.3.0
 - [ ] Tool: Replace Duplicates (in wp-content, blocks, widgets, acf image fields, acf gallery fields, known settings)

1.0.0
 - [ ] release at WP.org
