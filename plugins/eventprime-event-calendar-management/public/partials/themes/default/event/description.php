<div class="ep-box-row ep-my-2">
  <div class="ep-box-col-12 ep-text-secondary" id="ep_single_event_description">
    <?php
      $raw = get_post_field( 'post_content', $args->post->ID );

      // If you implemented Option A:
      remove_filter( 'the_content', array( $this, 'ep_load_single_template_dynamic' ), 1000000000 );
      $content = apply_filters( 'the_content', $raw );
      add_filter( 'the_content', array( $this, 'ep_load_single_template_dynamic' ), 1000000000 );

      // If you implemented Option B instead, just do:
      // $content = apply_filters( 'the_content', $raw );

      echo $content; // no wp_kses_post/wpautop here
    ?>
  </div>
</div>