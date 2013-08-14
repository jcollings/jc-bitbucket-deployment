<div class="wrap">
	<div id="icon-tools" class="icon32"><br></div>
	<h2>Bitbucket Deployment Settings</h2>

	<p>Settings to allow you to automatically update your bitbucket repository.</p>

	<?php if($errors = $this->config->errors): ?>
	<p>Please fix the following errors to complete setup.</p>
	<?php foreach($errors as $e): ?>

	
	<div id="message" class="error below-h2">
		<p><?php echo $e; ?></p>
	</div>
	<?php endforeach; ?>
	<?php else: ?>
		<?php // include the correct view ?>

		<form action="options.php" method="post" enctype="multipart/form-data">  
            <?php  
            settings_fields($this->config->option_group);
            do_settings_sections('tab_settings');  
            // do_settings_sections($tabs[$tab])
            ?>  
            <p class="submit">  
                <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />  
            </p>  
        </form> 

	<?php endif; ?>

</div>