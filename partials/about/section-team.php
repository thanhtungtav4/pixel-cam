<?php
defined('ABSPATH') || exit;

$eyebrow = $args['eyebrow'] ?? '';
$heading = $args['heading'] ?? '';
$desc    = $args['desc'] ?? '';
$members = $args['members'] ?? [];
if (empty($members)) {
    return;
}
?>
<section class="ab-section"><div class="wrap">
    <div class="head">
        <?php if ($eyebrow) : ?><div class="eyebrow"><?php echo esc_html($eyebrow); ?></div><?php endif; ?>
        <?php if ($heading) : ?><h2><?php echo esc_html($heading); ?></h2><?php endif; ?>
        <?php if ($desc) : ?><p><?php echo esc_html($desc); ?></p><?php endif; ?>
    </div>
    <div class="ab-team">
        <?php foreach ($members as $m) :
            $photo = $m['photo'] ?? 0;
            $name  = $m['name'] ?? '';
            ?>
            <div class="mem">
                <?php if ($photo) : ?>
                    <?php echo wp_get_attachment_image($photo, 'thumbnail', false, ['class' => 'av']); ?>
                <?php elseif ($name) : ?>
                    <div class="av"><?php echo esc_html(mb_substr($name, 0, 2)); ?></div>
                <?php endif; ?>
                <?php if ($name) : ?><div class="name"><?php echo esc_html($name); ?></div><?php endif; ?>
                <?php if (! empty($m['role'])) : ?><div class="role"><?php echo esc_html($m['role']); ?></div><?php endif; ?>
                <?php if (! empty($m['bio'])) : ?><div class="bio"><?php echo esc_html($m['bio']); ?></div><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div></section>
