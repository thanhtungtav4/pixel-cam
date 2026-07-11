<?php

declare(strict_types=1);

namespace Theme\Child\Hooks;

defined('ABSPATH') || exit;

/**
 * Per-menu-item icon field (SVG/PNG) using WordPress-native menu fields.
 *
 *   - Adds an "Icon" media picker to each item in Appearance → Menus.
 *   - Stores the chosen attachment ID in menu-item meta `_pxc_menu_icon`.
 *   - Renders the icon inline before the link label on the front end.
 *
 * No ACF needed. SVG upload is enabled by MediaHook (sanitized on upload).
 */
final class MenuHook
{
    private const META_KEY = '_pxc_menu_icon';

    public static function register(): void
    {
        $self = new self();

        add_action('wp_nav_menu_item_custom_fields', [$self, 'render_field'], 10, 2);
        add_action('wp_update_nav_menu_item', [$self, 'save_field'], 10, 2);
        add_filter('walker_nav_menu_start_el', [$self, 'prepend_icon'], 10, 4);
        add_action('admin_enqueue_scripts', [$self, 'enqueue_media_picker']);
    }

    /**
     * Icon picker row in the menu-item editor.
     */
    public function render_field($item_id, $item): void
    {
        $icon_id  = (int) get_post_meta($item_id, self::META_KEY, true);
        $icon_url = $icon_id ? wp_get_attachment_url($icon_id) : '';
        ?>
        <p class="field-pxc-icon description description-wide">
            <label><?php esc_html_e('Icon (SVG/PNG)', 'underscores'); ?></label><br>
            <span class="pxc-icon-preview" style="display:inline-block;vertical-align:middle;margin-right:8px">
                <?php if ($icon_url) : ?>
                    <img src="<?php echo esc_url($icon_url); ?>" alt="" style="width:24px;height:24px;object-fit:contain">
                <?php endif; ?>
            </span>
            <input type="hidden" class="pxc-icon-id" name="pxc_menu_icon[<?php echo (int) $item_id; ?>]" value="<?php echo esc_attr((string) $icon_id); ?>">
            <button type="button" class="button pxc-icon-pick"><?php esc_html_e('Chọn icon', 'underscores'); ?></button>
            <button type="button" class="button-link pxc-icon-remove" style="<?php echo $icon_id ? '' : 'display:none'; ?>;color:#b32d2e;margin-left:6px"><?php esc_html_e('Xóa', 'underscores'); ?></button>
        </p>
        <?php
    }

    /**
     * Save the icon ID posted alongside the menu item.
     */
    public function save_field($menu_id, $menu_item_db_id): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification -- core verifies the menu nonce.
        $posted = $_POST['pxc_menu_icon'][$menu_item_db_id] ?? null;
        if ($posted === null || $posted === '') {
            delete_post_meta($menu_item_db_id, self::META_KEY);
            return;
        }
        update_post_meta($menu_item_db_id, self::META_KEY, (int) $posted);
    }

    /**
     * Prepend the icon <img> to the item output on the front end.
     *
     * @param string $item_output
     * @return string
     */
    public function prepend_icon($item_output, $item, $depth, $args): string
    {
        $img = self::icon_html((int) $item->ID);
        if ($img === '') {
            return $item_output;
        }
        // Insert the icon just inside the opening <a ...> tag.
        return preg_replace('/(<a\b[^>]*>)/', '$1' . $img, $item_output, 1) ?? $item_output;
    }

    /**
     * Icon <img> for a menu item, or '' if none. Shared so custom walkers
     * (e.g. MegaWalker) render the same icon without duplicating the logic.
     */
    public static function icon_html(int $item_id): string
    {
        $icon_id = (int) get_post_meta($item_id, self::META_KEY, true);
        if (! $icon_id) {
            return '';
        }
        $url = wp_get_attachment_url($icon_id);
        if (! $url) {
            return '';
        }
        return '<img class="menu-icon" src="' . esc_url($url) . '" alt="" width="18" height="18" loading="lazy" />';
    }

    /**
     * Load wp.media + a tiny picker script on the menu editor screen only.
     */
    public function enqueue_media_picker(string $hook): void
    {
        if ($hook !== 'nav-menus.php') {
            return;
        }
        wp_enqueue_media();
        wp_add_inline_script('jquery-core', <<<'JS'
jQuery(function($){
  $(document).on('click','.pxc-icon-pick',function(e){
    e.preventDefault();
    var $row=$(this).closest('.field-pxc-icon');
    var frame=wp.media({title:'Chọn icon',library:{type:['image/svg+xml','image']},multiple:false,button:{text:'Dùng icon'}});
    frame.on('select',function(){
      var a=frame.state().get('selection').first().toJSON();
      $row.find('.pxc-icon-id').val(a.id);
      $row.find('.pxc-icon-preview').html('<img src="'+a.url+'" alt="" style="width:24px;height:24px;object-fit:contain">');
      $row.find('.pxc-icon-remove').show();
    });
    frame.open();
  });
  $(document).on('click','.pxc-icon-remove',function(e){
    e.preventDefault();
    var $row=$(this).closest('.field-pxc-icon');
    $row.find('.pxc-icon-id').val('');
    $row.find('.pxc-icon-preview').empty();
    $(this).hide();
  });
});
JS);
    }
}
