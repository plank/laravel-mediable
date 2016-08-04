# Changelog

## 1.0.0 - 2016-08-04
- Added match-all case to media eager load helpers.
- ``Mediable::getTagsForMedia()`` now properly rehydrates media if necessary.
- ``Mediable::load()`` now looks for media that is either the $relationship key or value

## 0.3.0 - 2016-07-25
- Added MediaCollection class.
- Added media eager loading helpers to query builder, ``Mediable``, and MediaCollection.

## 0.2.0 - 2016-07-21
- Added object typehints to all appropriate functions and closures.
