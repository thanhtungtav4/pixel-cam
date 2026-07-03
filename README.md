# Underscores Theme Child

Child theme duoc to chuc theo PSR-4 (hook = class) + ACF Local JSON, theo rule trong `.ai` va `wp-content/themes/underscores/.ai`.

## Cau truc

```text
underscores-child/
|-- acf-json/                       # ACF Local JSON (field groups, commit vao git)
|   |-- group_page_about.json
|   |-- group_page_contact.json
|   `-- group_theme_settings.json
|-- assets/
|   |-- css/child-theme.css (+ pages/)
|   `-- scripts/child-theme.js (+ pages/)
|-- bin/underscores-child           # CLI scaffold (standalone)
|-- inc/
|   |-- src/                        # Class PSR-4, namespace Theme\Child\
|   |   |-- Acf/LocalJson.php        # save/load JSON paths + options page
|   |   `-- Hooks/{Performance,Theme,About,Contact}PageHook.php
|   |-- functions/                  # helper global (underscores_child_*)
|   |   |-- common-functions.php
|   |   |-- performance-functions.php
|   |   `-- template-functions.php
|   |-- classes/                    # CLI scaffolder (global, dev tooling)
|   `-- bootstrap.php                    # bootstrap
|-- page-template/                  # template-{slug}.php
|-- partials/
|   |-- sections/                   # flexible-content layouts
|   `-- templates/                  # {slug}-page.php
|-- functions.php
`-- style.css
```

## Flow load

1. `functions.php` define constants + bootstrap (`after_setup_theme`).
2. `includes/bootstrap.php`: require helper thu tuc + goi `::register()` cho cac class (Acf\LocalJson, cac Hook).
3. Class tu autoload qua composer PSR-4 cua **parent** (`Theme\Child\` -> child `app/`).
4. KHONG con `configs/loadFile.php`. ACF dung Local JSON (`acf-json/`), KHONG con `inc/acf-fields/`.

## Cach code nhanh

- Them helper moi: them ham vao `includes/functions/{domain}-functions.php` (prefix `underscores_child_`), require trong `includes/bootstrap.php`.
- Them hook moi: tao class `app/Hooks/{Name}Hook.php` (namespace `Theme\Child\Hooks`, static `register()`), goi `::register()` trong `includes/bootstrap.php`.
- Them ACF field group: tao/sua trong wp-admin -> Custom Fields, ACF tu ghi `acf-json/group_*.json` (commit + **Sync**). Hoac tao file JSON thu cong roi Sync.
- Them flexible section: them layout trong file `acf-json/group_*.json`, render file cung ten trong `partials/sections/`.
- Override template: tao file cung ten trong child, vi du `page-template/template-about.php`.
- Toi uu Web Vitals: dat trong `app/Hooks/PerformanceHook.php` + `includes/functions/performance-functions.php`.
- Khong defer/async `jquery`, `wp-hooks`, `wp-i18n`, hoac runtime Contact Form 7 neu chua verify ky.

## Main Class Pattern

- `<main>` mo trong `header.php`, dong trong `footer.php`. Class mac dinh: `main`.
- Set them class truoc `get_header()`: `underscores_child_set_main_class('page-contact');`
  (nhieu class: truyen chuoi cach nhau khoang trang hoac mang).
- Them class theo dieu kien: dung filter `main_class` trong page hook, khong hard-code trong partial.

Vi du page hook (class):
```php
namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

final class ContactPageHook
{
    public static function register(): void
    {
        $self = new self();
        add_filter('main_class', [$self, 'main_class']);
    }

    public function main_class(array $classes): array
    {
        if (! is_page_template('page-template/template-contact.php')) {
            return $classes;
        }
        $classes[] = 'underscores-contact-page';
        return $classes;
    }
}
```

Luu y: khong mo them `<main>` trong `partials/templates/*`; partial chi render noi dung ben trong `<main>`.

## WP-CLI scaffold

> ⚠️ Scaffolder dang theo model cu mot phan (ghi `configs/loadFile.php` - da bo). Can cap nhat stub
> sang sinh class PSR-4 + chen `::register()` vao `includes/bootstrap.php` truoc khi dung lai. Phan `--acf` da
> chuyen sang ghi `acf-json/group_page_{slug}.json`.

- Standalone: `php wp-content/themes/underscores-child/bin/underscores-child make:page about --title="Giới thiệu" --acf --hook --assets`
- Qua WP-CLI: `wp underscores make page about --acf --hook --assets`
- `--dry-run` xem truoc, `--force` ghi de.

## AI Guide

- Entry file cho AI / agent: `AGENTS.md`
- Rule chi tiet:
  - `.ai/rules/child-theme.md`

## Ghi chu

- Helper functions: prefix `underscores_child_`.
- Class: namespace `Theme\Child\`, PascalCase, khong prefix.
- Ten file trong `partials` / `inc/functions`: `kebab-case`.
- Ten page template: `template-{name}.php`.
- ACF: Local JSON trong `acf-json/`, khong dung Extended ACF / PHP field group.
