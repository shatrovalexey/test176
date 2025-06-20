<?php
namespace Alexe\Schema;

use Alexe\Schema\{XPathHelper, StringHelper,};

/**
* Пример генератора application/ld+json для schema.org
*/
class SchemaGenerator
{
    protected const NS_NAME = 'sg';
    protected const NS_URI = 'urn:SchemaGenerator';

    protected \DOMDocument $_domh;
    protected ?\DOMXPath $_xpathh;
    protected string $_url;
    protected string $_site_name;

    /**
    * Конструктор
    *
    * @param string $url - URL
    * @param string $site_name - название сайта
    */
    public function __construct(string $url, string $site_name)
    {
        $this->_url = $url;
        $this->_site_name = $site_name;
        $this->_domh = new \DOMDocument('1.0', 'utf-8');
    }

    /**
    * Результат в виде ассоциативного массива
    *
    * @param string &$value - HTML-код страницы
    *
    * @return array
    */
    public function asArray(string &$value): array
    {
        $this->_xpathh = $this->_getXpath($value);

        return static::_expandValues($this->getWebPage());
    }

    /**
    * Результат в формате JSON
    *
    * @param string &$value - HTML-код страницы
    *
    * @return string
    */
    public function asString(string &$value): string
    {
        return json_encode($this->asArray($value), \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
    }

    /**
    * Создание объекта XPath из кода HTML
    *
    * @param string &$src - HTML-код страницы
    *
    * @return string
    */
    protected function _getXpath(string &$src): \DOMXPath
    {
        $this->_domh->loadHTML("\xEF\xBB\xBF{$src}");
        $result = new \DOMXPath($dom);
        $result->registerNamespace(static::NS_NAME, static::NS_URI);
        $result->registerPHPFunctionNS(
            static::NS_URI
            , 'css-class-contains'
            , fn (?string $classList, string $class)
                : bool => preg_match('{(?:^|\s)' . preg_quote($class) . '(\s|$)}uis', $classList)
        );

        return $result;
    }

    /**
    * Раскрытие значений с типом \Generator и очистка массива от пустых значений
    *
    * @param array $array
    *
    * @return array
    */
    protected static function _expandValues(array $array): array
    {
        foreach ($array as $key => &$value) {
            if ($value instanceof \Generator)
                $value = iterator_to_array($value);

            if (
                is_array($value) && !($value = static::_expandValues($value))
                    || is_scalar($value) && (
                        ($value === null)
                        || ($value === '')
                    )
            ) unset($array[$key]);
        }

        return $array;
    }

    /**
    * "Хлебные крошки"
    *
    * @return array
    */
    static function _getBreadcrumbs(): array {
        return [
            '@type' => 'BreadcrumbList'
            , 'itemListElement' => XPathHelper::getList($this->_xpathh, '
(//*[@class = "breadcrumbs"])[1]
    //*[sg:css-class-contains(@class, "breadcrumbs__breadcrumb")]
                ', fn (\DOMNode $item, int $i): array => [
                    '@type' => 'ListItem'
                    , 'position' => $i
                    , 'name' => XPathHelper::getOneText([$this->_xpathh, $item,], './/text()')
                    , 'item' => StringHelper::getUrl(XPathHelper::getOneText([$this->_xpathh, $item,], './/@href'), $this->_site_url)
                    ,
                ]
            )
            ,
        ];
    }

    /**
    * Пагинация
    *
    * @return array
    */
    protected function _getPagination(): array {
        return [
            '@type' => 'ItemList'
            , 'itemListElement' => array_merge(
                XPathHelper::getList($this->_xpathh, '
(//*[sg:css-class-contains(@class, "navigation__pages")])[1]
    //a
                    ', fn (\DOMNode $item, int $i): array => [
                        '@type' => 'WebPage'
                        , 'position' => XPathHelper::dummyText($item, $i)
                        , 'name' => 'Страница ' . XPathHelper::dummyText($item, $i)
                        , 'url' => StringHelper::getUrl(XPathHelper::getOneText([$this->_xpathh, $item,], '
.//@href
                        '), $this->_site_url)
                        ,
                    ]
                ), XPathHelper::getList($this->_xpathh, '
//*[sg:css-class-contains(@class, "sorting__item")]
                    ', fn (\DOMNode $item, int $i): array => [
                        '@type' => 'ListItem'
                        , 'position' => $i
                        , 'item' => [
                            '@type' => 'CategoryCode'
                            , 'name' => XPathHelper::dummyText($item, $i)
                            , 'url' => StringHelper::getUrl(XPathHelper::getOneText([$this->_xpathh, $item,], '
.//@href
                            '), $this->_site_url)
                            ,
                        ]
                        ,
                    ]
                )
            )
        ];
    }

    /**
    * Товарная позиция в прайс-листе
    *
    * @param \DOMNode $item
    *
    * @return array
    */
    protected function _getProductsItemListElement(\DOMNode $item): array
    {
        $href = XPathHelper::getOne([$this->_xpathh, $item], './/*[sg:css-class-contains(@class, "product-list__image")]');
        $info = XPathHelper::getOne([$this->_xpathh, $item,], './/*[sg:css-class-contains(@class, "product-list__info")]');
        $price = StringHelper::getPrice(XPathHelper::getOneText([$this->_xpathh, $item,], '
.//datalist
    /option[@value="product_price"]
    /text()
        '));
        $url = StringHelper::getUrl(XPathHelper::getOneText([$this->_xpathh, $href,], '
.//@href
        '), $this->_site_url);
        $offers = [
            '@type' => 'Offer'
            , 'priceCurrency' => 'RUR'
            , 'price' => $price
            , 'url' => $url
            , 'availability' => $price ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
            ,
        ];

        return [
            '@type' => 'ListItem'
            , 'item' => [
                '@type' => 'Book'
                , '@id' => $url
                , 'url' => $url
                , 'description' => XPathHelper::getOneText([$this->_xpathh, $info,], '
.//*[sg:css-class-contains(@class, "product-list__desc")]
                ')
                , 'image' => StringHelper::getUrl(XPathHelper::getOneText([$this->_xpathh, $href,], '
.//img/@src
                '), $this->_site_url)
                , 'name' => XPathHelper::getOneText([$this->_xpathh, $info,], '
.//*[sg:css-class-contains(@class, "product-list__name")]
                ')
                , 'datePublished' => XPathHelper::getOneText([$this->_xpathh, $item,], '
.//datalist/option[@value="product_year"]/text()
                ')
                , 'author' => [
                    '@type' => 'Person'
                    , 'name' => XPathHelper::getOneText([$this->_xpathh, $item,], '
.//datalist/option[@value="product_author"]/text()
                    ')
                    ,
                ]
                , 'offers' => $offers
                ,
            ]
            ,
        ];
    }

    /**
    * Прайс-лист
    *
    * @return array
    */
    protected function _getProducts(): array {
        return [
            '@type' => 'ItemList'
            , 'itemListElement' => XPathHelper::getList($this->_xpathh, '
//*[sg:css-class-contains(@class, "product-list__item")]
                ', fn (\DOMNode $item): array => $this->_getProductsItemListElement($item)
            )
        ];
    }

    /**
    * Текущая товарная позиция
    *
    * @return array
    */
    function _getProduct(): array {
        $price = StringHelper::getPrice(XPathHelper::getOneText($this->_xpathh, '
//*[sg:css-class-contains(@class, "product-info__price")]
    /text()
        '));

        $offers = [
            '@type' => 'Offer'
            , 'priceCurrency' => 'RUR'
            , 'price' => $price
            , 'url' => StringHelper::getUrl($this->_url, $this->_site_url)
            , 'availability' => $price ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
            ,
        ];

        return [
            '@type' => 'Book'
            , 'name' => XPathHelper::getOneText($this->_xpathh, '
//*[sg:css-class-contains(@class, "product-info__name")]
    /text()
            ')
            , 'description' => XPathHelper::getOneText($this->_xpathh, '
//meta[@name="description"]
    /@content
            ')
            , 'url' => StringHelper::getUrl($this->_url, $this->_site_url)
            , 'image' => StringHelper::getUrl(XPathHelper::getOneText($this->_xpathh, '
//*[sg:css-class-contains(@class, "product-info__image")]
    //img
        /@src
            '), $this->_site_url)
            , 'datePublished' => StringHelper::getYear(XPathHelper::getOneText($this->_xpathh, '
(
//*[
    sg:css-class-contains(@class, "product-info__left")][contains(text(), "Год издания")
]/following-sibling::*[
    sg:css-class-contains(@class, "product-info__text")
]
)[1]/text()
            '))
            , 'author' => [
                '@type' => 'Person'
                , 'name' => XPathHelper::getOneText($this->_xpathh, '
(
//*[
    sg:css-class-contains(@class, "product-info__left")][contains(text(), "Автор")
]/following-sibling::*[
    sg:css-class-contains(@class, "product-info__text")
]
)[1]/text()
                ')
                ,
            ]
            , 'offers' => $offers
            ,
        ];
    }

    /**
    * Продавец
    *
    * @return array
    */
    protected function _getProvider(): array {
        return [
            '@type' => 'LocalBusiness'
            , 'name' => $this->_site_name
            , 'address' => [
                '@type' => 'PostalAddress'
                , 'addressLocality' => 'г.Москва'
                , 'streetAddress' => 'м.Смоленская пер. Карманицкий 3А'
                , 'postalCode' => '119002'
                , 'addressCountry' => 'RU'
                ,
            ]
            , 'openingHours' => StringHelper::getOpeningHours(XPathHelper::getOneText($this->_xpathh, '
//*[sg:css-class-contains(@class, "header__schedule")]
    /text()
            '))
            , 'image' => StringHelper::getUrl(XPathHelper::getOneText($this->_xpathh, '
//*[sg:css-class-contains(@class, "header__logo")]
    //img
        /@src
            '), $this->_site_url)
            , 'email' => XPathHelper::getOneText($this->_xpathh, '
(
    //*[sg:css-class-contains(@class, "side-contacts__name")][contains(text(), "@")]
    | //a[starts-with(@href, "mailto:")]
)[1]/text()
            ')
            , 'telephone' => StringHelper::getTelephone(XPathHelper::getOneText($this->_xpathh, '
//*[sg:css-class-contains(@class, "header__phone-item")]/text()
            '))
            ,
        ];
    }

    /**
    * Новости
    *
    * @return array
    */
    protected function _getNews(): array {
        return [
            '@type' => 'CollectionPage'
            , 'name' => 'Новости'
            , 'url' => StringHelper::getUrl('/news', $this->_site_url)
            , 'itemListElement' => XPathHelper::getList($this->_xpathh, '
(//*[sg:css-class-contains(@class, "news__list")])[1]
    //*[sg:css-class-contains(@class, "news__item")]
            ', fn (\DOMNode $item, int $i): array => [
                    '@type' => 'NewsArticle'
                    , 'headline' => XPathHelper::getOneText([$this->_xpathh, $item,], '
.//*[sg:css-class-contains(@class, "news__name")]//a/text()
                    ')
                    , 'url' => StringHelper::getUrl(XPathHelper::getOneText([$this->_xpathh, $item,], '
.//*[sg:css-class-contains(@class, "news__name")]//a/@href
                    '), $this->_site_url)
                    , 'datePublished' => StringHelper::getDate(XPathHelper::getOneText([$this->_xpathh, $item,], '
(
    .//*[sg:css-class-contains(@class, "news__time")]
        | .//*[sg:css-class-contains(@class, "news__desc")][2]
)/text()
                    '))
                    ,
                ]
            )
            ,
        ];
    }

    /**
    * Веб-страница
    *
    * @return array
    */
    protected function _getWebPage(): array {
        $result = [
            '@context' => 'https://schema.org/'
            , '@type' => 'WebPage'
            , 'name' => XPathHelper::getOneText($this->_xpathh, '
//title
    /text()
            ')
            , 'image' => StringHelper::getUrl(XPathHelper::getOneText($this->_xpathh, '
//*[sg:css-class-contains(@class, "header__logo")]
    //img
        /@src
            '), $this->_site_url)
            , 'description' => XPathHelper::getOneText($this->_xpathh, '
//meta[@name="description"]
    /@content
            ')
            , 'keywords' => XPathHelper::getOneText($this->_xpathh, '
//meta[@name="keywords"]
    /@content
            ')
            , 'inLanguage' => 'ru-RU'
            , 'breadcrumb' => $this->_getBreadcrumbs()
            , 'hasPart' => $this->_getNews()
            , 'offers' => $this->_getProducts()
            , 'provider' => $this->_getProvider()
            ,
        ];

        if (
            preg_match('{^(?:delivery|contacts|news)/}uis', $this->_url)
            || ($mainEntity = $this->_getPagination())
        ) return $result;

        if (empty($mainEntity['itemListElement']))
            $mainEntity = $this->_getProduct();

        $result['mainEntity'] = $mainEntity;

        return $result;
    }
}