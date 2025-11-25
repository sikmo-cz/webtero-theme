Button classes

```
add_filter( 'webtero/tiptap/button_classes', function( $classes ) {
    return [
        'Primary'   => 'btn-primary',
        'Full Width' => 'btn-block',
    ];
});
```

$options_array = apply_filters( 'webtero/block/options', $options_array, $block, $block_content );

```
add_filter( 'webtero/block/options', function( $options_array, $block, $block_content ) {
    $options_array[ 'custom-data' ] = true;
    return $options_array;
});
```

View files

```
add_filter( 'webtero/theme/load_view', function( $view ) {
    if( is_front_page() ) {
        $view = 'template-parts/front-page';
    }

    return $view;
});
```