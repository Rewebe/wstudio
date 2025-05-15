<?php
$post_id  = $args['post_id'];
$accepted = $args['accepted'];
?>

<div id="wstudio-approval-dialog" class="gallery-top-controls" data-approved="<?php echo $accepted ? '1' : '0'; ?>">

    <!-- Wrapper der enten viser knap eller badge -->
    <div class="wstudio-approval-left">
        <?php if (!$accepted): ?>
            <button id="wstudio-approve-btn" class="wstudio-button wstudio-button-green">
                <?php echo esc_html(get_option('wstudio_approval_button_label', 'Accepter billederne')); ?>
            </button>
        <?php endif; ?>

        <div id="wstudio-approved-badge" style="<?php echo $accepted ? '' : 'display:none;'; ?>">
            <span class="wstudio-badge"><?php echo esc_html(get_option('wstudio_badge_text', 'Godkendt')); ?></span>
            <span class="wstudio-time"><?php echo esc_html(date_i18n('d-m-Y H:i', strtotime($accepted))); ?></span>
        </div>
    </div>

    <!-- Dropdown -->
    <select class="wstudio-download-select zip-download-select <?php echo $accepted ? '' : 'disabled'; ?>"
            onchange="if(this.value) window.location.href=this.value;"
            <?php if (!$accepted) echo 'disabled aria-disabled="true"'; ?>>
        <option value="">Download zip...</option>
        <option value="<?php echo esc_url(admin_url("admin-ajax.php?action=wstudio_download_zip&gallery_id={$post_id}&type=web")); ?>">Download Web</option>
        <option value="<?php echo esc_url(admin_url("admin-ajax.php?action=wstudio_download_zip&gallery_id={$post_id}&type=original")); ?>">Download Original</option>
        <option value="<?php echo esc_url(admin_url("admin-ajax.php?action=wstudio_download_zip&gallery_id={$post_id}&type=both")); ?>">Download Begge</option>
    </select>
</div>