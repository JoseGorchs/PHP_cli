#!/usr/bin/env php
<?php
//this is for the http requests
//ini_set('user_agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');
ini_set('user_agent', 'Mozilla/5.0');

/*TEMPORARY FILES */
$ids_file='id.txt';
$template_metadata_file="_metadata.txt";
$sites_dir="./sites/";

/*PARAMETER PARSING VARIABLES*/
$shortops="d";
$longopts=array(
	"sk:",
	"sa:",
	"tk:",
	"ta:"
);
$options = getopt($shortops,$longopts);
$arguments_no=sizeof($options);
$help="CMScli.php --sk <source key> --sa <source admin> --tk <target key> --ta <target admin> -d ";


/*GENERAL USE VARIABLES*/
$source_url_template="https://<source-admin>-admin.3scale.net/admin/api/cms/templates.xml?provider_key=<source-key>&page=1&per_page=100";
$source_replace = array("<source-admin>", "<source-key>");
$target_url_template="https://<target-admin>-admin.3scale.net/admin/api/cms/templates.xml?provider_key=<target-key>&page=1&per_page=100";
$target_replace = array("<target-admin>", "<target-key>");
$source_key="";
$source_admin="";
$target_key="";
$target_admin="";


if ($arguments_no<2)
{
usage();
} 
else
{
	if ($arguments_no==2)
	{

	if ($options["sk"]!="") $source_key=$options["sk"];else usage();
	if ($options["sa"]!="") 
		{
			$source_admin=$options["sa"];$template_metadata_file=$sites_dir.$source_admin.$template_metadata_file;
			if (!file_exists($sites_dir)) 
				{
					mkdir ($sites_dir);
				} 
		}
		else usage();
	}
	
	if ($arguments_no==4)
	{
	if ($options["sk"]!="") $source_key=$options["sk"];else usage();
	if ($options["sa"]!="") 
		{
			$source_admin=$options["sa"];$template_metadata_file=$sites_dir.$source_admin.$template_metadata_file;
			if (!file_exists($sites_dir)) 
				{
					mkdir ($sites_dir);
				} 
			
				if ($options["tk"]!="") $target_key=$options["tk"];else usage();
				if ($options["ta"]!="") $target_admin=$options["ta"];else usage();
				$target_data=array($target_admin,$target_key);
				$target_url=str_replace($target_replace,$target_data,$target_url_template);
			
		}
	}
	/*if ($options["d"]!="") $daemon=$options["d"];*/
	
	$source_data=array($source_admin,$source_key);
	$source_url=str_replace($source_replace,$source_data,$source_url_template);
}



/*SAMPLE $source_url = "https://jose-admin.3scale.net/ad<<<min/api/cms/templates.xml?provider_key=d3b483f08daa6d02ff9aabf05c9e9059&page=1&per_page=100";*/

fwrite(STDOUT,"\nDOWNLOADING THE SOURCE CMS:\n");
fwrite(STDOUT,"__________________________________________________________________________\n");
//generate_ids($source_url,true);
fwrite(STDOUT,"\nREPLICATING THE LOCAL REPOSITORY IN THE TARGET  CMS:\n");
fwrite(STDOUT,"__________________________________________________________________________\n");
replicate_site($template_metadata_file);
//create_template('page','PHP_created','PHP_created','/PHP_file.php','<html></html>','','php','PHP_FILE','unknown','','markdown');


function usage()
{
global $help;
echo $help."\n";
exit(0);
}

