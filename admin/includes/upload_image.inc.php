<?php 
require ('../../paths.php');
require_once (CONFIG.'config.php'); 
require (INCLUDES.'authentication.inc.php'); session_start(); sessionAuthenticate(); 
include (INCLUDES.'db_connect_universal.inc.php');
include (INCLUDES.'db_connect_admin.inc.php');
include (INCLUDES.'db_connect_log.inc.php');
$imageSrc = "../../images/";
$action = "default";
if (isset($_GET['action'])) {
  $action = (get_magic_quotes_gpc()) ? $_GET['action'] : addslashes($_GET['action']);
}

$section = "default";
if (isset($_GET['section'])) {
  $section = (get_magic_quotes_gpc()) ? $_GET['section'] : addslashes($_GET['section']);
}

$fileCornfirm = "default";
if (isset($_GET['fileConfirm'])) {
  $fileConfirm = (get_magic_quotes_gpc()) ? $_GET['fileConfirm'] : addslashes($_GET['fileConfirm']);
}

//Mmaximum file size.
$MAX_SIZE = 2000000;
                            
//Allowable file Mime Types.
$FILE_MIMES = array('image/jpeg','image/jpg','image/gif','image/png');

//Allowable file ext. names.         
$FILE_EXTS  = array('.jpg','.png','.gif');                                

$site_name = $_SERVER['HTTP_HOST'];
$url_dir = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
$url_this =  "http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

$upload_dir = (LABEL_IMAGES);
//echo $upload_dir;
$upload_url = $url_dir."/label_images/";
$message ="";

// Create Upload Directory
 if (!is_dir($upload_dir)) {
  if (!mkdir($upload_dir))
  	die ("Upload_files directory doesn't exist and creation failed.");
  if (!chmod($upload_dir,0755))
  	die ("Change permission to 755 failed.  You will probably need to change the folder's permission manually.  Consult your FTP program or ISP's documentation for chmod (folder permissions).");
}

// Process User's Request
if ($_REQUEST[del] && $DELETABLE)  {
  $resource = fopen("log.txt","a");
  fwrite($resource,date("Ymd h:i:s")."DELETE - $_SERVER[REMOTE_ADDR]"."$_REQUEST[del]\n");
  fclose($resource);
  
  if (strpos($_REQUEST[del],"/.")>0);                      // possible hacking
  else if (strpos($_REQUEST[del],$upload_dir) === false);  // possible hacking
  else if (substr($_REQUEST[del],0,6)==$upload_dir) {
    unlink($_REQUEST[del]);
    print "<script>window.location.href='?action=upload&section=".$section."&message=". htmlentities($message) . "&inserted=true'</script>";
  }
}
else if ($_FILES['userfile']) {
	// Uncomment if you want a log file.
  	//$resource = fopen("log.txt","a");
  	//fwrite($resource,date("Ymd h:i:s")."UPLOAD - $_SERVER[REMOTE_ADDR]"
            //.$_FILES['userfile']['name']." "
            //.$_FILES['userfile']['type']."\n");
 	// fclose($resource);

  	$file_type = $_FILES['userfile']['type']; 
  	$file_name = $_FILES['userfile']['name'];
  	$file_ext = strtolower(substr($file_name,strrpos($file_name,".")));

  //File Size Check
  if ($_FILES['userfile']['size'] > $MAX_SIZE) 
    $message = "The file size is over 2MB.  Please adjust the size and try again.";
  //File Type/Extension Check
  else if (!in_array($file_type, $FILE_MIMES) && !in_array($file_ext, $FILE_EXTS))
    $message = "Sorry, that file type is not allowed to be uploaded.  Acceptable file type extensions are: .jpg, .png, and .gif";
  else
    $message = do_upload($upload_dir, $upload_url);
  
  print "<script>window.location.href='?action=upload&message=$message'</script>";
}
else if (!$_FILES['userfile']);
else 
	$message = "Invalid file specified.";

