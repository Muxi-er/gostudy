<?php

	lt_include( PLOG_CLASS_PATH."class/action/admin/adminaction.class.php" );
    lt_include( PLOG_CLASS_PATH."class/view/admin/chooser/adminsimpleresourceslistview.class.php" );

    /**
     * \ingroup Action
     * @private
     *
     * shows a simple list with the resources so that users can choose one
     */
    class AdminResourceListAction extends AdminAction
    {
		var $_albumId;

    	/**
         * Constructor. If nothing else, it also has to call the constructor of the parent
         * class, BlogAction with the same parameters
         */
        function AdminResourceListAction( $actionInfo, $request )
        {
        	$this->AdminAction( $actionInfo, $request );
			
            $this->_albumId = $this->_request->getValue( "albumId" );
            if( $this->_albumId == "" )
            	$this->_albumId = 0;	
            	
            $this->requirePermission( "view_resources" );		
        }

        /**
         * Carries out the specified action
         */
        function perform()
        {
			$this->_view = new AdminSimpleResourcesListView( $this->_blogInfo, Array( "albumId" => $this->_albumId));
            $this->setCommonData();

            return true;
        }
    }
?>
