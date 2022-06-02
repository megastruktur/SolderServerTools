WIP document

I didn't find the way how to build Minecraft Server for Solder packs automatically so desided to write my own.

Builds are created at ./builds/<MODPACK SLUG>/<MODPACK VERSION> by default.

0. Run `composer install`
1. Copy config.ini.example to config.ini. Modify `solder_url`, `solder_api_key`.
2. Run `php runner.php <MODPACK SLUG> <MODPACK VERSION>`
3. Don't forget to add "level-type" `BIOMESOP` to config before starting the server to enable Biomes-o-plenty
