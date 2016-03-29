<?php

/*
Plugin Name: Top Shared Posts on Facebook Widget
Description: Top shared posts on Facebook fetches total share counts for your blog posts on Facebook using Facebook Graph API and sorts the most shared ones in an ascending order together with their total share counts.
Author: Samuel Elh
Version: 2.0.1
Author URI: https://samelh.com
*/

class TSPF
{

	protected static $instance = null;

	public static function instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	public function __construct() {
		$this->init();
		$this->admin_init();
	}

	public function init() {

		register_activation_hook( __FILE__, function() {
			$this->activate();
		});

		register_deactivation_hook( __FILE__, function() {
			$this->deactivate();
		});

		add_action( 'tspf_update', array( $this, 'cron' ) );

		add_action('widgets_init', function() {
			register_widget('tspf_widget');
		});

		$this->_functions();
		$this->functions();
		
	}

	public function _functions() {
		function tspf_settings() {
			return TSPF::instance()->settings();
		}
		function tspf_get_count( $post_id ) {
			return (int) TSPF::instance()->get_count( $post_id );
		}

		function tspf_sorted( $limit = 10 ) {
			return TSPF::instance()->sorted( $limit );
		}

		function _tspf_widget( $args = array() ) {

			if( ! current_user_can('manage_options') )
				return;

			$args = (object) $args;

			if( empty( $args ) )
				$args = new stdClass();

			if( ! ( $args->style > '' ) )
				$args->style = 1;
			if( ! ( $args->max > '' ) )
				$args->max = 5;
			if( ! ( $args->format > '' ) )
				$args->format = '[count] shares';
			if( ! ( $args->onThumb > '' ) )
				$args->onThumb = true;
			if( ! ( $args->cats > '' ) )
				$args->cats = false;
			if( ! ( $args->button > '' ) )
				$args->button = true;

			$data = tspf_sorted( $args->max );

			if( ! empty( $data ) ) :;

				foreach( $data as $post_id => $_count ) {
					
					$count = 0;

					foreach( get_the_category( $post_id ) as $cat ) {
						if( ( is_array( $args->cats ) && in_array( $cat->term_id, $args->cats ) ) || ! is_array( $args->cats ) ) {
							$count += 1;
						}
					}

					if( $count <= 0 )
						unset( $data[$post_id] );

				}

			endif;

			?>

				<ul class="tspf <?php echo 1 == $args->style ? 'thumb-list' : 'nulled-list', $args->onThumb ? ' on-thumb' : ''; ?>">

					<?php if( count( $data ) > 0 ) : ?>

						<?php foreach ( $data as $post_id => $count ) : ?>

							<?php if( 1 == $args->style ) { ?>
							
								<li id="post-<?php echo $post_id; ?>">

									<?php if( $args->onThumb ) : ?>
										
										<style type="text/css" media="all">#post-<?php echo $post_id; ?> .tspf-left a:before{content:"<?php echo tspf_count($count); ?>";top: <?php echo abs( (int) apply_filters('tspf_attachement_height', '70') * 0.36 ); ?>px}</style>

									<?php endif; ?>

									<div class="tspf-left">

										<a href="<?php echo get_the_permalink($post_id); ?>" title="<?php echo get_the_title($post_id); ?>" rel="bookmark"><?php tspf_thumbnail( $post_id, 100 ); ?></a>

									</div>

									<div class="tspf-right">

										<span><a href="<?php echo get_the_permalink($post_id); ?>" title="<?php echo get_the_title($post_id); ?>" rel="bookmark"><?php echo get_the_title($post_id); ?></a></span>
										<span><?php echo str_replace( '[count]', tspf_count($count), $args->format ); ?></span>

										<?php if( $args->button ) : ?>

											<div class="fb-int">

												<?php if( $args->button_share ) { ?>

													<div class="fb-like" data-href="<?php echo get_the_permalink($post_id); ?>" data-layout="button_count" data-action="like" data-show-faces="false" data-share="true"></div>

												<?php } else { ?>

													<div class="fb-like" data-href="<?php echo get_the_permalink($post_id); ?>" data-layout="button_count" data-action="like" data-show-faces="false" data-share="false"></div>

												<?php } ?>

											</div>

										<?php endif; ?>
									
									</div>

								</li>					

							<?php } else { ?>

								<li id="post-<?php echo $post_id; ?>">
									
									<span><a href="<?php echo get_the_permalink($post_id); ?>" title="<?php echo get_the_title($post_id); ?>" rel="bookmark"><?php echo get_the_title($post_id); ?></a></span>
									<?php echo apply_filters('tspf_nulled_list_separator', '&mdash;'); ?>
									<span><?php echo str_replace( '[count]', tspf_count($count), $args->format ); ?></span>

									<?php if( $args->button ) : ?>

										<div class="fb-int">

											<?php if( $args->button_share ) { ?>

												<div class="fb-like" data-href="<?php echo get_the_permalink($post_id); ?>" data-layout="button_count" data-action="like" data-show-faces="false" data-share="true"></div>

											<?php } else { ?>

												<div class="fb-like" data-href="<?php echo get_the_permalink($post_id); ?>" data-layout="button_count" data-action="like" data-show-faces="false" data-share="false"></div>

											<?php } ?>

										</div>

									<?php endif; ?>

								</li>

							<?php } ?>

						<?php endforeach; ?>

					<?php else : ?>

						<?php echo apply_filters('tspf_no_posts_inner', '<p>There are no posts to show.</p>'); ?>
					
					<?php endif; ?>

				</ul>

			<?php

		}

		function tspf_thumbnail( $post_id ) {
			
			if ( has_post_thumbnail( $post_id ) ) {
				$thumb = wp_get_attachment_url( get_post_thumbnail_id( $post_id ) );
			} else {
				$thumb = tspf_settings()->thumb;
			}

			$height = apply_filters('tspf_attachement_height', '70');
			$width = apply_filters('tspf_attachement_width', '100');

			$css = 'background-image: url(\'' . $thumb . '\');width: ' . $width . 'px;height: ' . $height . 'px;';

			echo '<img src="' . tspf_dir('assets/pixel.gif') . '" alt="' . get_the_title($post_id) . '" style="' . $css . '" height="' . $height . '" width="' . $width . '" />';

		}

		function tspf_count($num) {
			if(empty ($num) ) {
				return apply_filters('tspf_count_nan', 'n/a');
			} else {
				if( $num < 1000 ) return $num;
				$x = round($num);
				$x_number_format = number_format($x);
				$x_array = explode(',', $x_number_format);
				$x_parts = apply_filters( 'tspf_shot_count_parts', array('k', 'm', 'b', 't') );
				$x_count_parts = count($x_array) - 1;
				$x_display = $x;
				$x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
				$x_display .= $x_parts[$x_count_parts - 1];
				return $x_display;
			}
		}

		function tspf_dir( $sub = '' ) {
			return plugins_url('/') . str_replace( 'top-shared-posts-on-facebook.php', '',  plugin_basename( __FILE__ ) ) . $sub;
		}

	}

