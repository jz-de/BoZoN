<?php 
	/**
	* BoZoN list files script:
	* just list the files in the upload current path (with the filter if needed) 
	* @author: Bronco (bronco@warriordudimanche.net)
	**/

start_session();
$layout=$_SESSION['aspect'];
if (!function_exists('store')){
	include('core/core.php');
	if (!function_exists('newToken')){require_once('core/auto_restrict.php');} # Admin only!
}
include("core/auto_thumb.php");
$lb_token=returnToken();
echo str_replace('#TOKEN',$lb_token,$templates['link_lightbox']);
echo str_replace('#TOKEN',$lb_token,$templates['rename_lightbox']);
echo str_replace('#TOKEN',$lb_token,$templates['delete_lightbox']);
echo str_replace('#TOKEN',$lb_token,$templates['new_folder_lightbox']);
echo str_replace('#TOKEN',$lb_token,$templates['download_url_lightbox']);
echo str_replace('#TOKEN',$lb_token,$templates['qrcode_lightbox']);

// Configuration
$upload_path_size=strlen($_SESSION['upload_root_path'].$_SESSION['upload_user_path']);
if (empty($_SESSION['mode'])){$mode='view';}else{$mode=$_SESSION['mode'];}
if (empty($_SESSION['filter'])){$mask='*';}else{$mask='*'.$_SESSION['filter'].'*';}
if (empty($_SESSION['current_path'])){
	$liste=_glob($_SESSION['upload_root_path'].$_SESSION['upload_user_path'],$mask);
}else{
	$liste=_glob($_SESSION['upload_root_path'].$_SESSION['upload_user_path'].addslash_if_needed($_SESSION['current_path']),$mask);
}
if ($mode=='move'){
	# Prépare folder tree 
	$select_folder='<select name="destination" class="folder_list button"><option value="">'.e('Choose a folder',false).'</option>';
	$folders_list=tree($_SESSION['upload_root_path'].$_SESSION['upload_user_path'],false);
	$folders_list[0].='/';
	foreach($folders_list as $folder){
		$folder=substr($folder,$upload_path_size);
		$select_folder.='<option value="'.$folder.'">'.$folder.'</option>';
	}
	$select_folder.='</select>';
	# Add move dialogbox to the page
	$array=array(
			'#LIST_FILES_SELECT'	=> $select_folder,
			'#TOKEN'				=> returnToken(),
		);
	echo template('move_lightbox',$array);
}
if ($mode=='links'){
	# Add lock dialogbox to the page
	$array=array(
		'#TOKEN'	=> returnToken()
	);
	echo template('password_lightbox',$array);
}
$save=false;

