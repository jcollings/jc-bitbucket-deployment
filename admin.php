<?php 
/**
 * Bitbucket Wordpress Administration Class
 */
class JC_Admin_Wordpress_Deploy{

	/**
	 * Plugin config
	 * @var stdClass
	 */
	private $config = null;

	public function __construct(&$config){
		$this->config = $config;

		add_action( 'admin_init', array($this, 'register_settings' ));
		add_action( 'admin_menu', array($this, 'settings_menu' ));

		add_filter( 'plugin_action_links_'.plugin_basename( $this->config->file ) , array( $this, 'settings_link' ) );
	}

    /**
     * Add Settings link to plugin archive
     * 
     * @param  array $args 
     * @return array      
     */
	public function settings_link($args){
        array_unshift($args, '<a href="tools.php?page=wordpress-deploy">Settings</a>');
        return $args;
    }

    /**
     * Create plugin options page under tools
     * 
     * @return void
     */
    public function settings_menu(){

        add_submenu_page('tools.php','Deploy Scripts', 'Repo Deployment', 'manage_options', 'wordpress-deploy', array($this, 'theme_options_page'));
    }

    /**
     * Register plugin settings with wordpress settings api
     * 
     * @return void 
     */
    public function register_settings(){

        // Settings
        register_setting($this->config->option_group, $this->config->prefix . '-bitbucket_settings', array($this, 'save_settings'));

        add_settings_section('settings', 'Bitbucket Repository', array($this, 'section_settings'), 'tab_settings');

        add_settings_field('repo', 'Bitbucket Repo Name', array($this, 'field_callback'), 'tab_settings', 'settings', array(
            'type' => 'text',
            'field_id' => 'repo',
            'section_id' => 'settings',
            'setting_id' => $this->config->prefix . '-bitbucket_settings'
        ));

        add_settings_field('user', 'Bitbucket Username', array($this, 'field_callback'), 'tab_settings', 'settings', array(
            'type' => 'text',
            'field_id' => 'user',
            'section_id' => 'settings',
            'setting_id' => $this->config->prefix . '-bitbucket_settings'
        ));

        add_settings_field('pass', 'Bitbucket Password', array($this, 'field_callback'), 'tab_settings', 'settings', array(
            'type' => 'password',
            'field_id' => 'pass',
            'section_id' => 'settings',
            'setting_id' => $this->config->prefix . '-bitbucket_settings'
        ));

        add_settings_field('type', 'Repo Type', array($this, 'field_callback'), 'tab_settings', 'settings', array(
            'type' => 'select',
            'choices' => array('plugin' => 'Plugin', 'theme' => 'Theme'),
            'field_id' => 'type',
            'section_id' => 'settings',
            'setting_id' => $this->config->prefix . '-bitbucket_settings'
        ));

        add_settings_field('folder', 'Folder', array($this, 'field_callback'), 'tab_settings', 'settings', array(
            'type' => 'text',
            'field_id' => 'folder',
            'section_id' => 'settings',
            'setting_id' => $this->config->prefix . '-bitbucket_settings'
        ));

    }

    /**
     * Settings Section Text
     * 
     * @return void 
     */
    public function section_settings()
    {
        ?>
        <p>Setup your bitbucket repository account details.</p>
        <?php
    }

    /**
     * Create Settings Fields
     * 
     * @param  array $args 
     * @return void
     */
    public function field_callback($args){
        $multiple = false;
        extract($args);
        $options = get_option($setting_id);
        switch($args['type'])
        {
            case 'text':
            {
                $value = isset($options[$field_id]) ? $options[$field_id] : '';
                ?>
                <input class='text' type='text' id='<?php echo $setting_id; ?>' name='<?php echo $setting_id; ?>[<?php echo $field_id; ?>]' value='<?php echo $value; ?>' />
                <?php
                break;
            }
            case 'password':
            {
                $value = '';
                ?>
                <input class='text' type='text' id='<?php echo $setting_id; ?>' name='<?php echo $setting_id; ?>[<?php echo $field_id; ?>]' value='<?php echo $value; ?>' />
                <?php
                break;
            }
            case 'select':
            {
                    ?>
                    <select id="<?php echo $setting_id; ?>" name="<?php echo $setting_id; ?>[<?php echo $field_id; ?>][]" <?php if($multiple === true): ?>multiple<?php endif; ?>>
                    <?php
                    foreach($choices as $id => $name):?>
                        <?php if(isset($options[$field_id]) && is_array($options[$field_id]) && in_array($id,$options[$field_id])): ?>
                        <option value="<?php echo $id; ?>" selected="selected"><?php echo $name; ?></option>
                        <?php else: ?>
                        <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </select>
                    <?php
                break;
            }
        }
    }

    /**
     * Save Settings
     * 
     * @param  array $args 
     * @return array
     */
    public function save_settings($args){
        
        if(isset($args['pass'])){
            
            if(empty($args['pass'])){
                // fix to not clear the password
                $settings = get_option( $this->config->prefix . '-bitbucket_settings');
                $args['pass'] = $settings['pass'];
                // unset($args['pass']);
            }else{
                $args['pass'] = $this->config->encrypt->encrypt($args['pass']);
            }
        }

        return $args;
    }

    /**
     * Include settings view
     * 
     * @return void 
     */
    public function theme_options_page(){
    	require trailingslashit( $this->config->plugin_dir ) . 'views/settings.php';
    }

}

?>