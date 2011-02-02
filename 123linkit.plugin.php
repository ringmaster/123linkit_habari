<?php

class LinkIt extends Plugin
{
	const BASE_URL = 'http://www.123linkit.com/';
	
	private $api_calls = array(
		'api_login' => array('api/login', array('email', 'password')),
		'api_getstats' => array('api/getStats', array('#private_key')),
		'api_download' => array('api/downloadPost', array('guid', '#private_key', '#blog_url')),
		'api_upload' => array('api/createPost', array('guid', 'title', 'content', '#private_key', '#blog_url')),
	);
	
	public function action_init()
	{
		$options = array_merge(
			array('privatekey' => '', 'publickey' => '', 'email' => ''),
			Options::get_group('linkit')
		);
		foreach($options as $name => $value) {
			$this->$name = $value;
		}
	}

	function action_plugin_activation( $file )
	{
		Modules::add( '123LinkIt' );
		CronTab::add_hourly_cron( 'linkit_fetch', 'linkit_fetch', _t('Fetches the statistical data from the 123LinkIt site.', 'linkit'));
	}
	
	function action_plugin_deactivation( $file )
	{
		Modules::remove_by_name( '123LinkIt' );
	}
	
	function filter_dash_modules( $modules )
	{
		if($this->privatekey != '') {
			$modules[] = '123LinkIt';
		}
		return $modules;
	}

	public function filter_dash_module_123linkit( $module, $module_id, $theme )
	{
		$module['title'] = _t('123LinkIt', 'linkit');
		$theme->status_data = Options::get('linkit__stats', array(_t('Status', 'linkit') => _t('Queued', 'linkit')));
		$module['content'] = $theme->fetch( 'dash_status' );
		return $module;
	}
	
	public function filter_linkit_fetch($success)
	{
		
		$stats = $this->api_getstats();
		EventLog::log(_t('Running dashboard fetch for 123LinkIt', 'linkit'), 'info', null, null, $stats);
		Options::set('linkit__stats', $stats);
		
		return $success;
	}

	/**
	* Provide buttons to display the configuration for the plugin
	**/
	public function filter_plugin_config( $actions )
	{
		// Show the login form or the configuration UI:
		if($this->privatekey == '') {
			$actions['signup'] = _t('Sign Up');
			$actions['login'] = _t('Log In');
		}
		else {
			$actions['configure'] = _t('Configure');
			$actions['logout'] = _t('Log Out');
		}
		return $actions;
	}
	
	public function action_plugin_ui_signup()
	{
		$ui = new FormUI('linkit_signup');
		$ui->append('text', 'email', 'linkit__email', _t('Your email address:'));
		$ui->append('password', 'password', 'linkit__password', _t('Your password:'));
		// This should use an API call:
		$ui->append('select', 'category', 'linkit__category', _t('Your blog category:'), array(
			1 => _t('Accessories', 'linkit'),
			2 => _t('Art/Photo/Music', 'linkit'),
			3 => _t('Automotive', 'linkit'),
			4 => _t('Beauty', 'linkit'),
			5 => _t('Books/Media', 'linkit'),
			6 => _t('Business', 'linkit'),
			7 => _t('Buying and Selling', 'linkit'),
			8 => _t('Careers', 'linkit'),
			9 => _t('Clothing/Apparel', 'linkit'),
			10 => _t('Computer &amp; Electronics', 'linkit'),
			11 => _t('Department Stores/Malls', 'linkit'),
			12 => _t('Education', 'linkit'),
			13 => _t('Entertainment', 'linkit'),
			14 => _t('Family', 'linkit'),
			15 => _t('Financial Services', 'linkit'),
			16 => _t('Food &amp; Drinks', 'linkit'),
			17 => _t('Games &amp; Toys', 'linkit'),
			18 => _t('Gifts &amp; Flowers', 'linkit'),
			19 => _t('Health and Wellness', 'linkit'),
			20 => _t('Home &amp; Garden', 'linkit'),
			21 => _t('Legal', 'linkit'),
			22 => _t('Marketing', 'linkit'),
			23 => _t('Non-Profit', 'linkit'),
			24 => _t('Online Services', 'linkit'),
			25 => _t('Recreation &amp; Leisure', 'linkit'),
			26 => _t('Seasonal', 'linkit'),
			27 => _t('Sports &amp; Fitness', 'linkit'),
			28 => _t('Telecommunications', 'linkit'),
			29 => _t('Travel', 'linkit'),
		));
		$ui->append('submit', 'save', _t('Save'));
		$ui->out();
	}
	
