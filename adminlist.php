<div class="wrap">

	<h2>Mailing List Signups - <?php echo esc_html( $this->date_option )?></h2>

	<ul class="subsubsub">
	  <?php foreach ( SYAMailingList_Admin::$DATE_OPTIONS as $label ) { ?>
	  <li><a href="<?php 
				echo add_query_arg( array( 
					  SYAMailingList_Admin::FORM_COLLECTION.'[date_option]' => $label, 
					  SYAMailingList_Admin::FORM_COLLECTION.'[country]' => $this->country,
					  'page' => 'mailing_list'
					), admin_url() ) ?>"<?php if ( $label == $this->date_option ) echo ' class="current" '?>><?php echo esc_html( $label )?></a>
	  |
	  </li>
	  <?php } ?>
	  <li>
	  (<?php echo count( $data ) ?> sign-ups from <?php echo $this->country ?: 'all countries'?> 
	   between <?php echo $this->from_date->format('l jS F Y')?> and <?php echo $this->to_date->format('l jS F Y')?>)
	
	  </li>
	</ul>

	<form method="get" action="<?php echo admin_url()?>">
			  <input type="hidden" name="<?php echo SYAMailingList_Admin::FORM_COLLECTION?>[date_option]" value="<?php echo esc_attr( $this->date_option )?>" />
			  <input type="hidden" name="page" value="mailing_list" />
	  <div class="tablenav top">
		<div class="alignleft actions bulkactions">
		  <input type="submit" class="button" value="Export CSV" name="<?php echo SYAMailingList_Admin::FORM_COLLECTION?>[export]" />
		</div>
		<div class="alignleft actions">
		  <select name="<?php echo SYAMailingList_Admin::FORM_COLLECTION?>[country]">
			<option value="">All Countries</option>
			<?php foreach ( $this->syaml->get_countries() as $id => $value ) { ?>
			<option value="<?php echo esc_attr($id)?>"<?php if ( $id == $this->country ) echo ' selected="selected" '?>><?php echo esc_html( $value )?></option>
			<?php } ?>
		  </select>
		  <input type="submit" class="button" value="Filter"></input>
		</div>
	  </div>
	</form>
	
	<table class="wp-list-table widefat fixed">
	  
	  <thead>
		  <tr>
			  <th style="" class="manage-column column-name" id="name" scope="col">Name</th>
			  <th style="" class="manage-column column-email" id="email" scope="col">Email</th>
			  <th style="" class="manage-column column-country" id="country" scope="col">Country</th>
			  <th style="" class="manage-column column-added" id="date_added" scope="col">Date Added</th>
		  </tr>
	  </thead>

	<?php if ( count( $data) ) { ?>
		  <?php $rowtgl = 0; foreach ( $data as $row ) { $rowtgl = 1 - $rowtgl; ?>
	  
	  <tr<?php if ( $rowtgl ) echo ' class="alternate"'?>>
		<td><?php echo esc_html($row['name'])?></td>
		<td><?php echo esc_html($row['email'])?></td>
		<td><?php echo esc_html($this->country_name_from_id($row['country']))?></td>
		<td><?php echo esc_html($row['date_added'])?></td>
	  </tr>
		<?php } ?>
	  
	  <?php } ?>
	  
	  <tfoot>
		  <tr>
			  <th style="" class="manage-column column-name" id="name" scope="col">Name</th>
			  <th style="" class="manage-column column-email" id="email" scope="col">Email</th>
			  <th style="" class="manage-column column-country" id="country" scope="col">Country</th>
			  <th style="" class="manage-column column-added" id="date_added" scope="col">Date Added</th>
		  </tr>
	  </tfoot>	  
	</table>

	
</div>