if (count($liste)>0){
	$files=array_flip($ids);
	$folderlist='';
	$filelist='';
	foreach ($liste as $fichier){
		$nom=_basename($fichier);
		$length_upload_path=strlen($_SESSION['upload_root_path'].$_SESSION['upload_user_path']);
		$nom_racine=substr($fichier, $length_upload_path);
		if ($nom!='index.html'&&empty($files[$fichier])&&empty($files[$nom_racine])){
			// generates the file ID if not present
			$id=uniqid(true);
			$ids[$id]=$fichier;
			$files[$fichier]=$id;
			$save=true;

		}
		if ($nom!='index.html'){
			$taille=round(filesize($fichier)/1024,2);
			if (!empty($files[$fichier])){$id=$files[$fichier];}
			else{$id=$files[$nom_racine];}
			$class='';$title='';

			if (substr($id, 0,1)=='*'){
				# add class burn id after access 
				$class='burn';
				$title=e('The user can access this only one time', false);
			}else
			if (strlen($id)>strlen(uniqid(true))){
				# add class password protected 
				$class='locked';
				$title=e('The user can access this only with the password', false);
			}
			$extension=strtolower(pathinfo($fichier,PATHINFO_EXTENSION));
			if (visualizeIcon($extension)){
					$icone_visu='<a class="visu" href="index.php?f='.$id.'" target="_BLANK" title="'.e('View this file',false).'">&nbsp;</a>';
				}else{$icone_visu='';}
			$fichier_short=substr($fichier,$upload_path_size);
			if (is_dir($fichier)){
				# Item is a folder				
				$taille=count(_glob($fichier.'/'));
				$array=array(
					'#CLASS'			=> $class,
					'#ID'				=> $id,
					'#FICHIER'			=> $fichier_short,
					'#TOKEN'			=> returnToken(),
					'#SIZE'				=> $taille,
					'#NAME'				=> $nom,
					'#TITLE'			=> $title,
					'#SLASHEDNAME'		=> addslashes($nom),
					'#SLASHEDFICHIER'	=> addslashes($fichier),
				);
				$folderlist.= template($mode.'_folder_'.$layout,$array);
			}elseif ($extension=='gif'||$extension=='jpg'||$extension=='jpeg'||$extension=='png'){
				# Item is a picture
				auto_thumb($fichier,64,64);
				$array=array(
					'#CLASS'		=> $class,
					'#ID'			=> $id,
					'#FICHIER'		=> $fichier_short,
					'#TOKEN'		=> returnToken(),
					'#SIZE'			=> $taille,
					'#NAME'			=> $nom,
					'#TITLE'		=> $title,
					'#EXTENSION'	=> $extension,
					'#ICONE_VISU'	=> $icone_visu,
					'#SLASHEDNAME'	=> addslashes($nom),
					'#SLASHEDFICHIER'	=> addslashes($fichier_short),
				);
				$filelist.= template($mode.'_image_'.$layout,$array);
			}elseif ($extension=='zip'){
				# Item is a zip file=> add change to folder
				$icone_visu='<a class="tofolder" href="index.php?p=admin&unzip='.$id.'&token='.returnToken().'" title="'.e('Convert this zip file to folder',false).'">&nbsp;</a>';
				$array=array(
					'#CLASS'		=> $class,
					'#ID'			=> $id,
					'#FICHIER'		=> $fichier_short,
					'#TOKEN'		=> returnToken(),
					'#SIZE'			=> $taille,
					'#NAME'			=> $nom,
					'#TITLE'		=> $title,
					'#EXTENSION'	=> $extension,
					'#ICONE_VISU'	=> $icone_visu,
					'#SLASHEDNAME'	=> addslashes($nom),
					'#SLASHEDFICHIER'	=> addslashes($fichier_short),
				);
				$filelist.= template($mode.'_file_'.$layout,$array);
			}else {
				# all other types
				$array=array(
					'#CLASS'		=> $class,
					'#ID'			=> $id,
					'#FICHIER'		=> $fichier_short,
					'#TOKEN'		=> returnToken(),
					'#SIZE'			=> $taille,
					'#NAME'			=> $nom,
					'#TITLE'		=> $title,
					'#EXTENSION'	=> $extension,
					'#ICONE_VISU'	=> $icone_visu,
					'#SLASHEDNAME'	=> addslashes($nom),
					'#SLASHEDFICHIER'	=> addslashes($fichier_short),
				);
				$filelist.= template($mode.'_file_'.$layout,$array);
			}
		
		}
	}
	echo $folderlist.$filelist;
	if ($save){store($ids);} // save in case of new files
}else{e('No file on the server');}

?>
<script src="core/qr.js"></script>
<script>
	
	function put_file(fichier){
		document.getElementById('filename').value=fichier;
		document.getElementById('filename_hidden').value=fichier;
	}
	function put_id(id){document.getElementById('ID_hidden').value=id;}
	function put_link(id){document.getElementById('link').value="<?php echo $_SESSION['home'];?>?f="+id;}
	function put_file_and_id(id,file){
		document.getElementById('FILE_Rename').value=file;
		document.getElementById('ID_Rename').value=id;
	}
	function suppr(id){	document.getElementById('ID_Delete').value=id;}

// function inspired by Timo http://lehollandaisvolant.net/tout/tools/qrcode/
function qrcode(id) {
	var data = "<?php echo $_SESSION['home'];?>?f="+id;
	var options = {ecclevel:'M'};
	var url = QRCode.generatePNG(data, options);
	document.getElementById('qrcode_img').src = url;
	return false;
}


function downloadImage() {
	data = document.getElementById('outputimg').src;
	document.getElementById('outputlink').href = data;
}


</script>