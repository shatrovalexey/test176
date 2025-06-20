<?php
namespace Alexe\Schema;

/**
* Обработка значений строк
*/
class StringHelper {
    /**
    * Массив значений по массиву ключей
    *
    * @param array &$array
    * @param array $keys
    *
    * @return array
    */
    protected static function _getValues(array &$array, array $keys): array
    {
        return array_map(fn (string $key): string => $array[$key], $keys);
    }

    /**
    * Только цифры
    *
    * @return string
    */
    public static function getInt(?string $string): string
    {
        return preg_replace('{\D}uis', '', $string) ?: 0;
    }

    /**
    * Стоимость
    *
    * @return string
    */
    public static function getPrice(?string $string): string
    {
        return static::getInt($string);
    }

    /**
    * Год
    *
    * @return string
    */
    public static function getYear(?string $string): string
    {
        return static::getInt($string);
    }

    /**
    * Абсолютный URL по относительному
    *
    * @var ?string $url_current - текущий URL
    * @var string $url_site - URL сайта
    *
    * @return string
    */
    public static function getUrl(?string $url_current, string $url_site): string
    {
        [$origin, $url,] = array_map('parse_url', [$url_site, $url_current,]);

        if (mb_strpos($url['path'], '/') !== 0)
            $url['path'] = '/' . $url['path'];

        if (empty($url['host']))
            foreach (['scheme', 'host',] as $key)
                $url[$key] = $origin[$key];

        $result = sprintf('%s://%s%s', ... static::_getValues($url, ['scheme', 'host', 'path',]));

        if (!empty($url['query']))
            $result .= '?' . $url['query'];

        return $result;
    }

    /**
    * Адрес
    *
    * @param ?string $value
    *
    * @return ?string
    */
    public static function getAddressLocality(?string $value): ?string {
        return $value;
    }

    /**
    * Улица
    *
    * @param ?string $value
    *
    * @return ?string
    */
    public static function getStreetAddress(?string $value): ?string {
        return $value;
    }

    /**
    * E-mail
    *
    * @param ?string $value
    *
    * @return ?string
    */
    public static function getEmail(?string $value): ?string {
        return str_replace('@', '(at)', $value);
    }

    /**
    * Номер телефона
    *
    * @param ?string $value
    *
    * @return ?string
    */
    public static function getTelephone(?string $value): ?string {
        $result = preg_replace(['{\D}uis', '{^(?=[^7])}uis',], ['', '7',], $value);

        return mb_strlen($result) == 11
            ? preg_replace('{^(\d)(\d{3})(\d+)}uis', '+$1($2)$3', $result)
            : null;
    }

    /**
    * День
    *
    * @param array $values
    *
    * @return array
    */
    public static function getDay(array $values): array {
        $days = [['MON', 'MONDAY', 'ПН', 'ПНД', 'ПОНЕДЕЛЬНИК',], ['TUE', 'TUESDAY', 'ВТ', 'ВТОРНИК',]
            , ['WEB', 'WEDNESDAY', 'СР', 'СРЕДА',], ['THU', 'THURSDAY', 'ЧТ', 'ЧЕТВЕРГ',]
            , ['FRI', 'FRIDAY', 'ПТ', 'ПЯТНИЦА',], ['SAT', 'SATURDAY', 'СБ', 'СУББОТА',]
            , ['SUN', 'SUNDAY', 'ВС', 'ВОСКРЕСЕНЬЕ',]
            ,
        ];

        foreach ($values as &$value) {
            $value = mb_strtoupper($value);

            foreach ($days as &$variants) {
                if (!in_array($value, $variants)) continue;

                [$value,] = $variants;

                break;
            }
        }

        return $values;
    }

    /**
    * Список дней
    *
    * @param array $value
    *
    * @return array
    */
    public static function getDays(string $value): array {
        return static::getDay(array_filter(preg_split('{[^\wа-яА-ЯёЁ]+}uis', $value)));
    }

    /**
    * Часы и минуты
    *
    * @param ?string $value
    *
    * @return ?string
    */
    public static function getHours(?string $value): ?string {
        if (!preg_match('{^\s*(\d{1,2})(?:\D+(\d{1,2}))?\s*$}uis', $value, $matches)) return null;

        [$hours, $minutes,] = array_splice($matches, 1);

        return (($hours > 24) || ($minutes > 59))
            ? null
            : sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
    * Время работы
    *
    * @param ?string $value
    *
    * @return array
    */
    public static function getOpeningHours(?string $value): array {
        $results = explode('/', $value);

        foreach ($results as &$result) {
            if (!preg_match('{^\s*(\S+)\s*(\S+)(?:\s*\-\s*(\S+))?\s*$}uis', $result, $matches)) continue;

            [, $days, $from, $to,] = $matches;
            [$days, $from, $to,] = [static::_getDays($days), static::_getHours($from), static::_getHours($to),];

            $result = implode(' ', array_filter([implode('-', array_filter($days)), implode('-', array_filter([$from, $to,])),]));
        }

        return $results;
    }

    /**
    * Преобразование формата даты в формат "гггг-мм-дд"
    *
    * @param ?string $value
    *
    * @return ?string
    */
    public static function getDate(?string $value): ?string {
        foreach (
            [
                ['{^(\d{4})\.(\d{1,2})\.(\d{1,2})$}uis', false,]
                , ['{^(\d{1,2})\.(\d{1,2})\.(\d{4})$}uis', true,]
                ,
            ] as [$rx, $reverse,]
        ) {
            if (!preg_match($rx, $value, $matches)) continue;

            array_shift($matches);

            if ($reverse) $matches = array_reverse($matches);

            return sprintf('%04d-%02d-%02d', ... $matches);
        }

        return null;
    }
}