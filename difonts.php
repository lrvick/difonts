<?php //{{{ diFonts v0.7 -- begin configuration
$font_file[h2]        =  'yourfont.ttf';               // Full path to font from $local_path
$font_size[h2]        =  '25';                         // In pixels
$font_color[h2]       =  'black';                      // Standard color names only atm ( or "transparent")
$shadow_color[h2]     =  'grey';                       // Standard color names only atm ( or "transparent")
$shadow_y[h2]         =  '-5';                         // Shadow offset from top
$shadow_x[h2]         =  '-5';                         // Shadow offset from left
$shadow_op[h2]        =  '80';                         // Shadow Opacity
$shadow_blur[h2]      =  '1';                          // Blur threshhold
$background_color[h2] =  'transparent';                // Standard color names only atm ( or "transparent")
$font_file[h3]        =  'anotherfont.ttf';            // Full path to font from $local_path
$font_size[h3]        =  '20';                         // In pixels
$font_color[h3]       =  'black';                      // Standard color names only atm ( or "transparent")
$shadow_color[h3]     =  'grey';                       // Standard color names only atm ( or "transparent")
$shadow_y[h3]         =  '-5';                         // Shadow offset from top
$shadow_x[h3]         =  '-5';                         // Shadow offset from left
$shadow_op[h3]        =  '80';                         // Shadow Opacity
$shadow_blur[h3]      =  '1';                          // Blur threshhold
$background_color[h3] =  'transparent';                // Standard color names only atm ( or "transparent")
$http_path            =  '/http/path/to/difonts.php';  // http path to difonts
$local_path           =  '/local/path/to/difonts.php'; // local path to difonts
$cache_images         =  'true';                       // Will save/cache generated images
$cache_folder         =  'cache';                      // Folder to save cache images in from here
$mime_type            =  'image/png';                  // Mime type of file to pretend to be
$extension            =  '.png';                       // File extension used (if any)
$send_buffer_size     =  '4096';                       // Buffer size for sending cached images to browser }}} - end configuration
function fatal_error($message){ // {{{  Generates an error message and passes it off to the browser as a GD image
  if(function_exists('ImageCreate')){
    $width = ImageFontWidth(5) * strlen($message) + 10 ;
    $height = ImageFontHeight(5) + 10 ;
    if($image = ImageCreate($width,$height)){
      $background = ImageColorAllocate($image,255,255,255) ;
      $text_color = ImageColorAllocate($image,0,0,0) ;
      ImageString($image,5,5,5,$message,$text_color) ;
      header('Content-type: image/png') ;
      ImagePNG($image) ;
      ImageDestroy($image) ;
      exit ;
    }
    header("HTTP/1.0 500 Internal Server Error") ;
    print($message) ;
    exit ;
  }
} // }}}
function cacheName($text,$type){ // {{{ Generates an MD5 filename to be used for imafge caching backend
  global $_SERVER, $cache_folder, $extension, $basepath;
  $hash = md5(basename($font_file) . $font_size[$type] . $font_color[$type] .
      $background_color[$type] . $transparent_background . $text) ;
  $cache_filename = $cache_folder . '/' . $hash . $extension ;
  return($cache_filename);
} // }}}
function transGif() { // {{{ The alpha transparency hacks for IE6 call for a 1x1px transparent gif... here it is ;-)
  header("Content-type: image/gif");
  echo base64_decode('R0lGODlhAQABAJEAAAAAAP///////wAAACH5BAEAAAIALAAAAAABAAEAAAICVAEAOw==');
  exit;
} // }}}
function genImage($font_file,$font_size,$font_color,$shadow_color,$shadow_y,$shadow_x,$shadow_op,$shadow_blur,$background_color) { // {{{ Generates the images
  global $cache_images, $cache_folder, $mime_type, $extension, $send_buffer_size, $font_file_real, $text, $type, $basepath;
  $font_file_real = $basepath."/".$font_file;
  if (!method_exists('Imagick', 'getVersion')) fatal_error('Error: Server does not have support for Imagick');
  if(empty($text)) fatal_error('Error: Filename or text was specified.');
  if(!file_exists($font_file)) fatal_error('Error: The server is missing the specified font.');
  $cache_filename = cacheName($text, $type);
  if (!file_exists($cache_filename) && isset($text)){
    $image = new Imagick();
    $draw  = new ImagickDraw();
    $pixel = new ImagickPixel( $background_color );
    $color = new ImagickPixel( $font_color );
    $image->newImage(800, 800, $pixel);
    $pixel->setColor($font_color);
    $draw->setFont($font_file_real);
    $draw->setFontSize( $font_size );
    $draw->setFillcolor( $color );
    $image->annotateImage($draw, 10, 45, 0, $text);
    $image->setImageFormat('png');
    $image_s = $image->clone();
    $image_s->setImageBackgroundColor( new ImagickPixel( $shadow_color ) );
    $image_s->shadowImage( $shadow_op, $shadow_blur, $shadow_x, $shadow_y );
    $image_s->compositeImage( $image, Imagick::COMPOSITE_OVER, 0, 0 );
    $image_s->trimimage(0);
    $image_s->writeImage( $cache_filename );
  }
  $last_modified = gmdate('D, d M Y H:i:s',filemtime($cache_filename)).' GMT';
  if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $if_modified_since = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($if_modified_since == $last_modified) {
      header("HTTP/1.0 304 Not Modified");
      header("Cache-Control: max-age=86400, must-revalidate");
      exit;
    }
  }
  if ($file = @fopen($cache_filename,'rb')){
    $content_length = filesize($cache_filename);
    header('Cache-Control: max-age=86400, must-revalidate');
    header('Content-Length: '.$content_length);
    header('Last-Modified: '.$last_modified);
    header('Content-type: ' . $mime_type);
    header('ETag: "diFonts-'.str_replace($extension, "", str_replace($cache_folder."/", "", $cache_filename)).'"');
    while(!feof($file))
      print(($buffer = fread($file,$send_buffer_size))) ;
    fclose($file) ;
    exit ;
  }
} // }}}
function diFonts($text,$type,$title=NULL,$class=NULL) { // {{{ generates all code needed to display your text (with fallback) in all browsers.
  global $cache_folder, $mime_type, $extension;
  $cache_filename = "images/" . cacheName($text,$type);
  $image = $http_path."/".$type."/".$text.$extension;
  if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/msie\s(5\.[5-9]|[6]\.[0-9]*).*(win)/i',$_SERVER['HTTP_USER_AGENT'])){
		echo "<img src=\"".$http_path."transparent.gif\" style=\"filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='".$image."', sizingMethod='scale');\" ";
  } else {
    echo "<img src=\"".$image."\" ";
  }
  if (file_exists($cache_filename)){
    list($width, $height, $type, $attr) = getimagesize($cache_filename);
    echo "width=\"".$width."\" height=\"".$height."\" ";
  }
  if (isset($class)){
    echo "class=\"".$class."\" ";
  }
  echo "alt=\"".$text."\" ";
  if (isset($title)){
    echo "title=\"".$title."\"";
  }
  echo " >";
} // }}}
$basepath = realpath(dirname($_SERVER['SCRIPT_FILENAME'])); // {{{ begin output
$extensions  = array (".jpg" , ".png" , ".gif");
if (preg_match("(.jpg|.png|.gif)", $_SERVER['PATH_INFO'])) {
  $args = explode('/',stripslashes($_SERVER['PATH_INFO']));
  $text = str_replace( $extensions, "" , $args[2]);
  $text = stripslashes($text) ;
  $type = $args[1];
  if ($type == "transparent.gif"){
    transGif();
  } else {
    genImage($font_file[$type],$font_size[$type],$font_color[$type],$shadow_color[$type],$shadow_y[$type],$shadow_x[$type],$shadow_op[$type],$shadow_blur[$type],$background_color[$type]);
  }
}
// }}} ?>
