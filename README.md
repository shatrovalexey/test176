### ЗАДАЧА
* генерация файла application/ld+json schema.org для сайта http://test

### СИСТЕМНЫЕ ТРЕБОВАНИЯ
* PHP v8.3x

### ПОДГОТОВКА
* `composer dump-autoload`

### ПРИМЕР ИСПОЛЬЗОВАНИЯ
```
/**
* @var string $url_current - URL текущей страницы
* @var string $url_site - URL сайта
*/
$sgh = new SchemaGenerator($url_current, $url_site);

/**
* @var string $src - HTML текущей страницы
*/

// результат в виде ассоциативного массива
$aresult = $sgh->asArray($src);

// результат в формате JSON
$sresult = $sgh->asString($src);
```

### АВТОР
* Шатров Алексей Сергеевич <mail@ashatrov.ru>