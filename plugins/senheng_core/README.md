# Senheng Core Plugin

This plugin contains all custom functionality, integrations, and overrides specific to the Senheng WooCommerce implementation.

It serves as a central place for:
- Custom business logic
- Third-party API integrations
- Overrides for existing themes or plugins
- Developer utilities and helpers

---

## 📁 Folder Structure

senheng-core/
├── assets/
│   └── js/
│   └── css/
├── app/
│   ├── Controllers/
│   ├── Models/
│   └── Views/
├── routes/
│   └── web.php
├── senheng-core.php
├── env.php
└── bootstrap.php



## 🧩 Features

- Modular file structure for maintainability
- `dd()` helper for debugging (like Laravel)
- `write_log()` utility for custom structured logging
- Safe plugin/theme overrides using WordPress hooks
- Integrations with external systems like Senheng backend (via REST API)

---

## 🔧 Development

### Debugging

```php
$user = wp_get_current_user();
dd($user);
````

### Logging

```php
write_log('loyalty.log', $_POST, $_SERVER);
```

---

## 🛠 Setup & Deployment

1. Clone this plugin into `wp-content/plugins/`

2. Activate the plugin via WordPress admin or WP-CLI:

   ```bash
   wp plugin activate senheng-core
   ```

3. To add new modules:

   * Create a new file under `inc/integrations/` or `inc/overrides/`
   * Register it inside `inc/init.php`

---

## 📌 Best Practices

* Prefix all functions/classes with `senheng_` or `sh_`
* Never modify third-party plugins directly — override via hooks in `overrides/`
* Keep integration logic modular and testable
* Use WP-CLI or REST endpoints for batch jobs when needed
* Always test on staging before pushing to production

---

## 🧠 Future Enhancements

* Class-based autoloading (PSR-4)
* Custom REST API routes
* WP-CLI commands
* Admin settings page (if needed)