	public function settings() {

		$_settings = new stdClass();
		$_settings->interval = apply_filters( 'tspf_interval', $interval = 24 );
		$_settings->offset = apply_filters( 'tspf_offset', $offset = 200 );
		$_settings->sleep = 10;
		$_settings->thumb = tspf_dir('assets/no-thumbnail.jpg');
		$_settings = apply_filters('tspf_settings', $_settings);
		return $_settings;

	}


	public function activate() {

		if( ! wp_next_scheduled( 'tspf_update' ) ) {  
			wp_schedule_event( time(), 'tspf_custom_hours', 'tspf_update' );  
		}

	}

	public function deactivate() {

		wp_unschedule_event( wp_next_scheduled( 'tspf_update' ), 'tspf_update' );

	}

	public function cron() {

		$posts_list = $this->posts_list();
		if( ! empty( $posts_list ) ) :;

			$return = false;

			foreach( $this->posts_list() as $list ) :;

				error_reporting(0);

				$array = explode( ',', $list );
				$permalinks = array();

				foreach( $array as $post_id ) :;

					$permalinks[] = get_the_permalink( $post_id ); 

				endforeach;

				error_reporting(E_ALL);

				$return[] = implode(',', $permalinks);

			endforeach;

			if( ! empty( $return ) ) :;

				$data = array();

				$object = false;

				foreach( $return as $URLs ) :;

					$content = file_get_contents('http://graph.facebook.com/?ids=' . $URLs);
					$content = json_decode( $content, false );

					if( ! empty( $content ) ) :;

						foreach( $content as $URL ) :;

							$count = empty( $URL->shares ) ? 0 : $URL->shares;

							if( ! empty( $URL->id ) ) :;

								$post_id = url_to_postid( $URL->id );
								$object .= ' "' . $post_id .  '": "' . $count . '",';

							endif;

						endforeach;

					endif;

					sleep( tspf_settings()->sleep );

				endforeach;

				$object = '{' . substr( $object, 0, -1 ) . ' }';
				update_option( '_tspf', esc_attr( $object ) );

			endif;

		endif;

	}

