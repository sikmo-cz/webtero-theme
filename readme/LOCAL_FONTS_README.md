# Local Fonts Feature

This theme includes a custom local fonts management system that downloads and serves Google Fonts locally from `fonts.webtero.com`.

## How It Works

1. **Font List Update**: Once a day, the system fetches the latest font list from `https://fonts.webtero.com/` and stores it in WordPress options.

2. **Font Selection**: All font-family fields in Theme Options (General and Language-specific) are populated with the available fonts.

3. **Automatic Download**: When you save Theme Options with a font selected, the system automatically:
   - Downloads the font CSS file from `https://fonts.webtero.com/fonts/{font-name}/font.css`
   - Downloads WOFF files for regular and bold variants
   - Saves them to `wp-content/themes/webtero-child/fonts/{font-name}/`
   - Updates CSS paths to use local files

4. **Frontend Loading**: Used fonts are automatically enqueued on the frontend via `wp_enqueue_style`.

## File Structure

Fonts are saved in the **webtero-child** theme directory:

```
wp-content/themes/webtero-child/
└── fonts/
    ├── work-sans/
    │   ├── font.css
    │   ├── work-sans-regular.woff
    │   └── work-sans-bold.woff
    ├── yanone-kaffeesatz/
    │   ├── font.css
    │   ├── yanone-kaffeesatz-regular.woff
    │   └── yanone-kaffeesatz-bold.woff
    └── ...
```

## Font Fields

The following fields support font selection:
- `button_font_family` - Button typography
- `heading_1_font_family` - H1 headings
- `heading_2_font_family` - H2 headings
- `heading_3_font_family` - H3 headings
- `heading_4_font_family` - H4 headings
- `text_font_family` - Body text

## Manual Operations

### Force Update Font List

To manually update the font list from fonts.webtero.com:

```php
// In WordPress admin or via WP-CLI
if ( class_exists( 'WT\Local_Fonts' ) ) {
    $local_fonts = new WT\Local_Fonts();
    $local_fonts->update_font_list();
}
```

### Clean Up Unused Fonts

To remove font files that are no longer in use:

```php
if ( class_exists( 'WT\Local_Fonts' ) ) {
    $local_fonts = new WT\Local_Fonts();
    $local_fonts->cleanup_unused_fonts();
}
```

### Download a Specific Font

To manually download a font:

```php
if ( class_exists( 'WT\Local_Fonts' ) ) {
    $local_fonts = new WT\Local_Fonts();
    $local_fonts->download_font( 'work-sans' ); // Font slug
}
```

## WordPress Options

- `webtero_google_fonts_list` - Stores the complete font list JSON
- `webtero_google_fonts_last_update` - Timestamp of last font list update
- `webtero-theme-options_active` - Active version timestamp for general options
- `webtero-theme-options_{timestamp}` - Versioned theme options (JSON encoded)
- `webtero-theme-options_{lang}_active` - Active version timestamp for language-specific options
- `webtero-theme-options_{lang}_{timestamp}` - Versioned language-specific options

## Hooks and Filters

### Actions

- `webtero_update_font_list` - Scheduled daily to update font list
- `webtero/theme/options/saved` - Triggers font download when options are saved

### Filters

- `webtero/local_fonts/language_instances` - Filter language instances for multi-language support

## Font Server Structure

Fonts are hosted at `fonts.webtero.com` with this structure:

- Font list: `https://fonts.webtero.com/` (JSON)
- Font CSS: `https://fonts.webtero.com/fonts/{font-slug}/font.css`
- Regular WOFF: `https://fonts.webtero.com/fonts/{font-slug}/{font-slug}-regular.woff`
- Bold WOFF: `https://fonts.webtero.com/fonts/{font-slug}/{font-slug}-bold.woff`

## Troubleshooting

### Fonts not showing in dropdown
1. Check if `webtero_google_fonts_list` option exists in wp_options
2. Manually trigger font list update (see above)
3. Check error logs for API issues

### Fonts not loading on frontend
1. Verify font files exist in `webtero-child/fonts/`
2. Check if font is selected in Theme Options
3. Clear browser cache and WordPress cache
4. Check file permissions on fonts directory

### Font files not downloading
1. Check server can make outbound HTTPS requests
2. Verify fonts.webtero.com is accessible
3. Check error logs for download failures
4. Ensure `webtero-child/fonts/` directory is writable
