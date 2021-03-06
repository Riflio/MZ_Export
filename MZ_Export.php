<?php
/*
Plugin Name: MZ_Export
URI: http://pavelk.ru/portfolio/MZ_Export
Description: generate xml file for export Maginza lots
Version: 0.1
Author:PavelK
Author URI: http://pavelk.ru*/

class MZ_export {    

	public function __construct() {       
		add_action('init', array($this, 'init'), 1);
 		add_action('admin_menu', array($this, 'create_menu'));   
 		add_action('wp_ajax_wp_mz_export', array(&$this, 'ajax_exportaction'));
 	}    

 	function init() {
 		wp_register_script('MZExport', plugins_url('/MZ_Export.js', __FILE__ ), array('jquery'));
 
 	}

 	public function create_menu() {      
 		add_menu_page('MZ Export', 'MZ_export', 'administrator', __FILE__, array($this, 'settings_page'),plugins_url('/icon16.png', __FILE__));    
 	}    


 	public function settings_page() {    
 		wp_enqueue_script("MZExport");
 		echo '
			<div id="vkwp" class="wrap">
				'.screen_icon("options-general").'
				<h2>Maginza export </h2>	
				<div id="message" class="updated below-h2">
					<p>'.__('Welkom to export!').'</p>

				</div>
				<div>
					<a id="exportaction" class="button start" href="javascript:;">'.__('Start export').'</a>
					<div class="media-item">
						<div class="progress" style="float:left; width:100%">
							<div class="percent">0</div>
							<div class="bar" ></div>
						</div> 
					</div>
				</div>
				<div>

				</div>
			</div>
		';
 		

 	}


 	function ajax_exportaction() {
		global $Maginza;
 		$_export=urldecode($_GET['export']);		
		$export  = json_decode($_export);	
		
		$Meta=new Meta();

 		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><root></root>');        
 		$xMenu=$xml->addChild("menu");        
 		//--        
 		$updir=wp_upload_dir();
 		//--        
 		$zip = new ZipArchive();       
 		 
 		$filename = "mzexport-".date("Ymd").".zip";
 		$filepath= $updir['basedir']."/".$filename;
 		
 		if ($zip->open($filepath, ZIPARCHIVE::CREATE)!==TRUE) {            
 			die("Cannot create {$filepath} \n");        
 		}        
 		$menu_items = wp_get_nav_menu_items("mainMenu", array('output'=> OBJECT ) );       
 		$imgIndx=0;        
 		$keyIndx=0;        
 		foreach ($menu_items as $key => $menu_item ) {            
 			if ($menu_item->post_parent!=0) continue;            
 			//---            
 			$keyIndx++;            
 			$item=$xMenu->addChild("item");            
 			$item->addChild("x");            
 			$item->addChild("y");            
 			$item->addChild("w");            
 			$item->addChild("title", $menu_item->title);            
 			$item->addChild("gid", $keyIndx);            
 			//--            
 			$the_query = new WP_Query(array(                
 				"post_type"=>"lots",               
 				 'tax_query' => array(                    
 				 	array(                       
 				 	 'taxonomy' => 'types',                        
 				 	 'field' => 'id',                        
 				 	 'terms' => $menu_item->object_id,                    
 				 	)                
 				 )
 			));           
 			//--            
 			while ( $the_query->have_posts()) {                
 				$imgIndx++;	 				 
 				$the_query->next_post();
 				//--         
 				if (has_post_thumbnail($the_query->post->ID)) {      
	 				$pti=get_post_thumbnail_id($the_query->post->ID);                
	 				$md=wp_get_attachment_metadata($pti);      
	 				$imgName="img".$imgIndx.".jpg"; //TODO: Добавить определение формата/расширение файла картинки               
	 				$zip->addFile($updir['basedir']."/".$md["file"], $imgName);
 				}                
 				//--                
 				$lot=$xml->addChild('lot');                
 				$lot->addAttribute("gid", $keyIndx);                
 				$lot->addChild("id", $imgIndx);               
 				$title=$the_query->post->post_title;									
 				$descr=strip_tags(nl2br($the_query->post->post_content));     
 				 
 				$lot->addChild("description", $descr );				
 				$lot->addChild("title", $title);      
 				//--          
 				$lotMO=$Meta->getLotMetaOptions($the_query->post);
 				foreach($lotMO as $mo) { 					
 					$lot->addChild($mo->optName, $Meta->getMetaValue($the_query->post, $mo->optName) );	
 				}
 				$lot->addChild("images")->addChild("img", "".$imgName);            
 			}            
 			wp_reset_postdata(); 
 		}        
 		$zip->addFromString("data.xml",  $xml->asXML());        
 		$zip->close();
 		
 		$export->status="SUCCES";
 		$export->step=1;
 		$export->total=1;
 		$export->resultpath=$updir['baseurl']."/".$filename;
 		
 		echo json_encode($export);	
		die();		

 	}	

}
$mz_export= new MZ_export();

?>