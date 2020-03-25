<?php

	lt_include( PLOG_CLASS_PATH."class/action/admin/adminaction.class.php" );
    lt_include( PLOG_CLASS_PATH."class/view/admin/adminglobalsettingslistview.class.php" );

    /**
     * \ingroup Action
     * @private
     *
     * List of all the available settings for the site
     */
    class AdminGlobalSettingsAction extends AdminAction 
	{

    	function AdminGlobalSettingsAction( $actionInfo, $request )
        {
        	$this->AdminAction( $actionInfo, $request );

			$this->requireAdminPermission( "view_global_settings" );
        }

        function perform()
        {
            // if no problem, continue
            $show = $this->_request->getValue( "show" );
            $this->_view = new AdminGlobalSettingsListView( $this->_blogInfo, $show );
            $this->setCommonData();

            return true;
        }
    }
?>
