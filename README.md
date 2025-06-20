### ЗАДАЧА
* генерация файла application/ld+json schema.org для сайта http://test

### СИСТЕМНЫЕ ТРЕБОВАНИЯ
* PHP v8.3x

### ПОДГОТОВКА
* `composer dump-autoload`

### ПРИМЕР ИСПОЛЬЗОВАНИЯ
```
/**
* @var string $url_site - URL сайта
* @var string $site_name - название сайта
*/
$sgh = new SchemaGenerator($url_site, $site_name);

/**
* @var string $src - HTML текущей страницы
*/

// результат в виде ассоциативного массива
$aResult = $sgh->asArray($src);

// результат в формате JSON
$sResult = $sgh->asString($src);
```

### АВТОР
* Шатров Алексей Сергеевич <mail@ashatrov.ru>