function generate_ids($url, $download)
{
global $template_metadata_file;
fwrite(STDOUT,"Calling this URL: ". $url."\n\n");

try 
	{
	$template= simplexml_load_file($url);
	}
catch (Exception $e)
	{
	fwrite(STDOUT,"The URL doesn't seem to be valid,\n");	
	fwrite(STDOUT,"error: ".$e->getMessage()."\n");
	fwrite(STDOUT,"Exiting the script.\n");
	}
$ids=array();
$i=0;
/*here we construct the <account-admin>-template_metadata_file
the format is:
<id>:<system_name>:<layout>:<liquid_enabled>:<handler>:<title>:<path>
*/
//lets first deal with layouts
foreach ($template->layout as $layout):
        $id=$layout->id;
    	$system_name=$layout->{'system_name'};
    	if (strpos($layout,'<liquid_enabled/>') == TRUE) { $liquid_enabled=FALSE;} else {$liquid_enabled=$layout->{'liquid_enabled'};};
    	if (strpos($layout,'<handler/>') == TRUE) { $handler="";} else {$handler=$layout->{'handler'};};
    	if (strpos($layout,'<title/>') == TRUE) { $title="";} else {$title=$layout->{'title'};};
    	if (strpos($layout,'<path/>') == TRUE) { $path="";} else {$path=$layout->{'path'};};
    	$section_name="";
    	$section_id="";
    	$layout_name="";
    	$layout_id="";
    	$type="layout";
    	$ids[$i++]=$id.":".$type.":".$system_name.":".$title.":".$path.":".$section_name.":".$section_id.":".$layout_name.":".$layout_id.":".$liquid_enabled.":".$handler;
endforeach;
//now with builtin pages
foreach ($template->builtin_page as $builtin_page):
        $id=$builtin_page->id;
    	$system_name=$builtin_page->{'system_name'};
    	if (strpos($builtin_page,'<liquid_enabled/>') == TRUE) { $liquid_enabled=FALSE;} else {$liquid_enabled=$builtin_page->{'liquid_enabled'};};
    	if (strpos($builtin_page,'<handler/>') == TRUE) { $handler="";} else {$handler=$builtin_page->{'handler'};};
    	if (strpos($builtin_page,'<title/>') == TRUE) { $title="";} else {$title=$builtin_page->{'title'};};
    	if (strpos($builtin_page,'<path/>') == TRUE) { $path="";} else {$path=$builtin_page->{'path'};};
    	$section_name="";
    	$section_id="";
    	$layout_name="";
    	$layout_id="";
    	$type="";
    	$ids[$i++]=$id.":".$type.":".$system_name.":".$title.":".$path.":".$section_name.":".$section_id.":".$layout_name.":".$layout_id.":".$liquid_enabled.":".$handler;
endforeach;
//partials
foreach ($template->partial as $partial):
        $id=$partial->id;
    	$system_name=$partial->{'system_name'};
    	if (strpos($partial,'<liquid_enabled/>') == TRUE) { $liquid_enabled=FALSE;} else {$liquid_enabled=$partial->{'liquid_enabled'};};
    	if (strpos($partial,'<handler/>') == TRUE) { $handler="";} else {$handler=$partial->{'handler'};};
    	if (strpos($partial,'<title/>') == TRUE) { $title="";} else {$title=$partial->{'title'};};
    	if (strpos($partial,'<path/>') == TRUE) { $path="";} else {$path=$partial->{'path'};};
    	$section_name="";
    	$section_id="";
    	$layout_name="";
    	$layout_id="";
    	$type="partial";
    	$ids[$i++]=$id.":".$type.":".$system_name.":".$title.":".$path.":".$section_name.":".$section_id.":".$layout_name.":".$layout_id.":".$liquid_enabled.":".$handler;
endforeach;
//and pages
foreach ($template->page as $page):
        $id=$page->id;
    	$system_name=$page->{'system_name'};
    	if (strpos($page,'<liquid_enabled/>') == TRUE) { $liquid_enabled=FALSE;} else {$liquid_enabled=$page->{'liquid_enabled'};};
    	if (strpos($page,'<handler/>') == TRUE) { $handler="";} else {$handler=$page->{'handler'};};
    	if (strpos($page,'<title/>') == TRUE) { $title="";} else {$title=$page->{'title'};};
    	if (strpos($page,'<path/>') == TRUE) { $path="";} else {$path=$page->{'path'};};
    	if (strpos($page,'<layout/>') == TRUE) { $layout_name="";} else {$layout_name=$page->{'layout'};};
    	$section_name="";
    	$section_id="";
    	$layout_id="";
    	$type="page";
    	$ids[$i++]=$id.":".$type.":".$system_name.":".$title.":".$path.":".$section_name.":".$section_id.":".$layout_name.":".$layout_id.":".$liquid_enabled.":".$handler;
endforeach;
$ids=array_values($ids);
if (!file_exists($template_metadata_file)) {touch($template_metadata_file);}
foreach ($ids as $key=>$val) 
	{
	$output = $val."\n";
	file_put_contents($template_metadata_file, $output,FILE_APPEND);
	//if the $download flag is set to true then we will download the contnat as well. This will not be necessary for the target account, 
	//so it should be only be set to true to the source account
	if ($download) retrieve_template($val);
	}

}








