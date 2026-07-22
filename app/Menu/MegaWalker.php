<?php

declare(strict_types=1);

namespace Theme\Child\Menu;

defined('ABSPATH') || exit;

/**
 * Mega-menu walker for the header nav.
 *
 * Turns a 3-level WordPress menu into the Pixel Cam mega dropdown:
 *   depth 0  → top-level tab (adds aria-haspopup when it has children)
 *   depth 1  → a column: its label becomes the <h4> heading
 *   depth 2  → links listed under that column
 *
 * A 2-level menu still works (depth 1 items just render as links in a single
 * column). Markup matches the .mega / .mega-grid / .mega-col CSS.
 */
final class MegaWalker extends \Walker_Nav_Menu
{
    /** Whether the current top-level item has a submenu (mega). */
    private bool $top_has_children = false;

    public function start_lvl(&$output, $depth = 0, $args = null): void
    {
        if ($depth === 0) {
            // Opening the mega panel under a top item.
            $output .= '<div class="mega"><div class="wrap mega-grid">';
        } else {
            // depth 1 → the list of links inside a column.
            $output .= '<ul class="mega-links">';
        }
    }

    public function end_lvl(&$output, $depth = 0, $args = null): void
    {
        if ($depth === 0) {
            $output .= '</div></div>'; // .mega-grid, .mega
        } else {
            $output .= '</ul>';
        }
    }

    /**
     * @param object $item
     * @param int    $depth
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0): void
    {
        $has_children = in_array('menu-item-has-children', (array) $item->classes, true);
        $title        = apply_filters('the_title', $item->title, $item->ID);
        $url          = $item->url ?: '#';
        $atts         = $item->target ? ' target="' . esc_attr($item->target) . '"' : '';
        $atts        .= $item->target === '_blank' ? ' rel="noopener noreferrer"' : '';

        if ($depth === 0) {
            $this->top_has_children = $has_children;
            $classes = 'menu-item' . ($has_children ? ' has-mega' : '');
            $output .= '<li class="' . esc_attr($classes) . '">';
            $aria    = $has_children ? ' aria-haspopup="true" aria-expanded="false"' : '';
            $caret   = $has_children ? '<svg class="caret" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>' : '';
            $icon    = \Theme\Child\Hooks\MenuHook::icon_html((int) $item->ID);
            $output .= '<a href="' . esc_url($url) . '"' . $atts . $aria . '>' . $icon . esc_html($title) . $caret . '</a>';
            return;
        }

        if ($depth === 1) {
            // Column: heading is a link to the section.
            $output .= '<div class="mega-col">';
            $output .= '<h4><a href="' . esc_url($url) . '"' . $atts . '>' . esc_html($title) . '</a></h4>';
            // The <ul class="mega-links"> for its children (if any) follows via start_lvl.
            return;
        }

        // depth 2 → a link in the column list.
        $output .= '<li><a href="' . esc_url($url) . '"' . $atts . '>' . esc_html($title) . '</a></li>';
    }

    /**
     * @param object $item
     * @param int    $depth
     */
    public function end_el(&$output, $item, $depth = 0, $args = null): void
    {
        if ($depth === 0) {
            $output .= '</li>';
        } elseif ($depth === 1) {
            $output .= '</div>'; // .mega-col
        } else {
            $output .= '</li>';
        }
    }
}