	public function functions() {

		add_filter( 'cron_schedules', function( $schedules ) {
			$schedules['tspf_custom_hours'] = array(
			    'interval' 	=> abs( tspf_settings()->interval * 3600 ),
			    'display' 	=> __( 'Once every ' . tspf_settings()->interval . ' hour(s)' )
		    );
		    return $schedules;
		});

		add_action('wp_head', function() {
			?>
				<style type="text/css">ul.tspf li{margin-bottom:1.22em}ul.tspf img{background-size:cover;background-repeat:no-repeat;background-position:center center;border-radius:2px;-webkit-border-radius:2px}ul.tspf div{display:inline-block;vertical-align:top;position:relative}ul.tspf .tspf-left{max-width:45%}ul.tspf .tspf-right{max-width:50%;margin-left:6px}ul.tspf span{display:inline;position:relative;overflow:hidden}ul.tspf.thumb-list span{display:block}ul.tspf .tspf-left a:before{display:none;position:absolute;border:1px solid #CBCBCB;padding:0 5px;border-radius:3px;-webkit-border-radius:3px;-mox-border-radius:3px;-ms-border-radius:3px;background:#fff;left:-10px;z-index:777}ul.tspf.on-thumb .tspf-left a:before{display:block!important}ul.tspf div.fb-int{display:block;margin-top:3px}ul.tspf div.fb-int span{display:inherit;position:inherit;overflow:inherit}</style>
			<?php
		});

		add_action('wp_footer', function() {
			?>
				<div id="fb-root"></div>
				<script>(function(d, s, id) {
				  var js, fjs = d.getElementsByTagName(s)[0];
				  if (d.getElementById(id)) return;
				  js = d.createElement(s); js.id = id;
				  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.5&appId=712320088891230";
				  fjs.parentNode.insertBefore(js, fjs);
				}(document, 'script', 'facebook-jssdk'));</script>
			<?php
		});

		add_shortcode('tspf-count', function($atts) {

			$a = shortcode_atts( array(
				'post_id' => 0
		    ), $atts );

			$post_id = esc_attr( "{$a['post_id']}" );

			if( ! get_post( $post_id ) )
				return 'invalid post';

			return tspf_get_count($post_id);

		});

		add_filter('manage_edit-post_columns', function($columns) {
		    $columns['tspf_count'] = 'Facebook shares';
		    return $columns;
		});
		add_action( 'manage_post_posts_custom_column', function( $column, $post_id ) {
			switch( $column ) {
				case 'tspf_count' :
					echo (int) tspf_get_count($post_id);
					break;
			}
		}, 10, 2 );

		add_action( 'add_meta_boxes', function() {
		    add_meta_box( 'meta-box-id', 'Facebook share count', 'tspf_metabox_note', 'post', 'side' );
		});
		
		function tspf_metabox_note( $post ) {
			$count = (int) tspf_get_count($post->ID);
			if( $count > 0 ) {
				echo 'This post was shared ' . $count . ' time';
				echo $count !== 1 ? 's' : '';
			} else {
				echo 'NaN';
			}
		}

		add_filter( "plugin_action_links_".plugin_basename(__FILE__), function($links) {
		    array_push( $links, '<a href="options-general.php?page=tspf">' . __( 'Settings' ) . '</a>' );
		  	return $links;
		});

		add_filter('tspf_settings', function($settings) {

			$option = get_option('_tspf_settings');

			if( $option > '' ) {
				$option = html_entity_decode( stripslashes( $option ) );
				$option = json_decode($option, false);

				if( empty( $option ) )
					return $settings;

				if( is_numeric( strpos( $option->thumb, 'http' ) ) ) {
					$settings->thumb = $option->thumb;
				}

				if( (int) $option->int > 0 ) {
					$settings->interval = (int)  $option->int;
				}

				if( (int) $option->offset > 0 ) {
					$settings->offset = (int)  $option->offset;
				}

				if( (int) $option->sleep > 0 ) {
					$settings->sleep = (int)  $option->sleep;
				}

			}

			return $settings;

		});

	}

	public function posts_list( $all = false ) {

		$args = array(
			'post_status'		=> 'publish',
			'posts_per_page'	=> -1,
			'post_type' 		=> 'post'
		);

		$list = array();

		foreach( get_posts( $args ) as $post ) :;

			$list[] = $post->ID;

		endforeach;

		$array = $list;

		if( $all )
			return $array;

		$offset = tspf_settings()->offset;

		foreach( range(0, 9) as $i ) :;

			$results = array_slice( $array, $i * $offset, $offset );

			if( ! empty( $results ) ) :;

				$finalArray[] = implode(',', $results);

			endif;

		endforeach;

		$finalArray = ! empty( $finalArray ) ? $finalArray : array();
		
		return $finalArray;

	}

	public function get_count( $post_id ) {

		$meta = html_entity_decode( get_option('_tspf') );

		if( $meta !== '' ) :;
			
			$data = json_decode( $meta, true );
			return empty( $data[$post_id] ) ? false : $data[$post_id];

		endif;

	}


	public function sorted( $limit = 10 ) {

		$array = $this->posts_list( true );

		if( !empty( $array ) ) :;

			$sorted = array();

			foreach( $array as $post_id ) :;

				$sorted[ $post_id ] = (int) $this->get_count( $post_id );

			endforeach;

		endif;

		arsort( $sorted );

		$final = array();

		foreach( $sorted as $key => $value ) :;
			
			if( (int) $value > 0 ) $final[ array_search($key,array_keys($sorted)) ][$key] = $value; // excluding 0 values

		endforeach;

		if( ! ( (int) $limit > 0 ) )
			$limit = 10;

		//if( 'localhost' == $_SERVER['SERVER_NAME'] ) // debugging purposes
			//$final = array( 26 => 3333, 16 => 211, 11 =>15, 1 => 10 );

		$current = 1;
		$_final = array();

		foreach( $final as $post_id => $count ) {

			$_final[$post_id] = $count;

			if( $current >= $limit ) {
				break;
			}

			$current += 1;
		}
		$current = 0;
		foreach( $_final as $array ) {
			foreach( $array as $p => $count ) {
				$_final[$p] = $count;
				unset($_final[ $current ]);
				$current +=1;
			}
		}

		return $_final;

	}

	public function admin_init() {

		add_action( 'admin_notices', function() {

			if( false === get_option( '_tspf' )  ) {
				echo '<div id="updated" class="notice-info notice"><p><strong>[Top Shared Posts on Facebook notice]</strong> Almost there, please note that you will need to manually process the initial data insert. You can do that by clicking <a href="javascript:;" onclick="tspf_confirm()">here</a>.</p></div>';
			}

		});

		add_action('admin_footer', function() {

			$total_posts = count( TSPF::instance()->posts_list( true ) );
			$batches = (int) ( $total_posts / tspf_settings()->offset ) + 1;
			$est_time = (int) abs(tspf_settings()->sleep * $batches);

			?>
				<script type="text/javascript">
					function tspf_confirm() {
						var _conf = confirm( 'I understand that this manual process can take up to <?php echo $est_time; ?> seconds as we are targeting <?php echo $total_posts; ?> blog entries.\n\nProceed?' );
						if( _conf ) {
							window.location.href = 'index.php?do_manual_tspf=1';
						}
					}
				</script>
			<?php

			if( ! isset( $_GET['page'] ) || ( isset( $_GET['page'] ) && 'tspf' !== $_GET['page'] ) )
				return;

			?>
				<script type="text/javascript">
					jQuery(document).ready(function($){
					    $(document).on("click", "span.tspf-upload", function(e){
						    var custom_uploader;
					        e.preventDefault();
					        if (custom_uploader) {
					            custom_uploader.open();
					            return;
					        }
					        custom_uploader = wp.media.frames.file_frame = wp.media({
					            title: 'Choose Image',
					            button: {
					                text: 'Choose Image'
					            },
					            multiple: false
					        });
					        custom_uploader.on('select', function() {
					            attachment = custom_uploader.state().get('selection').first().toJSON();
					            jQuery("form.tspf input[type='url']").val(attachment.url);
					        });
					        custom_uploader.open();
					    });
					});

					document.getElementById('tspf_advanced').onclick = function() {
						var tar = document.getElementById('adv-settings');
						if( tar.style > '' ) {
							if( tar.style.display == 'none' ) {
								tar.style.display = 'block';
								this.innerText = 'Hide advanced settings';
							} else {
								tar.style.display = 'none';
								this.innerText = 'Advanced settings';
							}
						} else {
							tar.style.display = 'block';
							this.innerText = 'Hide advanced settings';
						}
					}

					if( window.location.href.indexOf('&done=') > 0 ) {
						window.history.pushState(null, null, window.location.href.substring(0, window.location.href.indexOf( '&done' )) );
					}

				</script>
				<style type="text/css">
					@media screen and (min-width: 700px) {
						.tspf_left,
						.tspf_right {
							display: inline-block;
						}
						.tspf_left {width: 65%;}
						.tspf_right {
							width: 25%;
							vertical-align: top;
							border-left: 1px solid #ddd;
							padding: 0 1.2em;
							margin-top: -5px;
						}
					}
				</style>
			<?php

		});

		add_action('admin_init', function() {

			if( is_admin() && isset( $_GET['do_manual_tspf'] ) ) {

				TSPF::instance()->cron();
				wp_redirect( admin_url( 'options-general.php?page=tspf&done=1' ) );
				exit;

			}

		});

		add_action( 'admin_menu', function() {
			add_options_page( 'TSPF', 'TSPF', 'manage_options', 'tspf', array( &$this, 'admin_screen' ) );
		});

		add_action('admin_enqueue_scripts', function() {
			if( isset( $_GET['page'] ) && $_GET['page'] == 'tspf' ) {
				wp_enqueue_script('jquery');
				wp_enqueue_media();
			}
		});

	}

	public function admin_screen() {

		$this->admin_update();

		if( isset( $_GET['done'] ) )
			echo '<div id="updated" class="updated notice is-dismissible"><p>Data updated successfully.</p></div>';

		?>
			<style type="text/css">form.tspf td { width: 10%}</style>
			
			<div class="wrap">

				<div class="tspf_left">

					<h2>Top Shared Posts on Facebook &rsaquo; Settings</h2>
	
					<form method="post" class="tspf">

						<!-- A small note: don't really rely on the min and max attributes of number inputs here to make your settings decisions. An example is, the offset, which stands for how many posts to include per batch while making requests to Facebook graph API; I set the default offset to 200 (posts) as the graph API handled that amount with no errors, and if you raise the offset you may get denied, or the data won't be handled as we are working with GET method not POST (POST can take huge amounts of data unlike GET), or so. -->
					
						<table>
								
							<tr>
								<td><h4>Default thumbnail</h4></td>
								<td>
									<input type="url" name="tspf_thumb" value="<?php echo tspf_settings()->thumb; ?>" size="60" /><span class="button tspf-upload">upload</span><br/>
									<em>Shows when the post has no thumbnail associated</em>
								</td>
							</tr>

							<tr>
								<td><h4>Updating interval</h4></td>
								<td>
									<label>Update data (counts) every <input type="number" min="1" max="999" name="tspf_interval" value="<?php echo tspf_settings()->interval; ?>" /> hour(s)</label>
								</td>
							</tr>

							<tr>
								<td><a href="javascript:;" id="tspf_advanced">Advanced settings</a></td>
							</tr>

							<tr id="adv-settings" style="display: none;">
								<td>
									<table>
										<tr>
											<td><h4>Posts per batch</h4></td>
											<td>
												<input type="number" min="20" max="9999" name="tspf_offset" value="<?php echo tspf_settings()->offset; ?>" /><br/>
												<em>Some blogs have a lot of posts, and making API requests with huge amount of data at once won't be successful, therefore splitting them into batches works fine.</em>
											</td>
										</tr>
										<tr>
											<td><h4>Delay between requests (sleep)</h4></td>
											<td>
												<input type="number" min="1" max="999" name="tspf_sleep" value="<?php echo tspf_settings()->sleep; ?>" /><br/>
												<em>Number of seconds to make a delay between API requests (when in batches). Helps preventing 403 server errors from the API server.</em>
											</td>
										</tr>
										<tr>
											<td><sub><a href="options-general.php?page=tspf&amp;do_manual_tspf=1" onclick="return confirm('Are you sure?');">Update data now?</a></sub></td>
										</tr>
									</table>
								</td>
							</tr>

							<tr>
								<td style="display: none;"><?php wp_nonce_field('tspf_nonce', 'tspf_nonce'); ?></td>
								<td><?php submit_button(); ?></td>
							</tr>

						</table>

						<h3>Shortcode:</h3>
						<p>You can use <code>[tspf-count post_id="X"]</code> to get the Facebook share counts for a specific post, and you are required to enter the post ID in the <code>post_id</code> shortcode attribute: <code>[tspf-count post_id="1"]</code>.</p>

					</form>

				</div>

				<div class="tspf_right">

					<h3>Check out more of our premium plugins</h3>
					<li><a target="_blank" href="http://go.samelh.com/get/wpchats/">WpChats</a> bringing instant live chat &amp; private messaging feature to your site..</li>
					<?php if( function_exists('bbpress')) : ?>
						<li><a target="_blank" href="http://go.samelh.com/get/bbpress-messages/">bbPress Messages</a> Let your forum users exchange messages privately and communicate..</li>
						<li><a target="_blank" href="http://go.samelh.com/get/bbpress-ultimate/">bbPress Ultimate</a> adds more features to your forums and bbPress/BuddyPress profiles..</li>
					<?php endif; ?>
					<li><a target="_blank" href="http://go.samelh.com/get/youtube-information/">YouTube Information</a>: easily embed YouTube video/channel info and stats, video cards, channel cards, widgets, shortcodes..</li>
					<p>View more of our <a target="_blank" href="https://profiles.wordpress.org/elhardoum#content-plugins">free</a> and <a target="_blank" href="http://codecanyon.net/user/samiel/portfolio?ref=samiel">premium</a> plugins.</p>
					<p><hr/></p>

					<h3>Subscribe, Join our mailing list</h3>
					<p><i>Join our mailing list today for more WordPress tips and tricks and awesome free and premium plugins</i><p>
					<form action="//samelh.us12.list-manage.com/subscribe/post?u=677d27f6f70087b832c7d6b67&amp;id=7b65601974" method="post" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate="">
						<label><strong>Email:</strong><br/>
							<input type="email" value="<?php echo wp_get_current_user()->email; ?>" name="EMAIL" class="required email" id="mce-EMAIL" />
						</label>
						<br/>
						<label><strong>Your name:</strong><br/>
							<input type="text" value="<?php echo wp_get_current_user()->user_nicename; ?>" name="FNAME" class="" id="mce-FNAME" />
						</label>
						<br/>
					    <input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button" />
					</form>
					<p><hr/></p>

					<h3>Are you looking for help?</h3>
					<p>Don't worry, we got you covered:</p>
					<li><a href="http://wordpress.org/support/plugin/top-shared-posts-on-facebook">Go to plugin support forum on WordPress</a></li>
					<li><a href="http://support.samelh.com/">Try our Support forum</a></li>
					<li><a href="http://blog.samelh.com/">Browse our blog for tutorials</a></li>
					<p><hr/></p>

					<p>
						<li><a href="https://wordpress.org/support/view/plugin-reviews/top-shared-posts-on-facebook?rate=5#postform">Give us &#9733;&#9733;&#9733;&#9733;&#9733; rating</a></li>
						<li><a href="http://twitter.com/samuel_elh">Follow @Samuel_Elh on Twitter</a></li>
					</p>

					<p>Thank you! :)</p>

				</div>

			</div>

		<?php
	}

