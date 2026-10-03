<?php
declare(strict_types=1);

$dir = dirname(__DIR__) . '/public/assets/img/p';
@mkdir($dir, 0775, true);

$cats = [
    10 => ['CPU', '#1E88E5', 'Процессор'],
    11 => ['MB', '#6A1B9A', 'Материнская плата'],
    12 => ['RAM', '#FB8C00', 'Память DDR5'],
    13 => ['SSD', '#43A047', 'Накопитель'],
    14 => ['GPU', '#E53935', 'Видеокарта'],
    15 => ['PSU', '#D84315', 'Блок питания'],
    16 => ['CASE', '#455A64', 'Корпус'],
    17 => ['COOL', '#0288D1', 'Кулер / СЖО'],
    20 => ['LAPTOP', '#5E35B1', 'Ноутбук'],
    21 => ['MON', '#00838F', 'Монитор'],
    22 => ['PC', '#2E7D32', 'Готовый ПК'],
    30 => ['PHONE', '#00ACC1', 'Смартфон'],
    31 => ['TAB', '#8E24AA', 'Планшет'],
    32 => ['WATCH', '#C2185B', 'Смарт-часы'],
    33 => ['AUDIO', '#D81B60', 'Наушники'],
    40 => ['TV', '#3949AB', 'Телевизор 4K'],
    50 => ['ROBOT', '#00897B', 'Робот-пылесос']
];

foreach ($cats as $id => [$code, $color, $label]) {
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
  <rect width="100%" height="100%" fill="#F8F9FA" rx="12"/>
  <rect x="20" y="20" width="260" height="260" fill="#FFFFFF" stroke="#E4E7EB" stroke-width="2" rx="10"/>
  <circle cx="150" cy="130" r="70" fill="{$color}" fill-opacity="0.12"/>
  <rect x="105" y="85" width="90" height="90" rx="16" fill="{$color}"/>
  <text x="150" y="142" font-family="system-ui, sans-serif" font-weight="900" font-size="28" fill="#FFFFFF" text-anchor="middle">{$code}</text>
  <text x="150" y="225" font-family="system-ui, sans-serif" font-weight="600" font-size="16" fill="#1F2328" text-anchor="middle">{$label}</text>
</svg>
SVG;
    file_put_contents("{$dir}/{$id}.svg", $svg);
}

// Default placeholder
$placeholder = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
  <rect width="100%" height="100%" fill="#F8F9FA" rx="12"/>
  <rect x="20" y="20" width="260" height="260" fill="#FFFFFF" stroke="#E4E7EB" stroke-width="2" rx="10"/>
  <circle cx="150" cy="130" r="60" fill="#8A929A" fill-opacity="0.15"/>
  <text x="150" y="140" font-family="system-ui, sans-serif" font-weight="bold" font-size="32" fill="#8A929A" text-anchor="middle">TECH</text>
  <text x="150" y="220" font-family="system-ui, sans-serif" font-weight="500" font-size="14" fill="#5B636B" text-anchor="middle">Электроника</text>
</svg>
SVG;
file_put_contents(dirname(__DIR__) . '/public/assets/img/placeholder.svg', $placeholder);
echo "SVGs generated.\n";