function retrieve_template($id_name)
{

global $source_key, $source_admin,$sites_dir;

fwrite(STDOUT, "Downloading template ".$id_name."\n");

$this_site_dir=$sites_dir.$source_admin."/";if (!is_dir($this_site_dir)) mkdir($this_site_dir,0777,true);
$id=explode(":",$id_name)[0];
$type=explode(":",$id_name)[1];
if (($type=="page")||($type=="layout")||($type=="partial"))
{
	$name=explode(":",$id_name)[3];
}
else
{
	$name=explode(":",$id_name)[2];
}
$template_API_call="https://".$source_admin."-admin.3scale.net/admin/api/cms/templates/".$id.".xml?provider_key=".$source_key;
$template = file_get_contents($template_API_call);
$path=explode("/",$name);
$k=count($path);
if ($k==1) {touch ($this_site_dir.$name);}
if ($k==2) {if (!file_exists($this_site_dir.$path[0])) {mkdir($this_site_dir.$path[0]);}touch ($this_site_dir.$path[0]."/".$path[1]);}
if ($k==3) {if (!file_exists($this_site_dir.$path[0]."/".$path[1])) {mkdir($this_site_dir.$path[0]."/".$path[1],0777,true);}touch ($this_site_dir.$path[0]."/".$path[1]."/".$path[2]);}
//now we will save the files and their metadata
if ($path[0]!="") file_put_contents($this_site_dir.$name, $template);
$template=simplexml_load_string($template);
/*$comment="path=".$name.",liquid_enabled=true,system_name=".$name.",type=layout";
shell_exec("setfattr -n user.comment -v ".$comment." ".$this_site_dir.$name);*/
}


