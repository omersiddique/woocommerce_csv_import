<?php
   /*
   Plugin Name: Product Import Unitex
   Description: Import Rugs from Unitex Master Feed
   Version: 1.0
   Author: Omer Siddique
   Author URI: https://teslait.com.au
   License: MIT
   */
$allAttributes = array();
$allVariables = array();
$productIds = array();
$lastProductIdentifier1 = null;
$lastProductIdentifier2 = null;
$lastProductIdentifier3 = null;
$productLength = 0;
$rugsID = 189;

// function sydneywidecarpets_removeAllProducs(){
//    global $wpdb;
   
//    $wpdb->query("DELETE FROM wp_terms WHERE term_id IN (SELECT term_id FROM wp_term_taxonomy WHERE taxonomy LIKE 'pa_%')");
//    $wpdb->query("DELETE FROM wp_term_taxonomy WHERE taxonomy LIKE 'pa_%'");
//    $wpdb->query("DELETE FROM wp_term_relationships WHERE term_taxonomy_id not IN (SELECT term_taxonomy_id FROM wp_term_taxonomy)");
//    $wpdb->query("DELETE FROM wp_term_relationships WHERE object_id IN (SELECT ID FROM wp_posts WHERE post_type IN ('product','product_variation'))");
//    $wpdb->query("DELETE FROM wp_postmeta WHERE post_id IN (SELECT ID FROM wp_posts WHERE post_type IN ('product','product_variation'))");
//    $wpdb->query("DELETE FROM wp_posts WHERE post_type IN ('product','product_variation')");
//    $wpdb->query("DELETE pm FROM wp_postmeta pm LEFT JOIN wp_posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL");


// }

function sydneywidecarepets_readCSV(){        
 

  global $allAttributes;
  global $allVariables;
  

   $filename = '/0-100.csv';
   $productHandle = '';
   global $lastProductIdentifier1;
   global $lastProductIdentifier2;
   global $lastProductIdentifier3;
   global $productLength;
   $lastProductAdded = null;
   $productCode;
   $n = 1;
   $outerIteration = 1;
   $innerIteration = 1;
   $isVariableProduct;

   $row = 1;
   if (($handle = fopen(plugin_dir_path(__FILE__) . $filename, "r")) !== FALSE) {

         while (($product = fgetcsv($handle, 0, ",")) !== FALSE) {          
        
          //echo '';
          sleep(1);  
          // Split the first CSV entry to get the product code

          $productCode = explode('-', $product[0]);
          $productLength = count($productCode);

          apply_filters( 'console', 'Length of Product = ' .  $productLength );

          
          if ( $lastProductAdded == null){
            // this is the first product on the list
            // Add it to the database

           $lastProductAdded = insertSingleProduct($product);
           updateIdentifiers($productCode);                  

          }

          else{            
            // Is it a Variable product or Simple Product?            
            
            if ($productLength == 3){
              $isVariableProduct = ($lastProductIdentifier1 == $productCode[0] && $lastProductIdentifier2 == $productCode[1]);
            }
            else if ($productLength == 4){
              $isVariableProduct = ($lastProductIdentifier1 == $productCode[0] && $lastProductIdentifier2 == $productCode[1] && $lastProductIdentifier3 == $productCode[2]);
            }
            
            apply_filters ('console', "Is a Variable Product? " . boolval($isVariableProduct));

            if ($isVariableProduct) {
                
               createProductVariation($product, $lastProductAdded);           
   

            } // end if variable product
          
            else{
              // It's a  new product 
              $allAttributes = array();
              $allVariables = array();
              $productIds = array();
              $lastProductAdded = insertSingleProduct($product); 
              updateIdentifiers($productCode);  
            }
          } // end other than first rows      

          
          }  // close while loop 
     
        } // close if handle 

     fclose($handle);  

      
}
  
  
function updateIdentifiers($productCode){
  global $lastProductIdentifier1;
  global $lastProductIdentifier2;
  global $lastProductIdentifier3;

  $lastProductIdentifier1 = $productCode[0];
  $lastProductIdentifier2 = $productCode[1];
  $lastProductIdentifier3 = $productCode[2]; 
}


