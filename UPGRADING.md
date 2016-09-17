# Upgrading

## 1.x to 2.x

You need to add an order column to the mediables table.

```php
$table->integer('order')->unsigned()->index();
```

A handful of methods have been renamed on the `MediaUploader` class.

`setFilename` -> `useFilename`
`setDisk` -> `toDisk`
`setDirectory` -> `toDirectory`

