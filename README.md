# DravenCMS File

File storage and administration for DravenCMS. The package combines `salamek/nette-files` with database-backed metadata, virtual structures, usage links, upload controls, selectors, and maintenance commands.

## Features

- Admin file manager with uploads, previews, metadata editing, and selection dialogs.
- Content-addressed files stored below the public data directory.
- Virtual directory structures and structure-file records.
- Usage links that let other packages track whether a file is still needed.
- Image/file rendering through Nette Files Latte integration.
- Cleanup and storage-migration console commands.

## Installation

```bash
composer require dravencms/file
```

The package loader publishes assets to `%wwwDir%/assets/file` and loads its configuration. By default, original data is stored under `%wwwDir%/data`, generated files under `%wwwDir%/webtemp`, and icons under `%wwwDir%/assets/file/ico`.

Apply the package's Doctrine schema. Navigation and permission definitions come from providers; assign permissions through the group editor. Ensure the web/PHP process can write to the data and webtemp directories.

## Application Use

Inject `FileRepository`, `StructureRepository`, or `StructureFileRepository` when application code needs to create or query managed files. Prefer repository creation methods so hashes, metadata, and storage relationships remain consistent.

Other DravenCMS modules should associate files through `StructureFileLink` records. The `isUsed` and `isAutoclean` flags allow cleanup commands to distinguish retained files from disposable references.

## Maintenance Commands

The package registers these commands:

```text
file:orphaned:delete
file:unused:delete
file:unused-f:delete
file:extension:fix
file:segmented-storage:migrate
```

The delete and migration commands modify both database records and storage. Back up the database and data directory, inspect command help, and test against a non-production copy before running them in production.

## Optional CMS Integration

Installing `dravencms/structure` allows file components to be exposed through the CMS integration layer.

## License

This package is licensed under the LGPL-3.0-only license.

## Admin authorization and mutations

Administration uses User 3.1, Admin 3.2 and Application 0.3 guards. Screens and write callbacks enforce the existing provider-defined permissions server-side, including direct URLs and bulk callbacks. Mutating signals require POST and a session-bound action token; synchronize the matching admin assets when upgrading. See the User package's `user:security:migrate` instructions for the password-reset schema change.


## Public assets

Released packages include a generated `public/` directory. Packager 3 links it to
`www/assets/` on every Composer install/update; existing asset URLs stay the same.
Development sources, package metadata and tests are kept outside the public directory.

After editing assets, install Composer dependencies and run `composer build:assets`.
The explicit runtime file list is `assets/public-assets.json`; local CSS dependencies
are included automatically. Commit `public/` and `public-assets.manifest.json` with
the source changes. The builder rejects missing dependencies and locally modified
or unmanaged generated files. Admin's `npm run build:admin` also rebuilds public assets.

## Private delivery (File 3 / Nette Files 3)

Uploaded originals and generated image variants are outside `www`. URLs use
`/files/<resolver>/<reference>/<checksum>/<variant>` and are authorized through
`Salamek\Files\Access\IFileAccessProvider`. Packages register specific resolvers;
File's `file.admin` resolver requires the existing `file.edit` permission and always
returns private responses. Public gallery/content attachments use their owning
plugin's resolver. Generic file icons remain public package assets.

Before the first deployment, stop uploads/requests, back up the database and storage,
install matching package versions, and update the generated File configuration
(private data/cache paths, image recipes, routing and presenter mapping). Then run:

```sh
php bin/console file:hash:migrate
php bin/console file:hash:migrate --apply
php bin/console file:storage:migrate
php bin/console file:storage:migrate --apply
```

The targeted schema migration widens only `fileFile.sum` from 32 to 64 characters.
Existing IDs, foreign keys and MD5 checksums remain valid. New uploads use SHA-256.
MySQL DDL is not transactional. The storage command previews all moves first,
refuses collisions/symlinks, moves originals to the configured private data directory,
archives old previews in `var/files/legacy-webtemp`, and verifies file checksums.
It preserves root README/server markers and top-level Webloader CSS/JS files.

Block direct `/data/` access and old `/webtemp/` image previews at the web server,
while retaining public Webloader CSS/JS. Clear compiled containers and reload workers
before resuming requests. `composer install` does not run database or storage migrations.
Use persistent/shared private storage when deploying separate release directories.

Public responses use a configurable TTL (default one hour); private responses and
errors are not shared-cacheable. URLs have no expiry. Configure a reverse-proxy cache
that honors those decisions if PHP-free public cache hits are needed. Optional
`files.accelMapping` hands the transfer to Nginx internal locations; without it,
Nette handles range-capable streaming. See Nette Files' README for server configuration.

All custom image helpers/templates must pass a `FileReference`, not a bare file.
Stored HTML embeds must pass through `fileContent` with their owning content resolver.
There is no public raw-checksum fallback endpoint.


## Variant configuration (3.1)

This package declares its own variants in `dravencms.config.neon`; Nette merges the
`files.variants` maps. Definitions use named `width`, `height` and `resizeMode` fields.
`resizeMode` defaults to `fit`; an omitted/null dimension is unconstrained, and at
least one dimension is required. On upgrade, replace old positional definitions
and remove variants owned by other packages from the File configuration. Project
variants belong in project configuration. Update the matching File, Nette Files
and installed Gallery/Article/Partner/Carousel releases together. No additional
database or storage migration is needed for this configuration change.