function insertSingleProduct($product){
  global $productLength;
  global $rugsID;

  $productCode = explode('-', $product[0]);

  apply_filters( 'console', $productCode);

  $productCategory = explode(',', $product[4]);

  apply_filters( 'console', $productCategory);

  $productCategoryInt = getCategoryID($productCategory[0]);

  $productTitle = preg_replace("/[^a-zA-Z\s]/", "", $product[1]); 
  $productTitle = str_replace( 'xcm' ,  '' ,  $productTitle );  

  apply_filters( 'console', $productTitle);

  $product_extraDescription = '  
    <h4>' . $productTitle . '</h4> 
    <table class="rugTables">
    <tr>
      <th>Collection Name</th><td>' . $product[3] . '</td>
    </tr>
    <tr>
      <th>Colour</th><td>' . $product[8] . '</td>
    </tr>
    <tr>
      <th>Style</th><td>' . $product[4] . '</td>
    </tr>
    <tr>
      <th>Shape</th><td>' . $product[7] . '</td>
    </tr>
    <tr>
      <th>Size Association</th><td>' . $product[6] . '</td>
    </tr>
    <tr>
      <th>Unit Weight</th><td>' . $product[16] . '</td>
    </tr>
    <tr>
      <th>Construction</th><td>' . $product[18] . '</td>
    </tr>
    <tr>
      <th>Material</th><td>' . $product[19] . '</td>
    </tr>
    <tr>
      <th>Pile Height</th><td>' . $product[20] . 'MM </td>
    </tr>
    </table>
  ';

  if ($product[22] != ''){
    $video_link = str_replace( 'watch?v=' ,  'embed/' ,  $product[22] );     
    $product_extraDescription = $product_extraDescription .  '<br />
      <iframe width="560" height="315" src="' . $video_link  . '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    ';
  }

   apply_filters( 'console', $product_extraDescription);

  $productNameJoined = '';
  if ($productLength == 3){
    $productNameJoined = $productCode[0] . ' ' . $productCode[1];
  }
  else if ($productLength == 4){
    $productNameJoined = $productCode[0] . ' ' . $productCode[1] . ' ' . $productCode[2];
  }

   apply_filters( 'console', $productNameJoined );

   // Remove any invalid or hidden characters
   $productAbout = preg_replace('/[^A-Za-z0-9\s]/', '', $product[2]);
   

  apply_filters( 'console', 'Product About: ' . $productAbout );

  $post_id = wp_insert_post(array(
                'post_title' => $productNameJoined,
                'post_type' => 'product',
                'post_status' => 'publish', 
                'post_content' => $productAbout
            ));

 apply_filters( 'console', $post_id);

  $productObject = new WC_Product_Variable($post_id);
 
  $productObject->set_short_description( $product_extraDescription  );

    
  $productObject->set_category_ids([$productCategoryInt, $rugsID]);

    apply_filters( 'console', $productObject);
  
  Generate_Featured_Image( $product[23], $post_id  );

  $galleryImgs = array();
  array_push($galleryImgs, $product[24], $product[25], $product[26], $product[27], $product[28], $product[29], $product[30] );

  generate_gallery_images( $galleryImgs, $post_id);
  

  $productObject->save(); // set product is simple/variable/grouped
  wp_set_object_terms( $post_id, 'variable', 'product_type' );

  createProductVariation($product, $post_id);


  return $post_id;

}

function createProductVariation($product, $post_id){
  global $allAttributes;
  global $allVariables;

  array_push($allAttributes, $product[5]);
     $attribute = new WC_Product_Attribute();
                  $attribute->set_name('Size');
                  $attribute->set_options($allAttributes);
                  $attribute->set_visible(true);
                  $attribute->set_variation(true);

                  $productObject = new WC_Product_Variable($post_id);
                  $productObject->set_attributes(array($attribute));
                  $productObject->save();  



  // Create a new variation with the color 'green'.
     $variation = new WC_Product_Variation();
     $variation->set_parent_id($post_id);
     $variation->set_attributes(array('size' => $product[5]));
     $variation->set_status('publish');
     $variation->set_sku( $product[0] );
     $variation->set_price( $product[10] );
     $variation->set_regular_price( $product[10] );
     $variation->set_manage_stock(true);
     $variation->set_stock_quantity(0);
     $variation->save();
     array_push($allVariables, $variation);


                    /*  

                  $i = 0;
                  foreach ($allVariables as $key => $value) {
                               $value->set_status('publish');
                               $value->set_attributes( array('size' => $allAttributes[$i]) );
                               $value->save();
                               $i++;
                   }  */

     
}


