WIP document

I didn't find the way how to build Minecraft Server for Solder packs automatically so desided to write my own.

Builds are created at ./builds/<DATE> by default.

0. Run `composer install`
1. Copy config.ini.example to config.ini. Modify `minecraft_version`, `solder_url`, `solder_api_key`, `solder_modpack_slug`.
2. Run `php "runner.php"`
