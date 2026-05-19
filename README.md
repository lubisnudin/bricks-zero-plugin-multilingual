# Bricks Zero-Plugin Multilingual

A lightweight, zero-plugin multilingual implementation for WordPress Multisite and Bricks Builder. 

This repository provides a step-by-step guide and custom PHP snippets to build a high-performance, multilingual website. By completely avoiding heavy translation plugins like WPML or Polylang, this approach guarantees a clean database, blazing-fast server performance, and perfectly structured Technical SEO (Hreflang) natively.

---

## ⚖️ Why Multisite Instead of Plugins? (Pros & Cons)

Before diving into the architecture, here is a quick comparison of why taking the Multisite route is often superior for enterprise-level or performance-critical websites compared to traditional multilingual plugins (like WPML or Polylang).

| Feature / Aspect | Traditional Plugins (WPML, Polylang) | WP Multisite Method (This Repo) |
| :--- | :--- | :--- |
| **Performance (TTFB)** | ❌ Slower. Runs heavy database queries to filter locales on every single page load. | ✅ **Blazing Fast.** Uses native WordPress routing. Zero locale-filtering overhead. |
| **Database Health** | ❌ Bloated. Mixes all languages in one `wp_posts` and `wp_options` table. | ✅ **Clean & Isolated.** Each language gets its own dedicated database tables (e.g., `wp_2_posts`). |
| **Page Builder Compatibility** | ❌ High risk of conflicts, bugs, and broken layouts during plugin updates. | ✅ **100% Native.** Bricks Builder runs natively on each sub-site without interference. |
| **Design Flexibility** | ❌ Often forces the exact same layout across all languages. | ✅ **Independent.** Allows completely different layouts or structures for specific languages if needed. |
| **Setup Complexity** | ✅ Easy plug-and-play UI for beginners. | ❌ Requires initial technical setup for network routing and custom PHP (Solved by this guide). |
| **Content Syncing** | ✅ Menus and widgets sync automatically. | ❌ Requires manual setup for menus and global templates per sub-site. |

If your priority is **raw performance, database cleanliness, and maximum stability**, the Multisite approach is the undisputed winner.

---

## 🏗️ Architecture & Concept

Heavy multilingual plugins often create massive database overhead by constantly querying locales on every page load. This implementation bypasses that by leveraging **WordPress Multisite (WPMU)** to physically isolate databases, while utilizing **Secure Custom Fields (SCF) / Advanced Custom Fields (ACF)** to handle exact 1-to-1 URL translations.

**Domain Structure Example:**
* **Primary Site (English):** `https://domain.com`
* **Translated Site (Chinese):** `https://domain.com/zh`

---

## ⚙️ Step-by-Step Implementation

