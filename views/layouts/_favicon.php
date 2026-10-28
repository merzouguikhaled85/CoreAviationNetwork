<?php
/** Shared application icons; file versions invalidate browser favicon caches. */
$icons = [
    ['icon', 'image/x-icon', 'favicon.ico', null],
    ['icon', 'image/png', 'favicon-32x32.png', '32x32'],
    ['icon', 'image/png', 'favicon.png', '512x512'],
    ['apple-touch-icon', 'image/png', 'apple-touch-icon.png', '180x180'],
];
foreach ($icons as [$rel, $type, $filename, $sizes]) {
    $options = [
        'rel' => $rel,
        'type' => $type,
        'href' => Yii::getAlias('@web/' . $filename) . '?v=' . filemtime(Yii::getAlias('@webroot/' . $filename)),
    ];
    if ($sizes !== null) {
        $options['sizes'] = $sizes;
    }
    $this->registerLinkTag($options);
}
