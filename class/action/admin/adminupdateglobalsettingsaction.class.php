<?php

	lt_include( PLOG_CLASS_PATH."class/action/admin/adminaction.class.php" );
    lt_include( PLOG_CLASS_PATH."class/view/admin/adminglobalsettingslistview.class.php" );

    /**
     * \ingroup Action
     * @private
     *
     * Updates the settings of the site
     */
    class AdminUpdateGlobalSettingsAction extends AdminAction 
    {

    	var $_newConfigOpts;

    	function AdminUpdateGlobalSettingsAction( $actionInfo, $request )
        {
        	$this->AdminAction( $actionInfo, $request );

			$this->requireAdminPermission( "update_global_settings" );
        }

        function validate()
        {
	    	// all the seettings come from a very nice array from the html form
            $this->_newConfigOpts = Array();
            $this->_newConfigOpts = $this->_request->getValue( "config" );

            // the xmlrpc_ping_hosts requires special treatment, since we need to
            // split the input returned from the textbox into an array
            if( isset( $this->_newConfigOpts["xmlrpc_ping_hosts"])) {
                $array = Array();
                foreach(explode( "\r\n", $this->_newConfigOpts["xmlrpc_ping_hosts"] ) as $host ) {
                	trim($host);
                	if( $host != "" && $host != "\r\n" && $host != "\r" && $host != "\n" )
                    	array_push( $array, $host );
                }
                $this->_newConfigOpts["xmlrpc_ping_hosts"] = $array;
            }

                // the custom URL strings need some extra validation
            $customUrlFormats = array(
                "permalink_format",
                "category_link_format",
                "blog_link_format",
                "archive_link_format",
                "user_posts_link_format",
                "post_trackbacks_link_format",
                "template_link_format",
                "album_link_format",
                "resource_link_format",
                "page_suffix_format");

            foreach($customUrlFormats as $format){
                if(isset($this->_newConfigOpts[$format])){
                    $val = $this->_newConfigOpts[$format];
                    $val = str_replace("\\", "/", $val);
                    $this->_newConfigOpts[$format] = $val;
                }
            }

            // the 'locales' and 'arrays' settings are not coming from the request
            $configOpts = $this->_config->getAsArray();
            $locales = new Locales();
            $this->_newConfigOpts["locales"] = $locales->getAvailableLocales();
            $this->_newConfigOpts["templates"] = $configOpts["templates"];
			
			// the default_blog_id setting is coming from a chooser, so it won't be automatically picked up
            $blogId = $this->_request->getValue( "blogId" );
            if($blogId)
                $this->_newConfigOpts["default_blog_id"] = $blogId;

            return true;
        }

        function perform()
        {
        	// get the global setting section
        	$show = $this->_request->getValue( "show" );
        	
        	// we can proceed to update the config
            foreach( $this->_newConfigOpts as $key => $value ) {
            	$this->_config->setValue( $key, $value );
            }
            // and finally save everything
            $res = $this->_config->save();

            // depending on the result, we shall show one thing or another...
            if( $res ) {
            	$this->_view = new AdminGlobalSettingsListView( $this->_blogInfo, $show );
                $this->_view->setSuccessMessage( $this->_locale->tr("site_config_saved_ok"));
                $this->setCommonData();
				// clear the contents of all the caches
				CacheControl::resetAllCaches();
            }
            else {
            	$this->_view = new AdminGlobalSettingsListView( $this->_blogInfo, $show );
                $this->_view->setErrorMessage( $this->_locale->tr("error_saving_site_config"));
                $this->setCommonData();
            }

            return $res;
        }
    }
?>