	public function admin_update() {

		if( ! isset( $_POST['submit'] ) || ! isset( $_POST['tspf_nonce'] ) || !wp_verify_nonce( $_POST['tspf_nonce'], 'tspf_nonce' ) )
			return;

		$thumb = isset( $_POST['tspf_thumb'] ) ? (string) $_POST['tspf_thumb'] : '';
		$int = isset( $_POST['tspf_interval'] ) ? (int) $_POST['tspf_interval'] : '';
		$offset = isset( $_POST['tspf_offset'] ) ? (int) $_POST['tspf_offset'] : '';
		$sleep = isset( $_POST['tspf_sleep'] ) ? (int) $_POST['tspf_sleep'] : '';
		$settings = '{"thumb": "' . $thumb . '", "int": "' . $int . '", "offset": "' . $offset . '", "sleep": "' . $sleep . '"}';

		if( $int > '' && (int) $int !== tspf_settings()->interval ) {
			echo '<div id="update" class="notice-info notice is-dismissible"><p>Please deactivate and activate this plugin in order to make the new updating interval (Cron) functional.</p></div>';
		}

		update_option('_tspf_settings', esc_attr( $settings ));

		echo '<div id="updated" class="updated notice is-dismissible"><p>Settings updated.</p></div>';

	}

}

