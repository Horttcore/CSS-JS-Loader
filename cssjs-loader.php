<?php
/*
Plugin Name: CSS/JS Loader
Plugin URI: http://horttcore.de/
Description: Load CSS and/or Javascript Files where they are needed
Version: 1.0
Author: Ralf Hortt
Author URI: http://horttcore.de/
*/


/**
 *
 * Security, checks if WordPress is running
 *
 **/
if ( !function_exists('add_action') ) :
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
endif;



/**
 *
 * CSS / JSS Loader Class
 *
 */
class CSS_JS_Loader
{



	/**
	 * Load the plugin
	 *
	 * @uses add_action()
	 * @author Ralf Hortt
	 */
	function __construct()
	{
		add_action( 'admin_init', array(&$this, 'admin_init') );
		add_action( 'get_header', array(&$this, 'load_scripts') );
		add_action( 'save_post', array(&$this, 'save_post'));
		
		add_post_type_support( 'post', 'css-js-loader' );
		add_post_type_support( 'page', 'css-js-loader' );
	}



	/**
	 * Add the metabox
	 *
	 * @uses add_meta_box()
	 * @author Ralf Hortt
	 **/
	function admin_init()
	{
		global $_wp_post_type_features;

		add_meta_box( 'cssjs-loader', __( 'CSS/JS Loader', RH_CJL_TEXTDOMAIN ), array(&$this, 'metabox'), 'page', 'side' );
		add_meta_box( 'cssjs-loader', __( 'CSS/JS Loader', RH_CJL_TEXTDOMAIN ), array(&$this, 'metabox'), 'post', 'side' );
	}



	/**
	 * Load the files by extension
	 *
	 * @param array $extensions Fileextensions to load
	 * @param str $directory Directory to scan; used for recursion
	 * @return array $files Files that were found in the current theme
	 * @uses $this->get_files(), get_template_directory()
	 * @author Ralf Hortt
	 **/
	function get_files( $extensions, $directory = '' ) {
		
		$files = array(); # Init files array
		$directory = ( $directory ) ? $directory : get_template_directory(); # Use template directory or $directory

		if ( $handle = opendir( $directory ) ) :

			while ( false !== ( $file = readdir($handle) ) ) :

				if ( '.' != $file && '..' != $file ) :

					if ( is_dir($directory. "/" . $file) ) :

						$files = array_merge($files, $this->get_files( $extensions, $directory. '/' . $file)); # Recursive

					else :

						if ( preg_match('&^.+\.(js|css)$&', $file)) :

							$file = $directory . "/" . $file;
							$files[] = str_replace(get_template_directory().'/', '', $file);

						endif;

					endif;

				endif;

			endwhile;

			closedir($handle);

		endif;

		asort($files);

		return $files;
	}



	/**
	 * Files Option Output
	 *
	 * @uses $this->get_files()
	 * @author Ralf Hortt
	 **/
	function files_options( $loaded_files = '' )
	{
		$files = $this->get_files( array( 'css', 'js' ) );
		
		if ( $files ) :

			foreach ( $files as $file ) :

				$selected = ( is_array($loaded_files) && in_array($file, $loaded_files) ) ? 'selected="selected"' : '';
				$options .= '<option value="' . $file . '" ' . $selected . '>' . $file . '</option>';

			endforeach;

			echo $options;

		endif;
	}



	/**
	 * Checks if $str contains .js
	 *
	 * @return bool
	 * @author Ralf Hortt
	 **/
	function is_script( $str )
	{
		if ( strstr( $str, '.js' ) ) :
			return true;
		else :
			return false;
		endif;
	}



	/**
	 * Checks if $str contains .css
	 *
	 * @return bool
	 * @author Ralf Hortt
	 **/
	function is_stylesheet( $str )
	{
		if ( strstr( $str, '.css' ) ) :
			return true;
		else :
			return false;
		endif;
	}



	/**
	 * Load Scripts
	 *
	 * @return void
	 * @uses $this->is_script(), $this->is_stylesheet(), wp_enqueue_script(), wp_enqueue_style()
	 * @author Ralf Hortt
	 **/
	function load_scripts()
	{
		global $post;

		if ( !is_admin() ) :

			$files = get_post_meta( $post->ID, '_cjl-files', true);

			if ( $files ) :

				foreach ( $files as $file ) :

					if ( $this->is_script( $file ) ) :
						wp_enqueue_script( $file, get_bloginfo('template_directory') . '/' . $file );
					elseif ( $this->is_stylesheet( $file ) ) :
						wp_enqueue_style( $file, get_bloginfo('template_directory') . '/' . $file );
					endif;

				endforeach;

			endif;

		endif;
	}



	/**
	 * The Metabox
	 *
	 * @uses get_post_meta(), $this->files_option()
	 * @author Ralf Hortt
	 **/
	function metabox( $post )
	{
		$loaded_files = get_post_meta( $post->ID, '_cjl-files', true );
		?>
		<select multiple="multiple" name="cjl-files[]" id="cjl-files" style="height:250px; width: 100%;">
			<?php $this->files_options( $loaded_files ); ?>
		</select>
		<?php
	}



	/**
	 * Hook on save_post 
	 *
	 * @return void
	 * @uses update_post_meta()
	 * @author Ralf Hortt
	 **/
	function save_post( $post_id )
	{
		if ( post_type_supports( $_POST['post_type'], 'css-js-loader' ) ) :
			if ( $_POST['cjl-files'] ) :
				update_post_meta( $post_id, '_cjl-files', $_POST['cjl-files'] );
			else :
				delete_post_meta( $post_id, '_cjl-files' );
			endif;
		endif;
	}
}

$CJL = new CSS_JS_Loader();
?>