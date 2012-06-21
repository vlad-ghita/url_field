<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	define_safe(UF_NAME, 'Field: URL');
	define_safe(UF_GROUP, 'url_field');



	class Extension_URL_Field extends Extension
	{
		const FIELD_TABLE = 'tbl_fields_url';

		protected static $assets_loaded = false;



		/*------------------------------------------------------------------------------------------------*/
		/*  Installation  */
		/*------------------------------------------------------------------------------------------------*/

		public function install(){
			return Symphony::Database()->query(sprintf(
				"CREATE TABLE `%s` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`field_id` INT(11) UNSIGNED NOT NULL,
					`related_field_id` VARCHAR(255) NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
				self::FIELD_TABLE
			));
		}

		public function uninstall(){
			try{
				Symphony::Database()->query(sprintf(
					"DROP TABLE `%s`",
					self::FIELD_TABLE
				));
			}
			catch( DatabaseException $dbe ){
				// table deosn't exist
			}
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Utilities  */
		/*------------------------------------------------------------------------------------------------*/

		public static function appendAssets(){
			if(
				!self::$assets_loaded
				&& class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			){
				$page = Administration::instance()->Page;

				$page->addStylesheetToHead(URL.'/extensions/'.UF_GROUP.'/assets/'.UF_GROUP.'.publish.css', 'screen', null, false);
				$page->addScriptToHead(URL.'/extensions/'.UF_GROUP.'/assets/'.UF_GROUP.'.publish.js', null, false);

				self::$assets_loaded = true;
			}
		}

	}