TSPF::instance();

class tspf_widget extends WP_Widget {
	function __construct() {
		parent::__construct(
			'tspf_widget', 
			__('TSPF widget', 'wordpress'), 
			array( 'description' => __( 'Top shared posts on Facebook: the widget' ), ) 
		);
	}
	public function widget( $args, $instance ) {
		
		if( ! is_user_logged_in() )
			return;

		$_args = array();
		$_args['title'] = apply_filters( 'widget_title', $instance['title'] );
		$_args['style'] = ! empty( $instance['style'] ) ? $instance['style'] : 1;
		$_args['max'] = ! empty( $instance['max'] ) ? $instance['max'] : 5;
		$_args['format'] = ! empty( $instance['format'] ) ? $instance['format'] : '[count] shares';
		$_args['onThumb'] = ! ( ! empty( $instance[ 'onThumb' ] ) && 'off' == $instance[ 'onThumb' ] );
		$_args['button'] = ! ( ! empty( $instance[ 'button' ] ) && 'off' == $instance[ 'button' ] );
		$_args['button_share'] = ! ( ! empty( $instance[ 'button_share' ] ) && 'off' == $instance[ 'button_share' ] );
		
		$cats = array();

		foreach( get_categories() as $cat ) {
			if( ! ( ! empty( $instance[ 'cat_' . $cat->term_id ] ) && 'off' == $instance[ 'cat_' . $cat->term_id ] ) ) {
				$cats[] = $cat->term_id;
			}
		}

		$_args['cats'] = $cats;

		$_args = (object) $_args;

		echo $args['before_widget'];
		echo ! empty( $_args->title ) ? $args['before_title'] . $_args->title . $args['after_title'] : '';

		_tspf_widget($_args);

		echo $args['after_widget'];
		
	}