//_______________________________________________________________________________________________________________________
function replicate_site($template_descriptions)
{
	global $sites_dir,$source_admin,$target_url,$target_admin,$template_metadata_file;

//we first extract all the metadata of the target account, as we will need it to find which files already exists
//and therefore should be updated but not created and which do not. These last will have to be created and populated
$template_metadata_file=$sites_dir.$target_admin."_metadata.txt";
generate_ids($target_url,false);
/*
we will be calling create_template for each of the files passed on the file_description argument. That argument should be a file containing one entry for each file that has to be replicated
the structire of each entry is the following:

$id:$type:$system_name:$title:$path:$draft:$section_name:$section_id:$layout_name:$layout_id:$liquid_enabled:$handler;

In this script, that file is created in the generate_ids function. So, basically what we need to do here is parse each line of that file and replicate the file described in there
*/
if (!file_exists($template_descriptions))
{
	fwrite(STDOUT, "ERROR: the file to read the templates description (\"".$template_descriptions."\") does not seem to exist.");
	exit (1);
}

$templates = file($template_descriptions, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
for ($i=0;$i<count($templates);$i++)
{
	$id=preg_split("/[:]/",$templates[$i])[0];
	$type=preg_split("/[:]/",$templates[$i])[1];
	$system_name=preg_split("/[:]/",$templates[$i])[2];
	$title=preg_split("/[:]/",$templates[$i])[3];
	$path=preg_split("/[:]/",$templates[$i])[4];
	$section_name=preg_split("/[:]/",$templates[$i])[5];
	$section_id=preg_split("/[:]/",$templates[$i])[6];
	$layout_name=preg_split("/[:]/",$templates[$i])[7];
	$layout_id=preg_split("/[:]/",$templates[$i])[8];
	$liquid_enabled=preg_split("/[:]/",$templates[$i])[9];
	$handler=preg_split("/[:]/",$templates[$i])[10];
	fwrite(STDOUT,"id=".$id.", type=".$type.", system_name=".$system_name.", title=".$title.", path=".$path."\n");
	//according to the API documentation, all the content  of the file to be created shoudl be on the "draft" parameter, so we will extract it from the "published"
	//section of the source files
	//if $draft is empty that means that we hsould use all the content of the file in the new destination



	if (($type=="layout")||($type=="page")||($type=="partial"))
	{
	$content=file_get_contents($sites_dir.$source_admin."/".$title);
	}
	else
	{
	$content=file_get_contents($sites_dir.$source_admin."/".$system_name);	
	}

	if (!strpos($content,'<published>') == FALSE)	
		{
			if (($type=="layout")||($type=="page")||($type=="partial"))
			{
			$content=simplexml_load_file($sites_dir.$source_admin."/".$title);
			}
			else
			{
			$content=simplexml_load_file($sites_dir.$source_admin."/".$system_name);	
			}
			$draft=$content->{'published'};
		}
	else 
		{
			fwrite(STDOUT,$content);
			$draft=$content;
		}
	
	
	/*in order to check if the template exists on the target account, we can check the file <destination_account>_metadata.txt that was created at the beginning of this function
	if there is a file with the same system_name or path, then we should skip its creation and do the update instead
	we can use the template_exists function to check if a template is already there
	*/

	$exists=template_exists ($templates[$i],$template_metadata_file);
	if ($exists==0)
		{
			//fwrite(STDOUT,"\tCreating template (".$templates[$i].")\n");
			create_template($type,$system_name,$title,$path,$section_name,$section_id,$layout_name,$layout_id,$liquid_enabled,$handler,$draft);
			//fwrite(STDOUT,"\tCREATING>".$type.", ".$system_name.", ".$title.", ".$path.", ".$section_name.", ".$section_id.", ".$layout_name.", ".$layout_id.", ".$liquid_enabled.", ".$handler."\n\n");
		}
		else
		{
			//fwrite(STDOUT,"\tUpdating template (".$templates[$i]."), matching id=".$exists."\n");
			//update_template($exists,$type,$system_name,$title,$path,$section_name,$section_id,$layout_name,$layout_id,$liquid_enabled,$handler,$draft);
			//fwrite(STDOUT,"\tUPDATING>".$type.", ".$system_name.", ".$title.", ".$path.", ".$section_name.", ".$section_id.", ".$layout_name.", ".$layout_id.", ".$liquid_enabled.", ".$handler."\n\n");
		}
	
}

}

function template_exists($template, $metadata_file)
{
	/*
	we use a file -metadata_file- that shoudl contain all the metadata of the CMS athat we want to check if the template already exists.
	this way we avoid making overloading Multitenant with calls every time 
	also "template" should be an array with a template description with the following fields
	
	id, type, system_name, title, path, draft, section_name, section_id, layout_name, layout_id, liquid_enabled, handler
	
	this script will check if there is already an entry with the same path or system_name
	if there is, it will return the id of the matching template (meaning that the template already exists) otherwise it will return false meaning that the template 
	does not exist on the list of templates provided

	*/
$source_system_name=preg_split("/[:]/",$template)[2];// as specified, we assume that "template[2]" will contain the system_name
$source_path=preg_split("/[:]/",$template)[4];// as specified, we assume that "template[2]" will contain the path
$templates = fopen($metadata_file, "r");
$exists=0;
while (!feof($templates)) 
	{
		$exists_path=0;$exists_systemname=0;
		$c = fgetc($templates); if($c === false) break;//because of how feof work, if we dont do this, we will get an eeror always at the end of the file
		$line = fgets($templates);$line=$c.$line;
		$exists_path=1;$exists_systemname=1;
		$target_system_name=preg_split("/[:]/",$line)[2];// as specified, we assume that "template[2]" will contain the system_name  on the target template description file
		$target_path=preg_split("/[:]/",$line)[4];// as specified, we assume that "template[2]" will contain the path on the target template description file
		
		if (($source_system_name!="")&&($target_system_name!="")) $exists_systemname = strcmp($source_system_name, $target_system_name); //we search for a match on the system_name
		if (($source_path!="")&&($target_path!="")) $exists_path = strcmp($source_path, $target_path);//we search for a match on the path
		if (($exists_path==0)||($exists_systemname==0)) 
								{
								//fwrite(STDOUT, " target template: p=".$target_path." (".$exists_path."), sn=".$target_system_name."(".$exists_systemname.")\n");
								$exists=explode(":",$line)[0];//if one of the two system_name/path is 0 , there is a mtach on the target account and there fore we retrun the target template id
								}
	}
fclose($templates);
return $exists;
}




function create_template($type,$system_name,$title,$path,$section_name,$section_id,$layout_name,$layout_id,$liquid_enabled,$handler,$draft)
{

/* 
This function creates a file. If the path is already take, that means that the file already exists and it shoudl not be created, but updated
which is a different function
If the file exists, this function will fail with an error

BUGS: si la seccion on existe hay que crearla primero. mirar en multitenant como hacerlo:
http://<admin>-admin.3scale.net/admin/api/cms/sections.xml -d 
	provider_key
	title
	public
	parent_id
	partial_path

SAMPLE CALL TO CREATE A FILE: curl -v  -X POST "https://jose006-admin.3scale.net/admin/api/cms/templates.xml" -d 'provider_key=962f405ac06b3ecf42623edce6cb1a92&type=page&system_name=PHP&title=PHP&path=/php&draft=%3Chtml%3E%3C%2Fhtml%3E&section_id=php&layout_name=php&liquid_enabled=on&handler=markdown'
We do not need any keys to be passed as parameters as they will be extrated from the arguments passed to the script
*/


global $source_key, $source_admin, $target_key, $target_admin;

fwrite(STDOUT, "\n\tCreating Template: ");
$template_API_call="https://".$target_admin."-admin.3scale.net/admin/api/cms/templates.xml";


$data = 'provider_key='.$target_key.
	'&type='.$type.//"page/layout/partial" 
	'&system_name='.$system_name.
	'&title='.$title.//Title of the template. This is what user can see on the directory hierarchy bar on the left side of the screen
	'&draft='.$draft. //Text content of the template (you have to publish the template)
	'&section_name='.$section_name.//system name of a section
	'&section_id='.$section_id. //valid only for pages
	'&layout_name='.$layout_name.//valid only for pages
	'&layout_id='.$layout_id.//overrides layout_name
	'&liquid_enabled='.$liquid_enabled//"on/off"
	;
//fwrite(STDOUT,"\tCREATING> 1.".$type.", 2.".$system_name.", 3.".$title.", 4.".$path.", 5.".$section_name.", 6.".$section_id.", 7.".$layout_name.", 8.".$layout_id.", 9.".$liquid_enabled.", ".$handler."\n\n");
if ($path=="") {$data=$data."&path=/";}else {$data=$data."&path=".$path;};
if ($handler=="") {$data=$data."&handler=markdown";};
if ($liquid_enabled="true") {$data=$data."&liquid_enabled=on";}else {$data=$data."&liquid_enabled=off";};
                                                                                  
                                                                                                                     
$ch = curl_init($template_API_call);   
curl_setopt($ch, CURLOPT_POST, 1);                                                                                                                             
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Content-Length:'.strlen($data)));      
                                                                                                          
$result = curl_exec($ch);

return true;
}






//_______________________________________________________________________________________________________________________________________________
function create_template_bck($type,$system_name,$title,$path,$section_name,$section_id,$layout_name,$layout_id,$liquid_enabled,$handler,$draft)
{
global $source_key, $source_admin, $target_key, $target_admin;

fwrite(STDOUT, "\nCreating Template: ");
$template_API_call="https://".$target_admin."-admin.3scale.net/admin/api/cms/templates.xml";
$sPD = array(
	'provider_key'=> urlencode($target_key),
	'type' => urlencode($type),//"page/layout/partial" 
	'system_name' => urlencode($system_name),
	'title' => urlencode($title),//Title of the template. This is what user can see on the directory hierarchy bar on the left side of the screen
	'path' => urlencode($path),//URI of the page
	'draft' => urlencode($draft), //Text content of the template (you have to publish the template)
	'section_name' => urlencode($section_name),//system name of a section
	'section_id' => urlencode($section_id), //valid only for pages
	'layout_name' => urlencode($layout_name),//valid only for pages
	'layout_id' => urlencode($layout_id),//overrides layout_name
	'liquid_enabled' => urlencode($liquid_enabled),//"on/off"
	'handler' => urlencode($handler) //"textile/markdown"
	);
//now lets deal with a few exceptions
if ($path=="") {$sPD['path']="/";};
if ($handler=="") {$sPD['handler']="markdown";};
if ($liquid_enabled="true") {$sPD['liquid_enabled']="on";}else {$sPD['liquid_enabled']="off";};


//fwrite(STDOUT,$template_API_call.http_build_query($sPD)."\n\n");

$aHTTP = array(
  'http' =>  array(
    'method'  => 'POST', // Request Method
    'header'  => 'Content-type: application/x-www-form-urlencoded',
    'content' => http_build_query($sPD)
  	)
);
$context = stream_context_create($aHTTP);
fwrite(STDOUT,"\tCREATING>".$title );
var_dump($sPD);
fwrite(STDOUT,"\n\n" );
$contents = file_get_contents($template_API_call, false, $context);
}


//____________________________________________________________________________________________________________________________________________
function update_template($id,$type,$system_name,$title,$path,$section_name,$section_id,$layout_name,$layout_id,$liquid_enabled,$handler,$draft)
{
/* 
This function updates a template. 
*/
global $source_key, $source_admin, $target_key, $target_admin;
//fwrite(STDOUT, "\tDraft : ".$draft."\n\n\n");
$template_API_call="https://".$target_admin."-admin.3scale.net/admin/api/cms/templates/".$id.".xml";
$data= array(
	'provider_key'=> $target_key,
	'draft' => utf8_encode($draft) //Text content of the template (you have to publish the template)
	);

$ch = curl_init($template_API_call);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
$response = curl_exec($ch);
if(!$response) 
	{
        return false;
    }
}


?>