# Dravencms File module

This is a File module for dravencms

## Instalation

The best way to install dravencms/file is using  [Composer](http://getcomposer.org/):


```sh
$ composer require dravencms/file:@dev
```

Then you have to register extension in `config.neon`.

```yaml
extensions:
	file: Dravencms\File\DI\FileExtension
```
