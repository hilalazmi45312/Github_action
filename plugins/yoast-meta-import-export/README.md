# Yoast Meta Import/Export

A comprehensive WordPress plugin for exporting and importing Yoast SEO meta fields as CSV files. Perfect for enterprise websites with complex SEO requirements.

## Features

- **Selective Export**: Choose specific post types and taxonomies to export
- **Batch Import**: Process large CSV files in batches with progress tracking
- **Security First**: Server-side processing with nonce verification and permission checks
- **UTF-8 Support**: Proper encoding for international characters with BOM
- **Rate Limiting**: Built-in delays to prevent server overload
- **Dry Run Mode**: Test imports without making changes

## Installation

1. Download the plugin files
2. Upload to your WordPress plugins directory (`wp-content/plugins/`)
3. Activate the plugin through the WordPress admin
4. Ensure Yoast SEO is installed and active (required dependency)

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Yoast SEO plugin (must be active)
- Modern browser with JavaScript enabled

## Usage

### Export

1. Navigate to **Tools → Yoast Meta Import/Export** in your WordPress admin
2. Click the **Export** tab
3. Select the post types you want to export (posts, pages, custom post types)
4. Select the taxonomies you want to export (categories, tags, custom taxonomies)
5. Click **Export CSV** to download your data

### Import

1. Navigate to **Tools → Yoast Meta Import/Export** in your WordPress admin
2. Click the **Import** tab
3. Configure your import options (see below)
4. Drag and drop your CSV file or click to select it
5. Click **Proceed with Import**

> **⚠️ IMPORTANT**: Only import CSV files that you exported from this plugin and manually edited yourself. Do not import CSV files from external sources or other tools, as they may have incorrect formatting or contain malicious data that could damage your site.

## Import Options Explained

### Dry Run (Preview Only)
- **What it does**: Tests the import process without making any actual changes to your database
- **When to use**: Always run this first to verify your CSV data is correct and will import as expected
- **Benefits**: Shows you exactly how many items will be updated without risking your live data
- **Note**: No changes are made to your site during a dry run

### Force Import (Overwrite Existing)
- **What it does**: Overwrites existing Yoast meta values even if they already contain data
- **When to use**: When you want to completely replace existing SEO data with new values
- **Caution**: This will replace any existing meta titles, descriptions, and other SEO settings
- **Default**: Off (safer option)

### Clear Existing Meta
- **What it does**: Removes ALL existing Yoast meta data for the items being imported before adding new data
- **When to use**: When you want a completely clean slate for the imported items
- **Caution**: This permanently deletes existing SEO data - use with extreme caution
- **Note**: Only affects items that are present in your CSV file

## CSV Format

Your CSV file should have the following structure:

```csv
ID,Type,Type_Value,Title/Name,_yoast_wpseo_title,_yoast_wpseo_metadesc,_yoast_wpseo_focuskw,_yoast_wpseo_canonical,etc...
1,post,post,"Hello World","SEO Title","Meta description","focus keyword","https://example.com/canonical/",...
1,term,category,"Uncategorized","Category SEO Title","Category meta description",,,...
```

### Column Explanations

- **ID**: The WordPress ID of the post/term
- **Type**: Either "post" or "term"
- **Type_Value**: The post type (for posts) or taxonomy name (for terms)
- **Title/Name**: The title of the post or name of the term
- **Yoast Fields**: All Yoast SEO meta fields (see complete list below)

### Supported Yoast Meta Fields

The plugin exports and imports all standard Yoast SEO meta fields:

#### Post/Term Meta Fields
- `_yoast_wpseo_title` - SEO Title
- `_yoast_wpseo_metadesc` - Meta Description
- `_yoast_wpseo_focuskw` - Focus Keyword
- `_yoast_wpseo_canonical` - Canonical URL
- `_yoast_wpseo_opengraph-title` - Open Graph Title
- `_yoast_wpseo_opengraph-description` - Open Graph Description
- `_yoast_wpseo_opengraph-image` - Open Graph Image
- `_yoast_wpseo_twitter-title` - Twitter Title
- `_yoast_wpseo_twitter-description` - Twitter Description
- `_yoast_wpseo_twitter-image` - Twitter Image
- `_yoast_wpseo_bctitle` - Breadcrumb Title
- `_yoast_wpseo_meta-robots-noindex` - No Index
- `_yoast_wpseo_meta-robots-nofollow` - No Follow
- `_yoast_wpseo_meta-robots-adv` - Advanced Robots Meta

## Best Practices

### Before Importing
1. **Always run a Dry Run first** to verify your data
2. **Test on a staging site** before importing to production
3. **Verify your CSV encoding** is UTF-8 to prevent character issues
4. **⚠️ Only import files you exported**: Never import CSV files from external sources or other tools - only use files exported by this plugin that you have manually edited yourself

### During Import
- **Monitor progress** - the plugin shows real-time progress for large imports
- **Don't close the browser** during import - let the process complete
- **Check for errors** - any issues will be displayed in the results

### After Import
- **Verify your changes** by checking a few pages/posts
- **Update your XML sitemap** if you made significant changes
- **Clear any caching plugins** to ensure changes are visible

## Troubleshooting

### Common Issues

**"Yoast SEO plugin not found"**
- Ensure Yoast SEO is installed and activated
- The plugin will automatically deactivate itself if Yoast is not present

**"Import failed"**
- Check your CSV format matches the expected structure
- Ensure all required columns are present
- Verify the IDs in your CSV correspond to existing posts/terms
- **⚠️ Only import files you exported**: Make sure you're importing a file that was exported by this plugin and manually edited by you

**"Character encoding issues"**
- Save your CSV file as UTF-8 encoded
- Check for special characters in your meta fields

**"Rate limiting errors"**
- The plugin includes automatic delays between batches
- For very large imports, consider splitting into smaller files

### Getting Help

If you encounter issues:
1. Check the WordPress error logs
2. Verify your server meets the requirements
3. Test with a small CSV file first
4. Ensure you have proper file permissions

## Security Notes

- CSV parsing is performed client-side for better performance
- All database operations happen server-side with proper validation
- Nonce verification prevents unauthorized requests
- User permission checks ensure only administrators can perform operations
- File uploads are limited to CSV format only
- Input sanitization prevents malicious data injection

## License

GPL v2 or later

## Support

This plugin is provided as-is. Always backup your database before performing imports. Use at your own risk.

**⚠️ Critical Warning**: Only import CSV files that you have exported from this plugin yourself. Importing files from external sources, other tools, or unknown origins can cause data corruption, security vulnerabilities, or complete loss of your SEO data.

## Changelog

### Version 0.0.1
- Initial release
- Selective export functionality
- Batch import with progress tracking
- Import options (dry run, force, clear existing)
- UTF-8 encoding support with BOM
- Rate limiting and error handling</content>
<parameter name="filePath">c:\Users\Kavit\Local Sites\rgvb-prod\app\public\wp-content\plugins\yoast-meta-import-export\README.md