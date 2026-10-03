<?php
/**
 * UTF-8 and Multibyte polyfills & string helpers using PCRE /u
 */

declare(strict_types=1);

namespace App\Core;

class Utf8
{
    private static array $cyrUpper = [
        'а'=>'А','б'=>'Б','в'=>'В','г'=>'Г','д'=>'Д','е'=>'Е','ё'=>'Ё','ж'=>'Ж','з'=>'З',
        'и'=>'И','й'=>'Й','к'=>'К','л'=>'Л','м'=>'М','н'=>'Н','о'=>'О','п'=>'П','р'=>'Р',
        'с'=>'С','т'=>'Т','у'=>'У','ф'=>'Ф','х'=>'Х','ц'=>'Ц','ч'=>'Ч','ш'=>'Ш','щ'=>'Щ',
        'ъ'=>'Ъ','ы'=>'Ы','ь'=>'Ь','э'=>'Э','ю'=>'Ю','я'=>'Я'
    ];

    private static array $cyrLower = [
        'А'=>'а','Б'=>'б','В'=>'в','Г'=>'г','Д'=>'д','Е'=>'е','Ё'=>'ё','Ж'=>'ж','З'=>'з',
        'И'=>'и','Й'=>'й','К'=>'к','Л'=>'л','М'=>'м','Н'=>'н','О'=>'о','П'=>'п','Р'=>'р',
        'С'=>'с','Т'=>'т','У'=>'у','Ф'=>'ф','Х'=>'х','Ц'=>'ц','Ч'=>'ч','Ш'=>'ш','Щ'=>'щ',
        'Ъ'=>'ъ','Ы'=>'ы','Ь'=>'ь','Э'=>'э','Ю'=>'ю','Я'=>'я'
    ];

    public static function strlen(string $str): int
    {
        if (extension_loaded('mbstring')) {
            return \mb_strlen($str, 'UTF-8');
        }
        return preg_match_all('/./us', $str, $matches);
    }

    public static function substr(string $str, int $start, ?int $length = null): string
    {
        if (extension_loaded('mbstring')) {
            return \mb_substr($str, $start, $length, 'UTF-8');
        }
        if (!preg_match_all('/./us', $str, $matches)) {
            return '';
        }
        $chars = $matches[0];
        $slice = array_slice($chars, $start, $length);
        return implode('', $slice);
    }

    public static function strtolower(string $str): string
    {
        if (extension_loaded('mbstring')) {
            return \mb_strtolower($str, 'UTF-8');
        }
        $str = strtolower($str);
        return strtr($str, self::$cyrLower);
    }

    public static function strtoupper(string $str): string
    {
        if (extension_loaded('mbstring')) {
            return \mb_strtoupper($str, 'UTF-8');
        }
        $str = strtoupper($str);
        return strtr($str, self::$cyrUpper);
    }

    public static function slugify(string $text): string
    {
        $text = self::transliterate(self::strtolower(trim($text)));
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        return trim($text, '-');
    }

    public static function transliterate(string $text): string
    {
        $table = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh',
            'з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
            'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts',
            'ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu',
            'я'=>'ya'
        ];
        return strtr($text, $table);
    }

    public static function fixKeyboardLayout(string $text): string
    {
        $enToRu = [
            'q'=>'й','w'=>'ц','e'=>'у','r'=>'к','t'=>'е','y'=>'н','u'=>'г','i'=>'ш','o'=>'щ','p'=>'з','['=>'х',']'=>'ъ',
            'a'=>'ф','s'=>'ы','d'=>'в','f'=>'а','g'=>'п','h'=>'р','j'=>'о','k'=>'л','l'=>'д',';'=>'ж','\''=>'э',
            'z'=>'я','x'=>'ч','c'=>'с','v'=>'м','b'=>'и','n'=>'т','m'=>'ь',','=>'б','.'=>'ю'
        ];
        $ruToEn = array_flip($enToRu);
        $lower = self::strtolower($text);
        
        // If string contains mostly latin chars
        if (preg_match('/^[a-z0-9\s\[\];,.\'"`-]+$/i', $text)) {
            return strtr($lower, $enToRu);
        }
        // If string contains mostly cyrillic
        return strtr($lower, $ruToEn);
    }
}
