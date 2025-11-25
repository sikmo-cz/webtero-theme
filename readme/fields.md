Usage
------

Get text field

```
$site_title = webtero_get_option( 'site_title' );
echo esc_html( $site_title );
```

Get number field
```
$posts_per_page = webtero_get_option( 'posts_per_page', '', null, 10 );
```

Get checkbox (returns boolean)
```
$show_breadcrumbs = webtero_get_option( 'enable_breadcrumbs' );
if ( $show_breadcrumbs ) {
    // Display breadcrumbs
}
```

Get color
```
$primary_color = webtero_get_option( 'primary_color', '', null, '#0073aa' );
echo '<style>:root { --primary-color: ' . esc_attr( $primary_color ) . '; }</style>';
```

Get select/radio value
```
$sidebar_position = webtero_get_option( 'sidebar_position', '', null, 'right' );
```

Get media field (returns attachment ID)
```
$logo_id = webtero_get_option( 'site_logo' );
if ( $logo_id ) {
    $logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
    echo '<img src="' . esc_url( $logo_url ) . '" alt="Logo">';
}
```

Get from specific language instance
```
$site_title_cs = webtero_get_option( 'site_title', 'cs' ); // Czech version
$site_title_en = webtero_get_option( 'site_title', 'en' ); // English version
```

Get from specific version
```
$old_value = webtero_get_option( 'site_title', '', 1699891234 ); // Specific timestamp
```

Fields
------

```
// Toggle field
[
    'type' => 'toggle',
    'id' => 'enable_feature',
    'label' => __( 'Enable Feature', 'webtero' ),
    'toggle_label' => __( 'Turn on this feature', 'webtero' ),
    'default' => 1,
    'width' => '50'
]

// Custom labels (On/Off)
[
    'type' => 'toggle',
    'id' => 'maintenance_mode',
    'label' => __( 'Maintenance Mode', 'webtero' ),
    'label_on' => __( 'On', 'webtero' ),
    'label_off' => __( 'Off', 'webtero' ),
    'toggle_label' => __( 'Enable maintenance mode', 'webtero' ),
    'default' => 0,
    'width' => '50'
]

// Custom labels (Enabled/Disabled)
[
    'type' => 'toggle',
    'id' => 'cache_enabled',
    'label' => __( 'Cache', 'webtero' ),
    'label_on' => __( 'Enabled', 'webtero' ),
    'label_off' => __( 'Disabled', 'webtero' ),
    'default' => 1,
    'width' => '50'
]

// Custom labels (Active/Inactive)
[
    'type' => 'toggle',
    'id' => 'feature_status',
    'label' => __( 'Feature Status', 'webtero' ),
    'label_on' => __( 'Active', 'webtero' ),
    'label_off' => __( 'Inactive', 'webtero' ),
    'width' => '50'
]

// Without description
[
    'type' => 'toggle',
    'id' => 'simple_toggle',
    'label' => __( 'Simple Toggle', 'webtero' ),
    'default' => 0,
    'width' => '50'
]
```

```
// Button group (radio style)
[
    'type' => 'button_group',
    'id' => 'layout_style',
    'label' => __( 'Layout Style', 'webtero' ),
    'options' => [
        'boxed' => '📦 Boxed',
        'wide' => '↔️ Wide',
        'full' => '⬛ Full Width'
    ],
    'default' => 'wide',
    'width' => '50'
]
```

```
// Button group (checkbox style - multiple)
[
    'type' => 'button_group',
    'id' => 'enabled_features',
    'label' => __( 'Features', 'webtero' ),
    'multiple' => true,
    'options' => [
        'search' => '🔍 Search',
        'cart' => '🛒 Cart',
        'wishlist' => '❤️ Wishlist'
    ],
    'width' => '100'
]
```

```
// Enhanced select
[
    'type' => 'enhanced_select',
    'id' => 'default_category',
    'label' => __( 'Default Category', 'webtero' ),
    'options' => [
        '1' => 'News',
        '2' => 'Blog',
        '3' => 'Updates'
    ],
    'searchable' => true,
    'placeholder' => __( 'Choose category...', 'webtero' ),
    'width' => '33'
]
```

