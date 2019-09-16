# Upgrading

## 2.x to 3.x

* Minimum PHP version moved to 7.2
* Minimum Laravel version moved to 5.6
* All methods now have parameter and return type hints. If extending any class or implementing any interface from this package, method signatures will need to be updated. 

## 1.x to 2.x

You need to add an order column to the mediables table.

```php
$table->integer('order')->unsigned()->index();
```

A handful of methods have been renamed on the `MediaUploader` class.

`setFilename` -> `useFilename`
`setDisk` -> `toDisk`
`setDirectory` -> `toDirectory`