### 📑 Table of Contents
1. [WordPress Multisite Initialization](#1-wordpress-multisite-initialization)
2. [Custom Fields Setup (URL Mapping)](#2-custom-fields-setup-url-mapping)
3. [Core PHP Engine Deployment](#3-core-php-engine-deployment)
4. [Bricks Builder UI Configuration](#4-bricks-builder-ui-configuration)
5. [Content Workflow Best Practices](#5-content-workflow-best-practices)

---

### 1. WordPress Multisite Initialization

**Convert your single WordPress installation into a Multisite network using the Sub-directory structure.**

#### 1.1 Allow Multisite
Open your `wp-config.php` file in the root directory and add the following line just **above** the `/* That's all, stop editing! Happy publishing. */` line:

```php
/* Multisite */
define( 'WP_ALLOW_MULTISITE', true );
```

Save the file and log back into your WordPress dashboard.

#### 1.2 Network Setup
Navigate to Tools > Network Setup.

Select the Sub-directories option (crucial for this architecture).

Fill in your Network Title and Admin Email, then click Install.

#### 1.3 Advanced Configuration & Routing
WordPress will generate configuration rules. Apply them carefully to avoid infinite redirect loops (ERR_TOO_MANY_REDIRECTS):

1. Update wp-config.php: Add these lines directly below the code you added in step 1.1:

```php
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', false );
define( 'DOMAIN_CURRENT_SITE', 'domain.com' ); // Replace with your actual domain
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );
```

2. Update .htaccess: Completely replace your default WordPress rules with these exact Sub-directory rules:

```apache
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]

# add a trailing slash to /wp-admin
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin$ $1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) $2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*\.php)$ $2 [L]
RewriteRule . index.php [L]
```

#### 1.4 Create the Translated Sub-site
Log in again (clear your browser cookies or use an Incognito window if you hit a redirect loop).

Go to My Sites > Network Admin > Sites > Add New.

Set the Site Address (URL) to zh (or your target language code).

Fill in the Site Title and Admin Email, then click Add Site.

---

### 2. Custom Fields Setup (URL Mapping)

To map translations accurately and maintain localized SEO URLs, create a custom field to link pages.

1. Install and network-activate **Secure Custom Fields (SCF)** or **Advanced Custom Fields (ACF)**.
2. Create a new Field Group named **Language Settings**.
3. Add a field with the following configuration:
   * **Field Label:** Translated URL
   * **Field Name:** `translated_url`
   * **Field Type:** URL (or Text)
4. Set the **Location Rules** to show this field group on Post types or Pages that require translation.

---

### 3. Core PHP Engine Deployment

The logic for running the dynamic language switcher and generating technical SEO `hreflang` tags is isolated in a separate file.

* **File to reference:** `multilingual-engine.php` (We will create this file in the next step).

#### How to deploy:
Copy the code inside `multilingual-engine.php` and paste it into:
* Your child theme's `functions.php` file, or
* A code snippet manager plugin (activated Network-wide).

---

### 4. Bricks Builder UI Configuration

Authorize the custom function and bind it to your visual elements inside Bricks Builder. Since recent security updates, Bricks requires explicit permission to execute custom PHP functions.

#### 4.1 Enable Code Execution
1. Go to **Bricks > Settings > Custom Code**.
2. Under the **Code Execution** section, enable it for the **Administrator** role.
3. Click **Save Settings**.

#### 4.2 Whitelist the Function
The provided `multilingual-engine.php` already contains the necessary filter to whitelist our function. However, if you are adding it manually, ensure this hook is active in your code:
```php
add_filter( 'bricks/code/echo_function_names', function() {
  return [
    'get_language_switcher_url',
  ];
} );
```

#### 4.3 Map the Dynamic Tag to your Elements
1. Open your Header template in Bricks Builder.
2. Select your Language Switcher element (e.g., Button, Text, or Link).
3. Set the link type to **Dynamic Data**.
4. Manually type the following dynamic tag into the URL field:
   `{echo:get_language_switcher_url}`
5. Press **ENTER** on your keyboard to lock it in, then save the page.

---

### 5. Content Workflow Best Practices

To maintain a clean system, apply different workflows for structural templates and regular page content:

* **Global Templates (Header/Footer):** Build them once on the primary site, export as JSON, and import into the sub-site. If you update the layout on the primary site, you will need to re-export/import. 
  * *(Coming Soon: An optional PHP script to automatically sync Bricks Global Templates across the Multisite network).*
* **Content Pages (Home, About, Services):** Use the native WordPress export tool (**Tools > Export > Pages**) on the primary site and import the XML file into the translated sub-site (**Tools > Import**). Let your translation team edit the text directly on the canvas without auto-syncing to avoid data loss.

---

## Optional: Shared Media Library (Centralized Media)

By default, WordPress Multisite isolates the media library for each sub-site. To save server disk space, avoid duplicate image uploads, and maintain a single source of truth for your assets, you can force all sub-sites to use the Primary Site's Media Library (Blog ID 1).

I have provided two standalone implementation files in this repository depending on your tech stack:

### Option A: Standard WP Media Library
If you are using the default WordPress Media Library without any folder management plugins, use this script.
👉 **File:** [`standard-media-sync.php`](./standard-media-sync.php)

### Option B: HappyFiles Compatibility
If you use the popular **HappyFiles** plugin to organize your media in Bricks Builder, use this extended script. It includes additional AJAX and REST API interceptors to sync your folder structures across the network.
👉 **File:** [`happyfiles-media-sync.php`](./happyfiles-media-sync.php)

### How to Install

Choose **only one** of the files above based on your needs, then install it using one of these methods:

1. **Method 1 (Recommended):** Download the file and drop it directly into your `/wp-content/mu-plugins/` directory. WordPress will automatically execute it network-wide as a Must-Use plugin.
2. **Method 2:** Place the file in your primary child theme folder and include it in your `functions.php` file using:
   `require_once get_stylesheet_directory() . '/name-of-the-file.php';`

**⚠️ Important Notice:**
When either of these scripts is active, uploading an image while working on a sub-site canvas (e.g., `/zh/`) will save the file directly inside the Main Site's database and `uploads` directory.

## 📄 License

---


This project is open-source and available under the [MIT License](LICENSE).
