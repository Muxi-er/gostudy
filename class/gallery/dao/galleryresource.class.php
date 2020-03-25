<?php

	lt_include( PLOG_CLASS_PATH."class/database/dbobject.class.php" );
    lt_include( PLOG_CLASS_PATH."class/gallery/galleryconstants.php" );
    
    // Add this to avoid long file name error in windows server
    define('GETID3_HELPERAPPSDIR', 'no_helper_apps_needed'); 

    /**
	 * \ingroup Gallery
	 *
     * Encapsulates a resource from our database.
	 *
	 * Each GalleryResource object can belong to only one GalleryAlbum object, and therefore every object
	 * has a reference to its album.
	 *
	 * This class also provides methods for getting the correct metadata reader, for checking the type of the
	 * resource, for getting access to the thumbnail of the object, etc.
     */
    class GalleryResource extends DbObject
    {

    	var $_id;
        var $_ownerId;
        var $_albumId;
        var $_date;
        var $_description;
        var $_flags;
        var $_resourceType;
        var $_filePath;
        var $_fileName;
        var $_metadata;
        var $_album;
		var $_thumbnailFormat;
		var $_fileDescriptor;
		var $_fileSize;
		
		/**
		 * Constructor.
		 *
		 * @param ownerId Id of the user to whom this resource belongs
		 * @param albumId The id of the GalleryAlbum object to which this resource belongs
		 * @param description Description of this file
		 * @param flags As of pLog 1.0, there is only one flag available: GALLERY_RESOURCE_PREVIEW_AVAILABLE.
		 * @param resourceType The type of the resource. One of the following constants:
		 * - GALLERY_RESOURCE_IMAGE
		 * - GALLERY_RESOURCE_VIDEO
		 * - GALLERY_RESOURCE_SOUND
		 * - GALLERY_RESOURCE_UNKNOWN
		 * - GALLERY_RESOURCE_DOCUMENT
		 * - GALLERY_RESOURCE_ZIP
		 * @param filePath path in disk to the real file. Not used.
		 * @param fileName name of the file, which is not exactly the same name that the file has in disk, since the
		 * GalleryResourceStorage class is taking care of managing things in disk. Nevertheless, this is the file that will
		 * be shown to users in the user interface
		 * @param metadata An array, as generated by the getID3 class.
		 * @param date A SQL date
		 * @param thumbnailFormat The format of the thumbnail
		 * @param id Optinally, the id. When creating new resources, the GalleryResources::addResource() method will
		 * update the id so it does not matter what we provide here.
		 * @see getID3
		 */
        function GalleryResource( $ownerId, $albumId, $description, $flags, $resourceType,
                                  $filePath, $fileName, $metadata, $date, $thumbnailFormat, $properties = Array(), $id = -1 )
        {
            $this->DbObject();
        	$this->_ownerId = $ownerId;
            $this->_albumId = $albumId;
            $this->_description = $description;
            $this->_flags = $flags;
            $this->_resourceType = $resourceType;
            $this->_filePath = $filePath;
            $this->_fileName = $fileName;
			$this->_fileSize = 0;
            $this->_metadata = $metadata;
            $this->_date = $date;
			$this->_thumbnailFormat = $thumbnailFormat;
            $this->_id = $id;
            $this->_album = null;
            $this->setProperties( $properties );
            
            $this->_fields = Array(
                "owner_id" => "getOwnerId",
                "album_id" => "getAlbumId",
                "description" => "getDescription",
                "date" => "getDate",
                "flags" => "getFlags",
                "resource_type" => "getResourceType",
                "file_path" => "getFilePath",
                "file_name" => "getFileName",
                "file_size" => "getFileSize", 
                "thumbnail_format" => "getThumbnailFormat",
                "normalized_description" => "getNormalizedDescription",
                "properties" => "getProperties",
                "metadata" => "getMetadata"
            );
            $this->_fileDescriptor = false;
        }

		/**
		 * @return the identifier of the resource, or -1 if none has been set yet.
		 */
        function getId()
        {
        	return $this->_id;
        }

		/**
		 * @return the identifier of the owner of this resource
		 */
        function getOwnerId()
        {
        	return $this->_ownerId;
        }

		/**
		 * @return the bloginfo of the owner of this resource
		 */        
        function getBlogInfo()
        {
    		lt_include( PLOG_CLASS_PATH."class/dao/blogs.class.php" );
    		$blogs = new Blogs();
    		$blogInfo = $blogs->getBlogInfo( $this->_ownerId );
        	return $blogInfo;
        }

		/**
		 * @return returns the identifier of the GalleryAlbum object to which this resource belongs
		 */
        function getAlbumId()
        {
        	return $this->_albumId;
        }

		/**
		 * @return Returns a 14-digit SQL date
		 */
        function getDate()
        {
        	return $this->_date;
        }

		/**
		 * @return Returns a Timestamp object
		 * @see Timestamp
		 */	
        function getTimestamp()
        {
            // source necessary source
            lt_include( PLOG_CLASS_PATH."class/data/timestamp.class.php" );

        	return new Timestamp($this->_date);
        }

		/**
		 * @return returns the "raw" metadata information, as generated by the getID3 class. It is
		 * advisable to use the GalleryResource::getResourceMetadataReader to get the right
		 * metadata reader class, since these classes provide convenience methods for accessing
		 * the most common attributes of sound files, videos, etc.
		 */
        function getMetadata()
        {
        	return $this->_metadata;
        }
		
		/**
		 * Set the resource metadata to the given array
		 *
		 * @param metadata
		 * @return Nothing
		 */
		function setMetadata( $metadata )
		{
			$this->_metadata = $metadata;
		}

		/**
		 * @return the flags of the resource object
		 */
        function getFlags()
        {
        	return $this->_flags;
        }

		/**
		 * @return The path of the file in disk
		 */
        function getFilePath()
        {
        	return $this->_filePath;
        }

		/**
		 * @return the name of the file in disk
		 */
        function getFileName()
        {
        	return $this->_fileName;
        }

		/**
		 * @return the encoded name of the file in disk
		 */
        function getEncodedFileName()
        {
            $fileParts = explode( ".", $this->_fileName );
            $fileExt = strtolower($fileParts[count($fileParts)-1]);
            $encodedFileName = $this->getOwnerId()."-".$this->getId().".".$fileExt;
        	return $encodedFileName;
        }

		/**
		 * @return the description of the resource
		 */
        function getDescription()
        {
        	return $this->_description;
        }

		/**
		 * returns the mime type of the resource
		 *
		 * @return a valid mime type
		 */
        function getMimeType()
        {
        	lt_include( PLOG_CLASS_PATH."class/data/mimetype.class.php" );        
        
        	$mimeType = new MimeType();
        	return $mimeType->getType( $this->_fileName );
        }
		
		/**
		 * returns the mime type of the thumbnails
		 *
		 * @return a valid mime type
		 */
		function getThumbnailMimeType()
		{
		  lt_include( PLOG_CLASS_PATH."class/data/mimetype.class.php" );
			if( $this->getThumbnailFormat() == "same" )
				return $this->getMimeType();
			else {
				$mimeType = new MimeType();
				return $mimeType->getType( $this->getThumbnailFormat());
			}
		}

        function getResourceType()
        {
        	return $this->_resourceType;
        }

		/**
		 * Sets the album id. You should normally not need to use this method
		 *
		 * @param albumId The id of the album
		 */
        function setAlbumId( $albumId )
        {
        	$this->_albumId = $albumId;
        }

		/**
		 * Sets the identifier of the owner of this resource
		 *
		 * @param ownerId The identifier of the owner
		 */
		function setOwnerId( $ownerId )
		{
			$this->_ownerId = $ownerId;
		}

		/**
		 * Sets the GalleryAlbum object to which this file belongs
		 */	
        function setAlbum( $album )
        {
        	$this->_album = $album;
        }
		
		/**
		 * @return The GalleryAlbum object to which this resource belongs
		 */		
		function getAlbum()
		{
			if( $this->_album == null ) {
				lt_include( PLOG_CLASS_PATH."class/gallery/dao/galleryalbums.class.php" );
				$albums = new GalleryAlbums();
				$this->_album = $albums->getAlbum( $this->getAlbumId());
			}
			
			return $this->_album;
		}

		/**
		 * Sets the description of the object
		 *
		 * @param description the new description
		 */
        function setDescription( $description )
        {
        	$this->_description = $description;
        }
		
		/**
		 * @return returns the format of the thumbnail that was generated for this file, if any. Of
		 * course this method has no relevance if the object is not representing an image
		 */		
		function getThumbnailFormat()
		{
			return $this->_thumbnailFormat;
		}
		
		/**
		 * Sets the thumbnail format
		 *
		 * @return nothing
		 */		
		function setThumbnailFormat( $format )
		{
			$this->_thumbnailFormat = $format;
		}
		
		/**
		 * returns the size of the resource file
		 *
		 * @return the size of the file in bytes
		 */
		function getFileSize()
		{
			return( $this->_fileSize );
		}
		
		function setFileSize( $size )
		{
			$this->_fileSize = $size;
		}

		/**
		 * returns an object that will allow to access the metadata of this resource
		 *
		 * @see GalleryResourceImageMetadataReader
		 * @see GalleryResourceSoundMetadataReader
		 * @see GalleryResourceVideoMoetadataReader
		 * @see GalleryResourceZipMetadataReader
		 * @see GalleryResourceBaseMetadataReader
		 */
        function getMetadataReader()
        {
                
        	switch( $this->getResourceType()) {
            	case GALLERY_RESOURCE_IMAGE:
                  lt_include( PLOG_CLASS_PATH."class/gallery/data/galleryresourceimagemetadatareader.class.php" );
                	$reader = new GalleryResourceImageMetadataReader( $this );
                    break;
                case GALLERY_RESOURCE_SOUND:
                  lt_include( PLOG_CLASS_PATH."class/gallery/data/galleryresourcesoundmetadatareader.class.php" );
                	$reader = new GalleryResourceSoundMetadataReader( $this );
                    break;
                case GALLERY_RESOURCE_VIDEO:
                  lt_include( PLOG_CLASS_PATH."class/gallery/data/galleryresourcevideometadatareader.class.php" );
                	$reader = new GalleryResourceVideoMetadataReader( $this );
                    break;
                case GALLERY_RESOURCE_ZIP:
                  lt_include( PLOG_CLASS_PATH."class/gallery/data/galleryresourcezipmetadatareader.class.php" );
                	$reader = new GalleryResourceZipMetadataReader( $this );
                    break;
                default:
                  lt_include( PLOG_CLASS_PATH."class/gallery/data/galleryresourcebasemetadatareader.class.php" );
                	$reader = new GalleryResourceBaseMetadataReader( $this );
                    break;
            }

            return $reader;
        }

        /**
         * Returns true if this resource has a preview or false if not.
         *
         * @return True if preview available or false otherwise.
         */
        function hasPreview()
        {
        	return( $this->_flags & GALLERY_RESOURCE_PREVIEW_AVAILABLE );
        }
		
		/**
		 * returns the name of the file in disk where the preview is
		 * stored
		 *
		 * @return the path to the small preview file
		 */
		function getPreviewFileName()
		{			
    		lt_include( PLOG_CLASS_PATH."class/gallery/dao/galleryresourcestorage.class.php" );	

			$config =& Config::getConfig();

			// encoding the filename if "encoded_file_name" enabled
			if( $config->getValue( "resources_naming_rule" ) == "encoded_file_name" )
				$fileName = $this->getEncodedFileName();
			else
				$fileName = $this->getFileName();
		
			// change the file extension, if the thumbnail output format is different from the original file
			if( $this->getThumbnailFormat() == THUMBNAIL_OUTPUT_FORMAT_SAME_AS_IMAGE )
				$previewFile = $fileName;
			else {
                $previewFile = preg_replace("/\.".$this->getFileExtension()."/i",
                                            ".".strtolower($this->getThumbnailFormat()),
                                            $fileName);
			}

			return $previewFile;
		}
		
		/**
		 * Returns the file extension
		 *
		 * @param toLower Whether the extension should be returned in lower case, false by default
		 * @return The file extension
		 */
		function getFileExtension( $toLower = false )
		{
			$fileName = $this->getFileName();
			if(( $extPos = strrpos( $fileName, "." )) !== false ) {					
				$fileExt = substr( $fileName, $extPos+1, strlen( $fileName ));
			}
			else {
				$fileExt = "";
			}			
			
			return( $fileExt );
		}

		/**
		 * returns the full path to the file with the medium-sized preview
		 *
		 * @return full path to the medium-sized preview
		 */
		function getMediumSizePreviewFileName()
		{
			lt_include( PLOG_CLASS_PATH."class/gallery/dao/galleryresourcestorage.class.php" );		
		
			$config =& Config::getConfig();

			// encoding the filename if "encoded_file_name" enabled
			if( $config->getValue( "resources_naming_rule" ) == "encoded_file_name" )
				$fileName = $this->getEncodedFileName();
			else
				$fileName = $this->getFileName();
			
			// change the file extension, if the thumbnail output format is different from the original file
			if( $this->getThumbnailFormat() == THUMBNAIL_OUTPUT_FORMAT_SAME_AS_IMAGE )
				$previewFile = $fileName;
			else{
                $previewFile = preg_replace("/\.".$this->getFileExtension()."/i",
                                            ".".strtolower($this->getThumbnailFormat()),
                                            $fileName);
            }

			return $previewFile;
		}

		/**
		 * returns the full path to the file with the original size
		 *
		 * @return full path to the original size
		 */
		function getOriginalSizeFileName()
		{
			lt_include( PLOG_CLASS_PATH."class/gallery/dao/galleryresourcestorage.class.php" );		
		
			$config =& Config::getConfig();

			// encoding the filename if "encoded_file_name" enabled
			if( $config->getValue( "resources_naming_rule" ) == "encoded_file_name" )
				$fileName = $this->getEncodedFileName();
			else
				$fileName = $this->getFileName();
			
			return $fileName;
		}
		
		function getNormalizedDescription()
		{
			lt_include( PLOG_CLASS_PATH."class/data/textfilter.class.php" );
			$tf = new Textfilter();
			return( $tf->normalizeText( $this->getDescription()));
		}
		
		/**
		 * @return true if the resource is an image
		 */
        function isImage()
        {
        	return( $this->_resourceType == GALLERY_RESOURCE_IMAGE );
        }

		/**
		 * @return true if the resource is a sound file
		 */
        function isSound()
        {
        	return( $this->_resourceType == GALLERY_RESOURCE_SOUND );
        }

		/**
		 * @return true if the resource file is a video
		 */
        function isVideo()
        {
        	return( $this->_resourceType == GALLERY_RESOURCE_VIDEO );
        }

		/**
		 * @return true if the resource file is a ZIP file
		 */
        function isZip()
        {
        	return( $this->_resourceType == GALLERY_RESOURCE_ZIP );
        }

		/**
		 * returns the link to the resource page
		 */
		function getResourceLink()
		{
			$blog = $this->getBlogInfo();
			$url = $blog->getBlogRequestGenerator();
			return( $url->resourceLink( $this ));			
		}

		/**
		 * returns the download link for this resource
		 */
		function getLink()
		{
			$blog = $this->getBlogInfo();
			$url = $blog->getBlogRequestGenerator();
			return( $url->resourceDownloadLink( $this ));
		}
		
		/**
		 * returns the preview link (thumbnail) for this resource if it's an image, or an empty string otherwise
		 */
		function getPreviewLink()
		{
			$link = "";
			if( $this->isImage()) {			
				$blog = $this->getBlogInfo();
				$url = $blog->getBlogRequestGenerator();
				$link = $url->resourcePreviewLink( $this );
			}
			
			return( $link );
		}
		
		/**
		 * returns the medium preview link (thumbnail) for this resource if it's an image, 
		 * or an empty string otherwise
		 */
		function getMediumPreviewLink()
		{
			$link = "";
			if( $this->isImage()) {			
				$blog = $this->getBlogInfo();
				$url = $blog->getBlogRequestGenerator();
				$link = $url->resourceMediumSizePreviewLink( $this );
			}
			
			return( $link );			
		}		
    }
?>