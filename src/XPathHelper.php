<?php
namespace Alexe\Schema;

/**
* Упрощённая работа с XPath
*/
abstract class XPathHelper
{
    /**
    * Преобразование результата
    *
    * @param mixed $item - результат
    * @param int &$i - позиция
    * @param callable $sub - функция преобразования
    *
    * @return mixed
    */
    protected static function _compute($result, int &$i, callable $sub)
    {
        return $sub($result, ++ $i);
    }

    /**
    * Дополнительная обработка узла DOM
    *
    * @param \DOMNode $item - узел
    * @param int &$i - позиция
    *
    * @return \DOMNode
    */
    public static function dummyNode(\DOMNode $node, int $i = 0): \DOMNode {
        return $node;
    }

    /**
    * Текст узла с очисткой пробелов по краям
    *
    * @param \DOMNode $item - узел
    * @param int &$i - позиция
    *
    * @return ?string
    */
    public static function dummyText(\DOMNode $node, int $i = 0): ?string {
        $result = trim(static::dummyNode($node, $i)->textContent);

        return mb_strlen($result) ? $result : null;
    }

    /**
    * Список узлов
    *
    * @param array | \DOMXPath $ctx - объект XPath или массив: объект XPath и контекст
    * @param string $query - запрос XPath
    * @param ?callable $sub - доп. обработчик результата
    *
    * @return \Generator
    */
    public static function getList(array | \DOMXPath $ctx, string $query, ?callable $sub = null): \Generator {
        [$args, $i,] = [[&$query,], 0,];

        if (is_array($ctx)) [$ctx, $args[],] = $ctx;
        if (!$sub) $sub = [static::class, 'dummyNode',];

        foreach ($ctx->query(... $args) as $item)
            yield static::_compute($item, $i, $sub);
    }

    /**
    * Первый узел из коллекции
    *
    * @param array | \DOMXPath $ctx - объект XPath или массив: объект XPath и контекст
    * @param string $query - запрос XPath
    * @param ?callable $sub - доп. обработчик результата
    *
    * @return ?\DOMNode
    */
    public static function getOne(array | \DOMXPath $ctx, string $query, ?callable $sub = null): ?\DOMNode {
        return static::getList($ctx, $query, $sub)->current();
    }

    /**
    * Массив текстов узлов коллекции
    *
    * @param array | \DOMXPath $ctx - объект XPath или массив: объект XPath и контекст
    * @param string $query - запрос XPath
    * @param ?callable $sub - доп. обработчик результата
    *
    * @return \Generator
    */
    public static function getListText(array | \DOMXPath $ctx, string $query, ?callable $sub = null): \Generator {
        return static::getList($ctx, $query, $sub ?: [static::class, 'dummyText',]);
    }

    /**
    * Текст первого узла коллекции
    *
    * @param array | \DOMXPath $ctx - объект XPath или массив: объект XPath и контекст
    * @param string $query - запрос XPath
    * @param ?callable $sub - доп. обработчик результата
    *
    * @return ?string
    */
    public static function getOneText(array | \DOMXPath $ctx, string $query, ?callable $sub = null): ?string {
        return static::getListText($ctx, $query, $sub)->current();
    }
}