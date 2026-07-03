<?php
defined('ABSPATH') || exit;

$eyebrow = $args['eyebrow'] ?? '';
$heading = $args['heading'] ?? '';
$desc    = $args['desc'] ?? '';
$rooms   = $args['rooms'] ?? [];
if (empty($rooms)) {
    return;
}
?>
<section class="ab-section"><div class="wrap">
    <div class="head">
        <?php if ($eyebrow) : ?><div class="eyebrow"><?php echo esc_html($eyebrow); ?></div><?php endif; ?>
        <?php if ($heading) : ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?>
        <?php if ($desc) : ?><p><?php echo esc_html($desc); ?></p><?php endif; ?>
    </div>
    <div class="ab-showroom">
        <?php foreach ($rooms as $room) :
            $image = $room['image'] ?? 0;
            $rows  = $room['rows'] ?? [];
            $map   = underscores_child_acf_link($room['map_link'] ?? []);
            $book  = underscores_child_acf_link($room['book_link'] ?? []);
            ?>
            <div class="room">
                <?php if ($image) : ?>
                    <?php echo wp_get_attachment_image($image, 'large'); ?>
                <?php endif; ?>
                <div class="body">
                    <?php if (! empty($room['city'])) : ?><div class="city"><?php echo esc_html($room['city']); ?></div><?php endif; ?>
                    <?php if (! empty($room['name'])) : ?><h4><?php echo esc_html($room['name']); ?></h4><?php endif; ?>
                    <?php if (! empty($rows)) : ?>
                        <div class="info">
                            <?php foreach ($rows as $row) : ?>
                                <div class="row">
                                    <span class="k"><?php echo esc_html($row['k'] ?? ''); ?></span>
                                    <span><?php echo esc_html($row['v'] ?? ''); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($map || $book) : ?>
                        <div class="actions">
                            <?php if ($map) : ?><a class="btn-sm primary" href="<?php echo esc_url($map['url']); ?>"<?php echo $map['target'] ? ' target="' . esc_attr($map['target']) . '"' : ''; ?>><?php echo esc_html($map['title']); ?></a><?php endif; ?>
                            <?php if ($book) : ?><a class="btn-sm" href="<?php echo esc_url($book['url']); ?>"><?php echo esc_html($book['title']); ?></a><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div></section>