	public function form( $instance ) {
		$title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
		$style = isset( $instance[ 'style' ] ) ? (int) $instance[ 'style' ] : 1;
		$max = isset( $instance[ 'max' ] ) ? (int) $instance[ 'max' ] : 5;
		$format = isset( $instance[ 'format' ] ) ? (string) $instance[ 'format' ] : '[count] shares';
		$onThumb = ! ( isset( $instance[ 'onThumb' ] ) && 'off' == $instance[ 'onThumb' ] );
		$cats = array();
		foreach( get_categories() as $cat ) {
			if( ! ( isset( $instance[ 'cat_' . $cat->term_id ] ) && 'off' == $instance[ 'cat_' . $cat->term_id ] ) ) {
				$cats[] = $cat->term_id;
			}
		}
		$button = ! ( isset( $instance[ 'button' ] ) && 'off' == $instance[ 'button' ] );
		$button_share = ! ( isset( $instance[ 'button_share' ] ) && 'off' == $instance[ 'button_share' ] );

		if( is_array( $cats ) && empty( $cats ) && count( get_categories() ) > 0 ) {
			echo '<p style="border:1px solid #C5C5C5;padding:4px 6px;background:#FFE5E5">No posts will be shown because you filtered out all the categories.</p>';
		}

		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>" style="font-weight:bold;"><?php _e( 'Widget Title:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>

			<p>
				<h4>Widget style:</h4>
				<label>
					<input type="radio" name="<?php echo $this->get_field_name( 'style' ); ?>" value="1" <?php echo 1 == $style ? 'checked="checked"' : ''; ?>/>Thumbnailed list
				</label>
				<label>
					<input type="radio" name="<?php echo $this->get_field_name( 'style' ); ?>" value="2" <?php echo 2 == $style ? 'checked="checked"' : ''; ?>/>Simple list
				</label>
			</p>

			<p>
				<h4>Max items:</h4>
				<input type="number" max="999" min="1" name="<?php echo $this->get_field_name( 'max' ); ?>" value="<?php echo $max; ?>" />
			</p>

			<p>
				<h4>Format:</h4>
				<input type="text" class="widefat" name="<?php echo $this->get_field_name( 'format' ); ?>" value="<?php echo $format; ?>" />
				<em><code>[count]</code> will be replaced with the dynamic count.</em>
			</p>

			<p>
				<h4>Categories:</h4>
				
				<?php if( count( get_categories() ) > 0 ) : ?>
					
					<span>Show only posts from selected categories:</span><br/><br/>
					
					<?php foreach( get_categories() as $cat ) : ?>
					
						<label style="margin-right: 2px;">
							<input type="checkbox" name="<?php echo $this->get_field_name( 'cat_' . $cat->term_id ); ?>" <?php echo in_array( $cat->term_id, $cats ) ? 'checked="checked"' : '' ?>/>
							<?php echo $cat->name; ?>
						</label>
					
					<?php endforeach; ?>

				<?php else : ?>
				
					<span>You don't have any.</span>
				
				<?php endif; ?>
			
			</p>

			<p>
				<h4>Additional:</h4>
				<label>
					<input type="checkbox" name="<?php echo $this->get_field_name( 'onThumb' ); ?>" <?php echo $onThumb ? 'checked="checked"' : ''; ?>/>Show counts on thumbnails (tooltips)
				</label>
				<br/>
				<label>
					<input type="checkbox" name="<?php echo $this->get_field_name( 'button' ); ?>" <?php echo $button ? 'checked="checked"' : ''; ?>/>Include a like button
				</label>
				<?php if( 'on' == $button ) : ?>
					<br/>
					<label style="margin-left: 1.44em;">
						<input type="checkbox" name="<?php echo $this->get_field_name( 'button_share' ); ?>" <?php echo $button_share ? 'checked="checked"' : ''; ?>/>Include a share button
				</label>
				<?php endif; ?>
			</p>



		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['style'] = ! empty( $new_instance['style'] ) ? (int) $new_instance['style'] : '1';
		$instance['max'] = ! empty( $new_instance['max'] ) ? (int) $new_instance['max'] : 5;
		$instance['format'] = ! empty( $new_instance['format'] ) ? strip_tags( $new_instance['format'] ) : '[count] shares';
		$instance['onThumb'] = isset( $new_instance['onThumb'] ) ? (string) $new_instance['onThumb'] : 'off';
		$instance['button'] = isset( $new_instance['button'] ) ? (string) $new_instance['button'] : 'off';
		$instance['button_share'] = isset( $new_instance['button_share'] ) ? (string) $new_instance['button_share'] : 'off';

		foreach( get_categories() as $cat ) {
			$instance['cat_' . $cat->term_id] = isset( $new_instance['cat_' . $cat->term_id] ) ? (string) $new_instance['cat_' . $cat->term_id] : 'off';
		}

		return $instance;
	}

}

// I don't always comment my code, but when I do, it's to tell myself to fix it later - unknown