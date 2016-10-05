# Changelog

## 2.1.0 - 2016-09-24
- Added means of removing order by from media relation query.
- Fixed multiple media passed to `attachMedia()` or `syncMedia()` receiving the same order value.
- Fixed issue with ONLY_FULL_GROUP_BY (MySQL 5.6.5+).
- Reworked `attachMedia()` to optimize the number of executed queries.


## 2.0.0 - 2016-09-17
- `Mediable` models now remember the order in which `Media` is attached to each tag.
- Renamed a few `MediaUploader` methods.
- Facilitated setting `MediaUploader` on-duplicate behaviour. Thanks @jdhmtl.
- `MediaUploader` can now generate filenames using hash of file contents. Thanks @geidelguerra!
- Added `import()` and `update()` methods to `MediaUploader`.

## 1.1.1 - 2016-08-16
- Published migration file now uses dynamic timestamp. Thanks @borisdamevin!

## 1.1.0 - 2016-08-14
- Added behaviour for detaching mediable relationships when Media or Mediable models are deleted or soft deleted.

## 1.0.1 - 2016-08-12
- Fixed `Mediable` relationship not connecting to custom `Media` subclass defined in config.

## 1.0.0 - 2016-08-04
- Added match-all case to media eager load helpers.
- `Mediable::getTagsForMedia()` now properly rehydrates media if necessary.
- `Mediable::load()` now looks for media that is either the $relationship key or value.

## 0.3.0 - 2016-07-25
- Added MediaCollection class.
- Added media eager loading helpers to query builder, `Mediable`, and MediaCollection.

## 0.2.0 - 2016-07-21
- Added object typehints to all appropriate functions and closures.
