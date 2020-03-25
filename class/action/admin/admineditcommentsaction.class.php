<?php

	lt_include( PLOG_CLASS_PATH."class/action/admin/adminaction.class.php" );
    lt_include( PLOG_CLASS_PATH."class/view/admin/adminarticlecommentslistview.class.php" );
	lt_include( PLOG_CLASS_PATH."class/view/admin/adminpostslistview.class.php" );
    lt_include( PLOG_CLASS_PATH."class/data/validator/integervalidator.class.php" );
    lt_include( PLOG_CLASS_PATH."class/data/filter/htmlfilter.class.php" );
	
    /**
     * \ingroup Action
     * @private
     *
     * Action that shows a list of all the comments or trackbacks for a given post
     */
    class AdminEditCommentsAction extends AdminAction 
	{

    	/**
         * Constructor. If nothing else, it also has to call the constructor of the parent
         * class, BlogAction with the same parameters
         */
        function AdminEditCommentsAction( $actionInfo, $request )
        {
        	$this->AdminAction( $actionInfo, $request );
			
			// data validation
			$this->registerFieldValidator( "articleId", new IntegerValidator(), true);
			$this->registerFieldValidator( "showStatus", new IntegerValidator( true ), true);
			$view = new AdminPostsListView( $this->_blogInfo );
			$view->setErrorMessage( $this->_locale->tr("error_fetching_comments"));
			$this->setValidationErrorView( $view );
			
			// we do this so that AdminEditTrackbacksAction can basically extend this class and provide
			// a different view... it will allow us to save some extra code!
			$this->_viewClass = "AdminArticleCommentsListView";
			
			$this->requirePermission( "view_comments" );
        }

        /**
         * Carries out the specified action
         */
        function perform()
        {
			// get the validated parameters from the request
        	$articleId = $this->_request->getValue( "articleId" );
			$showStatus = $this->_request->getValue( "showStatus" );
			$searchTerms = $this->_request->getFilteredValue( "searchTerms", new HtmlFilter());
			
			if( $articleId && $articleId > 0 ) {
				$articles = new Articles();
				$article = $articles->getBlogArticle( $articleId, $this->_blogInfo->getId());
				if( !$article ) {
					$this->_view = new AdminPostsListView( $this->_blogInfo );
					$this->_view->setErrorMessage( $this->_locale->tr("error_fetching_article" ));				
				}			
				else
					$this->_view = new $this->_viewClass( $this->_blogInfo, Array( "article" => $article,
																				   "showStatus" => $showStatus,
																				   "searchTerms" => $searchTerms ));
			}
			else {
				// if there is no article id, then we will show all comments from all posts...
				$this->_view = new $this->_viewClass( $this->_blogInfo, Array( "article" => null,
																			   "showStatus" => $showStatus,
																			   "searchTerms" => $searchTerms ));					
			}
			
            $this->setCommonData();

            // better to return true if everything fine
            return true;
        }
    }
?>