function getCategoryID($cat){
  switch ($cat){
    case 'Contemporary':
      return 190;
    case 'Underlay':
      return 199;
    case 'Kids':
      return 198;
    case 'Flate Weave':
      return 197;
    case 'Transitional':
      return 196;
    case 'Cowhide':
      return 195;
    case 'Shag':
      return 194;
    case 'Outdoor':
      return 193;
    case 'Traditional':
      return 192;
    case 'Modern':
      return 191;
    default:
      return 200;
  }
}


function Generate_Featured_Image( $image_url, $post_id  ){

  somatic_attach_external_image( $url = $image_url, $post_id = $post_id , $thumb = true );


/*
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($image_url);

    $filename = basename($image_url);

    if(wp_mkdir_p($upload_dir['path'])){
      $file = $upload_dir['path'] . '/' . $filename;
    }
    else{
      $file = $upload_dir['basedir'] . '/' . $filename;
    }

    file_put_contents($file, $image_data);

    $wp_filetype = wp_check_filetype($filename, null );

    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
    $res2= set_post_thumbnail( $post_id, $attach_id );
  
*/
 /*
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $filename = basename($image_url);

    $file = media_sideload_image( $image_url, $post_id , $filename , 'src' );

   
    $wp_filetype = wp_check_filetype($filename, null );

    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

    $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
    $res2= set_post_thumbnail( $post_id, $attach_id );

    */

}



function generate_gallery_images( $images, $post_id  ){

  
  require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    //$productObject = new WC_Product($post_id);
    $gallery = '';

    foreach ($images as $value) {
     // $filename = basename($value);
      $imgID = os_try_link_attachment( $value, $post_id ); 

     if (is_int($imgID)){
      $gallery = $gallery . strval($imgID) . ',';
     }
     else{
    var_error_log( $imgID );
    }
    
      update_post_meta($post_id,'_product_image_gallery', $gallery);
    }
    

/*
    $gallery = '';

    foreach ($images as $value) {
     // $filename = basename($value);   
      $imgID = crb_insert_attachment_from_url( $value, $post_id );

     
      $gallery = $gallery . strval($imgID) . ',';
          
    }

     update_post_meta($post_id,'_product_image_gallery', $gallery);
    */
}


/**
 * Download an image from the specified URL and attach it to a post.
 * Modified version of core function media_sideload_image() in /wp-admin/includes/media.php  (which returns an html img tag instead of attachment ID)
 * Additional functionality: ability override actual filename, and to pass $post_data to override values in wp_insert_attachment (original only allowed $desc)
 *
 * @since 1.4 Somatic Framework
 *
 * @param string $url (required) The URL of the image to download
 * @param int $post_id (required) The post ID the media is to be associated with
 * @param bool $thumb (optional) Whether to make this attachment the Featured Image for the post (post_thumbnail)
 * @param string $filename (optional) Replacement filename for the URL filename (do not include extension)
 * @param array $post_data (optional) Array of key => values for wp_posts table (ex: 'post_title' => 'foobar', 'post_status' => 'draft')
 * @return int|object The ID of the attachment or a WP_Error on failure
 */
