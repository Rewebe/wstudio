<?php
/* Template: Enkelt galleri (kundegalleri) */

get_header();
?>

<main id="primary" class="site-main">
  <div class="wrap" style="padding: 2rem 1rem;">

    <div class="kundegalleri-content">
      <?php
      if (have_posts()) :
        while (have_posts()) : the_post();
          echo do_shortcode('[kundegalleri id="' . get_the_ID() . '"]');
        endwhile;
      else :
        echo '<p>Galleriet blev ikke fundet.</p>';
      endif;
      ?>
    </div>

  </div>
</main>

<?php get_footer(); ?>
