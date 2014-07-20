<?php

class SYAMailingList_Widget extends WP_Widget {


	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		parent::__construct('syamailinglist_widget', 'Mailing List Signup');
	}

	public function field($id)
	{
	  return SYAMailingList::FORM_COLLECTION . '[' . $id . ']';
	}
	
	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		if ( ! empty( $instance['title'] ) ) {
			$title = apply_filters( 'widget_title', $instance['title'] );
		}
		if ( ! empty( $args['before_widget'] ) ) {
			echo $args['before_widget'];
		}
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		$mlist = SYAMailingList::instance();
		$values = $mlist->get_form_values();
		if ( $mlist->has_been_submitted() && ! $mlist->has_errors() ) {
?>
<strong><?php _e('Thank you for signing up', SYAMailingList::PLUGIN_NAME )?></strong>
<?php
		} else {
			if ( $mlist->has_errors() ) {
?>
<strong style="color: red;"><?php _e( 'Please correct the following errors:', SYAMailingList::PLUGIN_NAME )?></strong>
<br /><?php echo join( '<br />', $mlist->get_errors() )?>
<?php
			}
?>
<form method="post">
<label>
<?php _e('Name:', SYAMailingList::PLUGIN_NAME)?>
<input type="text" name="<?php echo $this->field(SYAMailingList::NAME_FIELD)?>" value="<?php echo esc_html( $values[ SYAMailingList::NAME_FIELD] )?>" />
</label>
<label>
<?php _e('Email:', SYAMailingList::PLUGIN_NAME)?>
<input type="text" name="<?php echo $this->field(SYAMailingList::EMAIL_FIELD)?>" value="<?php echo esc_html( $values[ SYAMailingList::EMAIL_FIELD] )?>" />
</label>
<label>
<?php _e('Country:', SYAMailingList::PLUGIN_NAME)?>
<select name="<?php echo $this->field(SYAMailingList::COUNTRY_FIELD)?>">
<?php foreach ( SYAMailingList::instance()->get_countries() as $id => $name ) { ?>
<option value="<?php echo esc_html( $id )?>"<?php if ( $id == $values[ SYAMailingList::COUNTRY_FIELD ] ) { echo ' selected="selected"';}?>><?php echo esc_html( $name )?></option>
<?php } ?>
</label>
<input type="submit" name="<?php echo $this->field(SYAMailingList::SUBMIT_FIELD)?>" value="<?php _e( 'Sign Up', SYAMailingList::PLUGIN_NAME )?>" />
</form>
<?php
		}
		if ( ! empty( $args['after_widget'] ) ) {
			echo $args['after_widget'];
		}
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;	
	}
}