function somatic_attach_external_image( $url = null, $post_id = null, $thumb = null, $filename = null, $post_data = array() ) {
    if ( !$url || !$post_id ) return new WP_Error('missing', "Need a valid URL and post ID...");
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    // Download file to temp location, returns full server path to temp file, ex; /home/user/public_html/mysite/wp-content/26192277_640.tmp
    $tmp = download_url( $url );

    // If error storing temporarily, unlink
    if ( is_wp_error( $tmp ) ) {
        @unlink($file_array['tmp_name']);   // clean up
        $file_array['tmp_name'] = '';
        return $tmp; // output wp_error
    }

    preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $url, $matches);    // fix file filename for query strings
    $url_filename = basename($matches[0]);                                                  // extract filename from url for title
    $url_type = wp_check_filetype($url_filename);                                           // determine file type (ext and mime/type)

    // override filename if given, reconstruct server path
    if ( !empty( $filename ) ) {
        $filename = sanitize_file_name($filename);
        $tmppath = pathinfo( $tmp );                                                        // extract path parts
        $new = $tmppath['dirname'] . "/". $filename . "." . $tmppath['extension'];          // build new path
        rename($tmp, $new);                                                                 // renames temp file on server
        $tmp = $new;                                                                        // push new filename (in path) to be used in file array later
    }

    // assemble file data (should be built like $_FILES since wp_handle_sideload() will be using)
    $file_array['tmp_name'] = $tmp;                                                         // full server path to temp file

    if ( !empty( $filename ) ) {
        $file_array['name'] = $filename . "." . $url_type['ext'];                           // user given filename for title, add original URL extension
    } else {
        $file_array['name'] = $url_filename;                                                // just use original URL filename
    }

    // set additional wp_posts columns
    if ( empty( $post_data['post_title'] ) ) {
        $post_data['post_title'] = basename($url_filename, "." . $url_type['ext']);         // just use the original filename (no extension)
    }

    // make sure gets tied to parent
    if ( empty( $post_data['post_parent'] ) ) {
        $post_data['post_parent'] = $post_id;
    }

    // required libraries for media_handle_sideload
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // do the validation and storage stuff
    $att_id = media_handle_sideload( $file_array, $post_id, null, $post_data );             // $post_data can override the items saved to wp_posts table, like post_mime_type, guid, post_parent, post_title, post_content, post_status

    // If error storing permanently, unlink
    if ( is_wp_error($att_id) ) {
        @unlink($file_array['tmp_name']);   // clean up
        error_log($att_id);
        return $att_id; // output wp_error
    }

    // set as post thumbnail if desired
    if ($thumb) {
        set_post_thumbnail($post_id, $att_id);
    }

    return $att_id;
}

/**
 * Insert an attachment from an URL address.
 *
 * @param  String $url
 * @param  Int    $parent_post_id
 * @return Int    Attachment ID
 */
function crb_insert_attachment_from_url($url, $parent_post_id = null) {

  if( !class_exists( 'WP_Http' ) ){
    include_once( ABSPATH . WPINC . '/class-http.php' );
  }

  $http = new WP_Http();
  $response = $http->request( $url );
  if( $response['response']['code'] != 200 ) {
    return false;
  }

  $upload = wp_upload_bits( basename($url), null, $response['body'] );
  if( !empty( $upload['error'] ) ) {
    return false;
  }

  $file_path = $upload['file'];
  $file_name = basename( $file_path );
  $file_type = wp_check_filetype( $file_name, null );
  $attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
  $wp_upload_dir = wp_upload_dir();

  $post_info = array(
    'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
    'post_mime_type' => $file_type['type'],
    'post_title'     => $attachment_title,
    'post_content'   => '',
    'post_status'    => 'inherit',
  );

  // Create the attachment
  $attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );

  // Include image.php
  require_once( ABSPATH . 'wp-admin/includes/image.php' );

  // Define attachment metadata
  $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );

  // Assign metadata to attachment
  wp_update_attachment_metadata( $attach_id,  $attach_data );

  return $attach_id;

}

function var_error_log( $object=null ){
    ob_start();                    // start buffer capture
    var_dump( $object );           // dump the values
    $contents = ob_get_contents(); // put the buffer into a variable
    ob_end_clean();                // end capture
    error_log( $contents );        // log contents of the result of var_dump( $object )
}
 

/**
* Downloads an image from the specified URL and attaches it to a post as a post thumbnail.
*
* @param string $file    The URL of the image to download.
* @param int    $post_id The post ID the post thumbnail is to be associated with.
* @param string $desc    Optional. Description of the image.
* @return string|WP_Error Attachment ID, WP_Error object otherwise.
*/
function os_try_link_attachment( $file, $post_id, $desc = '' ){
  require_once(ABSPATH . 'wp-admin/includes/media.php');
  require_once(ABSPATH . 'wp-admin/includes/file.php');
  require_once(ABSPATH . 'wp-admin/includes/image.php');
    // Set variables for storage, fix file filename for query strings.
    preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
    if ( ! $matches ) {
         return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
    }

    $file_array = array();
    $file_array['name'] = basename( $matches[0] );

    // Download file to temp location.
    $file_array['tmp_name'] = download_url( $file );

    // If error storing temporarily, return the error.
    if ( is_wp_error( $file_array['tmp_name'] ) ) {
        return $file_array['tmp_name'];
    }


    // Do the validation and storage stuff.
    $id = media_handle_sideload( $file_array, $post_id, $desc );

    // If error storing permanently, unlink.
    if ( is_wp_error( $id ) ) {
        @unlink( $file_array['tmp_name'] );
        return $id;
    }
    return $id;

}