// List Files in the directory
$handle=opendir($upload_dir);
$filelist = "";
while ($file = readdir($handle)) {
   if(!is_dir($file) && !is_link($file)) {
      	$filelist .= "<tr>";
		$filelist .= "<td width=\"5%\" nowrap><a href=\"../../label_images/$file\"  class=\"thickbox\">".$file."</a></td>";
      	$filelist .= "<td width=\"5%\" nowrap class=\"data\">".date("l, F j, Y H:i", filemtime($upload_dir.$file))."</td>";
	    if ($row_user['userLevel'] == "1") $filelist .= "<td class=\"data\"><a href =\"?action=upload&section=confirm&fileConfirm=".$file."\"><img src=\"".$imageSrc."bin_closed.png\" border=\"0\"></a></td>";
		else $filelist .="<td>&nbsp;</td>";
		$filelist .= "</tr>";
   }
}

function do_upload($upload_dir, $upload_url) {

	$temp_name = $_FILES['userfile']['tmp_name'];
	$file_name = $_FILES['userfile']['name']; 
  	$file_name = str_replace("\\","",$file_name);
  	$file_name = str_replace("'","",$file_name);
	$file_path = $upload_dir.$file_name;

// File Name Check
  if ($file_name == "") { 
  	$message = "Invalid file name specified";
  	return $message;
  }

  $result  =  move_uploaded_file($temp_name, $file_path);
  if (!chmod($file_path,0777))
   	$message = "Change permission to 777 failed.";
  else
    $message = ($result)?"The label image $file_name was uploaded successfully." : "An error has occurred, please try again.";
  return $message;
}
?>
<html>
<head>
<title>BrewBlogger - Upload Image</title>
<link href="../../css/html_elements.css" rel="stylesheet" type="text/css" />
<link href="../../css/universal_elements.css" rel="stylesheet" type="text/css" />
<link href="../../css/reference.css" rel="stylesheet" type="text/css" />
<link href="../../css/thickbox.css" rel="stylesheet" type="text/css" media="screen" />
<script type="text/javascript" src="../../js_includes/jquery.js"></script>
<script type="text/javascript" src="../../js_includes/thickbox.js"></script>
</head>
<body>
<div id="wideWrapperReference">
<div id="referenceHeader">Upload Images</div>
<?php if ($section == "default") { ?>
<form name="upload" id="upload" ENCTYPE="multipart/form-data" method="post">
<table class="dataTable">
<tr>
	<td class="dataLabelLeft">Image File:</td>
	<td class="data" width="10%"><input name="userfile" type="file" class="submit" id="userfile" size="60"></td>
    <td class="data"><input type="submit" value="Upload"></td>
</tr>
<tr>
	<td class="error" colspan="3"><br><?=$_REQUEST[message]?><br></td>
</tr>
</table>
</form>
<div id="referenceHeader">Files in the Directory</div>
<table class="dataTable">
<?php echo $filelist; ?>
</tr>
</table>
<?php } 
if ($section == "confirm") { ?>
<div class="headerContentAdmin">Delete Image</div>
<table class="dataTable">
  <tr>
	<td width="5%" rowspan="2" nowrap><a href="../../label_images/<?php echo $fileConfirm; ?>" class="thickbox"><img src="../../label_images/<?php echo $fileConfirm; ?>" width = "100" border="0"></a></td>
	<td width="5%"  nowrap class="data">Are you sure you want to delete <?php echo $fileConfirm; ?>?</td>
   	<td width="5%" nowrap class="data"><a href="?action=upload&section=delete&fileConfirm=<?php echo $fileConfirm; ?>">Yes</a></td>
    <td class="data"><a href="?action=upload">No</a></td>
</tr>
  <tr>
    <td width="5%" nowrap>&nbsp;</td>
    <td width="5%" nowrap class="data">&nbsp;</td>
    <td class="data">&nbsp;</td>
  </tr>
</table>
<?php } ?>
<?php if ($section == "delete") {
unlink($upload_dir.$fileConfirm);
?>
<div class="headerContentAdmin">Delete Image</div>
<table class="dataTable">
<tr>
	<td class="error"><?php echo $fileConfirm; ?> deleted.</td>
    <td class="data"><a href="?action=upload">Back to List</a></td>
</td>
</table>
<?php } ?>
<?php include (INCLUDES.'footer2.inc.php'); ?>
</div>
</body>
</html>