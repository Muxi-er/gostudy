<?php

	lt_include( PLOG_CLASS_PATH."class/summary/action/summaryaction.class.php" );
    lt_include( PLOG_CLASS_PATH."class/summary/dao/summarystats.class.php" );
    lt_include( PLOG_CLASS_PATH."class/data/timestamp.class.php" );
    lt_include( PLOG_CLASS_PATH."class/dao/blogs.class.php" );
	lt_include( PLOG_CLASS_PATH."class/summary/view/summaryrssview.class.php" );
	lt_include( PLOG_CLASS_PATH."class/net/rawrequestgenerator.class.php" );
    lt_include( PLOG_CLASS_PATH."class/data/validator/integervalidator.class.php" );
	
	define( "SUMMARY_RSS_TYPE_DEFAULT", "default" );
	define( "SUMMARY_RSS_TYPE_MOST_COMMENTED", "mostcommented" );
	define( "SUMMARY_RSS_TYPE_MOST_READ", "mostread" );
	define( "SUMMARY_RSS_TYPE_MOST_ACTIVE_BLOGS", "mostactiveblogs" );
	define( "SUMMARY_RSS_TYPE_NEWEST_BLOGS", "newestblogs" );
	define( "SUMMARY_RSS_TYPE_POSTS_LIST", "postslist" );
	define( "SUMMARY_RSS_TYPE_BLOGS_LIST", "blogslist" );	

     /**
      * This is the one and only default action. It simply fetches all the most recent
      * posts from the database and shows them. The default locale is the one specified
      * in the configuration file and the amount of posts shown in this page is also
      * configurable through the config file.
      */
     class SummaryRssAction extends SummaryAction
     {

        function SummaryRssAction( $actionInfo, $request )
        {
            $this->SummaryAction( $actionInfo, $request );
        }
		
		function validate()
		{
			// make sure that the mode is set to something meaningful...
			$this->_mode = $this->_request->getValue( "type" );
			if( $this->_mode != SUMMARY_RSS_TYPE_DEFAULT &&
			    $this->_mode != SUMMARY_RSS_TYPE_MOST_COMMENTED &&
				$this->_mode != SUMMARY_RSS_TYPE_MOST_READ &&
				$this->_mode != SUMMARY_RSS_TYPE_MOST_ACTIVE_BLOGS &&
				$this->_mode != SUMMARY_RSS_TYPE_NEWEST_BLOGS &&
				$this->_mode != SUMMARY_RSS_TYPE_POSTS_LIST &&
				$this->_mode != SUMMARY_RSS_TYPE_BLOGS_LIST ) {
				
				// in case the parameter looks weird, let's use a default one...
				$this->_mode = SUMMARY_RSS_TYPE_DEFAULT;
			}
			
			$this->_profile = $this->_request->getValue( "profile" );
			
			return true;
		}

        /**
         * Loads the posts and shows them.
         */
        function perform()
        {
            if( $this->_mode == SUMMARY_RSS_TYPE_MOST_COMMENTED ||
                $this->_mode == SUMMARY_RSS_TYPE_MOST_READ ||
                $this->_mode == SUMMARY_RSS_TYPE_DEFAULT ||
                $this->_mode == SUMMARY_RSS_TYPE_POSTS_LIST ) {	                
	                
            	// get the globalArticleCategoryId from request
				$globalArticleCategoryId = $this->_request->getValue( "globalArticleCategoryId" );
				$val = new IntegerValidator();
				if( !$val->validate( $globalArticleCategoryId ))
					$globalArticleCategoryId = ALL_GLOBAL_ARTICLE_CATEGORIES;
				    			
	            // RSS feeds for posts stuff
	            $this->_view = new SummaryRssView( $this->_profile, Array( "summary" => "rss", 
			                                       "globalArticleCategoryId" => $globalArticleCategoryId,
			                                       "mode" => $this->_mode,
												   "profile" => $this->_profile ));
				if( $this->_view->isCached()) {
					$this->setCommonData();
					return true;
				}
		
            	$blogs       = new Blogs();
            	$stats       = new SummaryStats();
	                
				if( $this->_mode == SUMMARY_RSS_TYPE_MOST_COMMENTED ) {
					$postslist = $stats->getMostCommentedArticles();
				}
				elseif( $this->_mode == SUMMARY_RSS_TYPE_MOST_READ ) {
					$postslist = $stats->getMostReadArticles();			
				}
				elseif( $this->_mode == SUMMARY_RSS_TYPE_POSTS_LIST ) {
     				lt_include( PLOG_CLASS_PATH."class/dao/globalarticlecategories.class.php" );
            		lt_include( PLOG_CLASS_PATH."class/config/config.class.php" );
            		
            		// get the summary_items_per_page from config
            		$config =& Config::getConfig();
            		$summaryItemsPerPage = $config->getValue( "summary_items_per_page", SUMMARY_DEFAULT_ITEMS_PER_PAGE );
					
					$categories = new GlobalArticleCategories();
					$currentGlobalArticleCategory = $categories->getGlobalArticleCategory( $globalArticleCategoryId );
					
					if( empty($currentGlobalArticleCategory) )
						$globalArticleCategoryId = ALL_GLOBAL_ARTICLE_CATEGORIES;
						
					$postslist = $stats->getPostsByGlobalCategory( $globalArticleCategoryId,
        										 					 $page = 1, 
        										 					 $summaryItemsPerPage );
				}				
				else {
					lt_include( PLOG_CLASS_PATH."class/dao/globalarticlecategories.class.php" );
					$categories = new GlobalArticleCategories();
					$currentGlobalArticleCategory = $categories->getGlobalArticleCategory( $globalArticleCategoryId );
					
					if( empty($currentGlobalArticleCategory) )
						$globalArticleCategoryId = ALL_GLOBAL_ARTICLE_CATEGORIES;
					
					$postslist = $stats->getRecentArticles( $globalArticleCategoryId );
				}
	
	            if( !$postslist ) {
					$postslist = Array();
	            }
	
				$this->_view->setValue( "posts", $postslist );
			}
			elseif( $this->_mode == SUMMARY_RSS_TYPE_MOST_ACTIVE_BLOGS ||
			        $this->_mode == SUMMARY_RSS_TYPE_NEWEST_BLOGS ||
			        $this->_mode == SUMMARY_RSS_TYPE_BLOGS_LIST ) {
				
            	// get the globalArticleCategoryId from request
				$blogCategoryId = $this->_request->getValue( "blogCategoryId" );
				$val = new IntegerValidator();
				if( !$val->validate( $blogCategoryId ))
					$blogCategoryId = ALL_BLOG_CATEGORIES;

				// RSS feeds for blogs, need different template sets...
	            $this->_view = new SummaryRssView( "blogs_".$this->_profile, Array( "summary" => "rss",
	            								   "blogCategoryId" => $blogCategoryId, 
			                                       "mode" => $this->_mode,
												   "profile" => $this->_profile ));
				if( $this->_view->isCached()) {
					$this->setCommonData();
					return true;
				}
				
				// load the stuff
				$blogs = new Blogs();
				$stats = new SummaryStats();
				
				if( $this->_mode == SUMMARY_RSS_TYPE_MOST_ACTIVE_BLOGS ) {
					$blogslist = $stats->getMostActiveBlogs();	
				}
				elseif( $this->_mode == SUMMARY_RSS_TYPE_BLOGS_LIST ) {
     				lt_include( PLOG_CLASS_PATH."class/dao/blogcategories.class.php" );
            		lt_include( PLOG_CLASS_PATH."class/config/config.class.php" );
            		
            		// get the summary_items_per_page from config
            		$config =& Config::getConfig();
            		$summaryItemsPerPage = $config->getValue( "summary_items_per_page", SUMMARY_DEFAULT_ITEMS_PER_PAGE );
					
					$categories = new BlogCategories();
					$currentBlogCategory = $categories->getBlogCategory( $blogCategoryId );
					
					if( empty($currentBlogCategory) )
						$blogCategoryId = ALL_BLOG_CATEGORIES;
						
					$blogslist = $blogs->getAllBlogs( BLOG_STATUS_ACTIVE, 
													  $blogCategoryId, 
													  "", 
													  1, 
													  $summaryItemsPerPage );
				}
				else {
					$blogslist = $stats->getRecentBlogs();
				}
				
				// in case there is really no data to fetch...
				if( !$blogslist )
					$blogslist = Array();
					
				$this->_view->setValue( "blogs", $blogslist );								
			}
			
			$this->_view->setValue( "type", $this->_mode );
			$this->_view->setValue( "summary", true );

			// this 'url' object is just a dummy one... But we cannot get it from the list
			// of blogs that we fetched because it could potentially be null! Besides, we only
			// need it to generate the base url to rss.css and to summary.php, so no need to
			// have a fully-featured object
			$this->_view->setValue( "url", new RawRequestGenerator( null ));
			
			$this->setCommonData();		

            return true;
        }
     }
?>