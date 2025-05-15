<?php
if (!defined('ABSPATH')) exit;
$accepted = get_post_meta(get_the_ID(), 'wg_accepted_time', true);
?>

<div id="wg-approval-dialog" class="gallery-top-controls" data-approved="<?php echo $accepted ? '1' : '0'; ?>">
    <?php if (!$accepted): ?>
        <button id="wg-approve-btn" class="wg-button wg-button-green">
            <?php echo esc_html(get_option('wg_approval_button_label', 'Accepter billederne')); ?>
        </button>
    <?php endif; ?>

    <select class="wg-download-select zip-download-select <?php echo $accepted ? '' : 'disabled'; ?>"
            onchange="if(this.value) window.location.href=this.value;"
            <?php if (!$accepted) echo 'disabled aria-disabled="true"'; ?>>
        <option value="">Download zip...</option>
        <option value="<?php echo esc_url(admin_url("admin-ajax.php?action=wg_download_zip&gallery_id=" . get_the_ID() . "&type=web")); ?>">Download Web</option>
        <option value="<?php echo esc_url(admin_url("admin-ajax.php?action=wg_download_zip&gallery_id=" . get_the_ID() . "&type=original")); ?>">Download Original</option>
        <option value="<?php echo esc_url(admin_url("admin-ajax.php?action=wg_download_zip&gallery_id=" . get_the_ID() . "&type=both")); ?>">Download Begge</option>
    </select>

    <div id="wg-approved-badge" <?php echo $accepted ? '' : 'style="display:none;"'; ?>>
        <span class="wg-badge"><?php echo esc_html(get_option('wg_badge_text', 'Godkendt')); ?></span>
        <span class="wg-time"><?php echo esc_html(date_i18n('d-m-Y H:i', strtotime($accepted))); ?></span>
    </div>
</div>