<?php

	lt_include( PLOG_CLASS_PATH."class/action/admin/adminaction.class.php" );
    lt_include( PLOG_CLASS_PATH."class/view/admin/adminpostslistview.class.php" );
	lt_include( PLOG_CLASS_PATH."class/data/validator/integervalidator.class.php" );
	lt_include( PLOG_CLASS_PATH."class/data/filter/htmlfilter.class.php" );

    /**
     * \ingroup Action
     * @private
     *
     * Fetches all the posts and offers them for edition or deletion.
     */
    class AdminEditPostsAction extends AdminAction 
	{
    	/**
         * Constructor. If nothing else, it also has to call the constructor of the parent
         * class, BlogAction with the same parameters
         */
        function AdminEditPostsAction( $actionInfo, $request )
        {
        	$this->AdminAction( $actionInfo, $request );

			// field validation
			$this->registerFieldValidator( "showCategory", new IntegerValidator( true ));
			$this->registerFieldValidator( "showStatus", new IntegerValidator( true ));
			$this->registerFieldValidator( "showUser", new IntegerValidator());
			$this->registerFieldValidator( "showMonth", new IntegerValidator( true ));

			$this->requirePermission( "view_posts" );
        }

		/**
		 * We're going to do some manual validation here because we want to capture the
		 * validation problem but instead of showing an error, we're just going to fix
		 * here (by resetting the value) and continue with the show as if nothing
		 * had happened.
		 */
		function validate()
		{
			$intVal = new IntegerValidator( true );
			$uIntVal = new IntegerValidator();
			
			if( !$intVal->validate( $this->_request->getValue( "showCategory" )))
				$this->_request->setValue( "showCategory", -1 );
				
			if( !$intVal->validate( $this->_request->getValue( "showStatus" )))
				$this->_request->setValue( "showStatus", -1 );
				
			if( !$uIntVal->validate( $this->_request->getValue( "showUser" )))
				$this->_request->setValue( "showUser", 0 );
				
			if( !$intVal->validate( $this->_request->getValue( "showMonth" )))
				$this->_request->setValue( "showMonth", -1 );
				
			return( true );
		}

        /**
         * Carries out the specified action
         */
        function perform()
        {
			$this->_searchTerms = $this->_request->getFilteredValue( "searchTerms", new HtmlFilter());
			// create the view with the right parameters... 
        	$this->_view = new AdminPostsListView( $this->_blogInfo, 
			                                       Array( "showCategory" => $this->_request->getValue( "showCategory" ),
												          "showStatus" => $this->_request->getValue( "showStatus" ),
														  "showUser" => $this->_request->getValue( "showUser" ),
														  "showMonth" => $this->_request->getValue( "showMonth" ),
														  "searchTerms" => $this->_searchTerms ));
            $this->setCommonData();

            // better to return true if everything fine
            return true;
        }
    }
?>