```
// Enhanced multi-select
[
    'type' => 'enhanced_select',
    'id' => 'allowed_post_types',
    'label' => __( 'Post Types', 'webtero' ),
    'multiple' => true,
    'options' => [
        'post' => 'Posts',
        'page' => 'Pages',
        'product' => 'Products'
    ],
    'width' => '50'
]
```

```
// TEXT FIELD
[
    'type' => 'text',
    'id' => 'site_title',
    'name' => 'site_title',
    'label' => __( 'Site Title', 'webtero' ),
    'description' => __( 'Enter your website title', 'webtero' ),
    'placeholder' => __( 'My Awesome Website', 'webtero' ),
    'default' => '',
    'class' => 'regular-text',
    'attributes' => [
        'data-custom' => 'value'
    ]
]
```

```
// NUMBER FIELD
[
    'type' => 'number',
    'id' => 'posts_per_page',
    'name' => 'posts_per_page',
    'label' => __( 'Posts Per Page', 'webtero' ),
    'description' => __( 'Number of posts to display per page', 'webtero' ),
    'default' => 10,
    'min' => 1,
    'max' => 100,
    'step' => 1,
    'class' => 'small-text'
]
```

```
// RANGE FIELD
[
    'type' => 'range',
    'id' => 'content_width',
    'name' => 'content_width',
    'label' => __( 'Content Width (%)', 'webtero' ),
    'description' => __( 'Adjust the content width percentage', 'webtero' ),
    'default' => 80,
    'min' => 50,
    'max' => 100,
    'step' => 5
]
```

```
// TEXTAREA FIELD
[
    'type' => 'textarea',
    'id' => 'footer_text',
    'name' => 'footer_text',
    'label' => __( 'Footer Text', 'webtero' ),
    'description' => __( 'Text to display in footer', 'webtero' ),
    'placeholder' => __( 'Copyright 2024...', 'webtero' ),
    'default' => '',
    'rows' => 5,
    'class' => 'large-text'
]
```

```
// CHECKBOX FIELD
[
    'type' => 'checkbox',
    'id' => 'enable_breadcrumbs',
    'name' => 'enable_breadcrumbs',
    'label' => __( 'Breadcrumbs', 'webtero' ),
    'checkbox_label' => __( 'Enable breadcrumbs navigation', 'webtero' ),
    'description' => __( 'Show breadcrumbs on pages', 'webtero' ),
    'default' => 1
]
```

```
// COLOR FIELD
[
    'type' => 'color',
    'id' => 'primary_color',
    'name' => 'primary_color',
    'label' => __( 'Primary Color', 'webtero' ),
    'description' => __( 'Choose your primary brand color', 'webtero' ),
    'default' => '#0073aa'
]
```

```
// RADIO FIELD
[
    'type' => 'radio',
    'id' => 'sidebar_position',
    'name' => 'sidebar_position',
    'label' => __( 'Sidebar Position', 'webtero' ),
    'description' => __( 'Choose where to display the sidebar', 'webtero' ),
    'default' => 'right',
    'options' => [
        'left' => __( 'Left', 'webtero' ),
        'right' => __( 'Right', 'webtero' ),
        'none' => __( 'No Sidebar', 'webtero' )
    ]
]
```

```
// SELECT FIELD
[
    'type' => 'select',
    'id' => 'header_style',
    'name' => 'header_style',
    'label' => __( 'Header Style', 'webtero' ),
    'description' => __( 'Select header layout style', 'webtero' ),
    'default' => 'default',
    'options' => [
        'default' => __( 'Default', 'webtero' ),
        'centered' => __( 'Centered', 'webtero' ),
        'minimal' => __( 'Minimal', 'webtero' ),
        'split' => __( 'Split Layout', 'webtero' )
    ],
    'class' => 'regular-text'
]
```

```
// MEDIA FIELD (Image)
[
    'type' => 'media',
    'id' => 'site_logo',
    'name' => 'site_logo',
    'label' => __( 'Site Logo', 'webtero' ),
    'description' => __( 'Upload your website logo', 'webtero' ),
    'default' => ''
]
```