	public function action_plugin_ui_login()
	{
		$ui = new FormUI( 'linkit_login' );
		$ui->append('text', 'email', 'linkit__email', _t('Your email address:'));
		$ui->append('password', 'password', 'linkit__password', _t('Your password:'));
		$ui->append('submit', 'login', _t('Log In'));
		$ui->on_success( array( $this, 'do_login' ) );
		$ui->out();
	}
	
	public function do_login($ui)
	{
		$login = $this->api_login($ui->email->value, $ui->password->value);
		if($login->error != '0') {
			Session::error($login->error);
		}
		else {
			Options::set('linkit__email', $ui->email->value);
			Options::set('linkit__privatekey', $login->private_key);
			Options::set('linkit__publickey', $login->public_key);
			Session::notice(_t('Successful login.'));
		}
		return _t('You have logged in successfully', 'linkit'); // do not display the form again
	}

	public function action_plugin_ui_logout()
	{
		$ui = new FormUI( 'linkit_logout' );
		$ui->append('static', 'notice', _t('You have logged out.'));
		$ui->out();
		Session::notice(_t('Successful logout.'));
		Options::set('linkit__privatekey', null);
	}
	
	function filter_post_actions($actions, $post)
	{
		$actions['link it'] = array('url' => URL::get('linkit', array('post_id' => $post->id)), 'title' => _t('Apply affiliate links', 'linkit'), 'label' => _t('Link It', 'linkit'), 'permission' => 'edit' );
		return $actions;
	}
	
	function filter_special_searches($searches)
	{
		$searches['Link It'] = 'linkit:none';
		return $searches;
	}
	
	function filter_posts_search_to_get ( $arguments, $flag, $value, $match, $search_string)
	{
		if($flag == 'linkit') {
			$arguments['criteria'] = '123lingit';
		}
		return $arguments;
	}
	
	function filter_posts_manage_actions($actions)
	{
		$actions = array_merge(array(
				'link it' => array('action' => 'itemManage.update(\'linkit\');return false;', 'title' => _t('Apply affiliate links', 'linkit'), 'label' => _t('Link It', 'linkit') ),
			),
			$actions
		);
		return $actions;
	}
	
	function filter_rewrite_rules($rules)
	{
		$rules[] = new RewriteRule( array(
			'name' => 'linkit',
			'parse_regex' => '%admin/linkit/(?P<post_id>.+)/?$%i',
			'build_str' => 'admin/linkit/{$post_id}',
			'handler' => 'PluginHandler',
			'action' => 'linkit',
			'priority' => 7,
			'is_active' => 1,
			'description' => 'Sends a post to 123LinkIt for processing',
		));
		return $rules;
	}
	
	function action_plugin_act_linkit($handler)
	{
		$post = Post::get(array('id'=>$handler->handler_vars['post_id']));
		if($post->get_access()->edit) {
			//$result = $this->api_upload($post->guid, $post->title, $post->content);
			//$result = $this->api_download($post->guid);
			//$post->info->linkit_content = $result->content;
			//$post->info->commit();
//Utils::debug($post->info->linkit_content);
			Session::notice(_t("Sync'ed the post \"%s\" to 123LinkIt", array($post->title), 'linkit'));
			Utils::redirect(URL::get('admin', array('page'=>'posts')));
		}
	}
	
	function action_template_header()
	{
		Stack::add('template_header_javascript', Site::get_url('scripts') . '/jquery.js', 'jquery');
	}
	
	function filter_post_content_out($content, $post)
	{
		if($post->info->linkit_content) {
			return $post->info->linkit_content;
		}
		return $content;
	}
	
	function __call($name, $inputs) 
	{
		if(isset($this->api_calls[$name])) {
			list($fn, $params) = $this->api_calls[$name];
			foreach($params as $param) {
				switch($param) {
					case '#private_key':
						$outputs['private_key'] = $this->privatekey;
						break;
					case '#blog_url':
						$outputs['blog_url'] = Site::get_url('habari');
						break;
					default:
						$outputs[$param] = array_shift($inputs);
						break;
				}
			}
//Utils::debug(self::BASE_URL . $fn, $outputs);
			$rr = new RemoteRequest(self::BASE_URL . $fn, 'POST', 180);
			$rr->set_postdata($outputs);
			$rr->execute();
			$headers = $rr->get_response_headers();
			$response = json_decode($rr->get_response_body());
			$response->_status = $headers['Status'];
			return $response;
		}
	}
	
}
?>
