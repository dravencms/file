# Dravencms File module

This is a File module for dravencms

## Instalation

The best way to install dravencms/file is using  [Composer](http://getcomposer.org/):


```sh
$ composer require dravencms/file
```

Then you have to register extension in `config.neon`.

```yaml
extensions:
	file: Dravencms\File\DI\FileExtension
```

## CRON Jobs
These CRON jobs are needed for removing old files

```
./bin/conosle file:unused:delete  # Removes unused files uploaded by another plugins
./bin/console file:orphaned:delete # Removes untracked files from /data directory
```
