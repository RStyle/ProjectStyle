<?php
	
class parser{
	
	static $settings;
	static $manialinks;
	
	static function loadSettings($settings = false)
	{
		if (!$settings){
			self::$settings = json_decode(file_get_contents('sys/parser.plugin.json'));
			self::$manialinks = json_decode(file_get_contents('sys/manialinks.json'));
		} else {
			self::$settings = $settings;
			self::$manialinks = json_decode(file_get_contents('sys/manialinks.json'));
		}
	}
	
	static function xml_frame($obj = array())
	{
		echo '<frame '.
		//(!empty($obj['pos']) ? 'pos="'.$obj['pos'].'" ' : '').
		(!empty($obj['posn']) ? 'posn="'.$obj['posn'].'" ' : '').
		(!empty($obj['scale']) ? 'scale="'.$obj['scale'].'" ' : '').
		'>';
		self::readability();
		pml($obj);
		echo '</frame>';
		self::readability();
	}
	
	static function quad_style($obj = array())
	{
		if (!empty($obj['style']) && !empty($obj['substyle']) && self::$settings->quadStyle) {
			
			$path = './img/'.INPUT.'styles/'.$obj["style"].'/'.$obj["substyle"].'/image.png';
			$path2 = './img/'.INPUT.'styles/'.$obj["style"].'/'.$obj["substyle"].'/imagefocus.png';
			
			if (file_exists($path)) {
				$obj['image'] = $path;
				if (file_exists($path2))
					$obj['imagefocus'] = $path2;
				$obj['style'] = '';
				$obj['substyle'] = '';
			}
		}
	}
	
	static function xml_convert($obj = array())
	{
		global $webOnly;	
		if ($obj['halign'] == 'left')
			$obj['halign'] = '';
		if ($obj['valign'] == 'top')
			$obj['valign'] = '';
		
		echo '<'.$obj['xmlName'].' ';
		foreach ($obj->attributes() as $key => $value) {
			if ($value != '' && !in_array($key, $webOnly) && !in_array($key, array('pos', 'size')))
				echo $key.'="'.$value.'" ';	
		}
		echo '/>';
	}
	
	static function xml_timeout($obj = array())
	{
		echo '<timeout>'.$obj[0].'</timeout>';
	}
	
	static function href($obj = array())
	{
		if (self::$settings->enableHref) {
			foreach ($obj->attributes() as $key => $value) {
				if (substr($value, 0, 2) == './') {
					$obj[$key] = str_replace('./', self::$settings->href, $value);
				}
			}
		}
	}
	
	//---------------WEB----------------
	
	static function web_css($obj = array())
	{
		addCss('.pa', 'position:absolute; font-size:16;');
		addCss('.la_hl_r', 'width:100%; position: absolute; text-align: right;');
		addCss('.la_hl_c', 'width:100%; position: absolute; text-align: center;');
		addCss('.la_vl_c', "-webkit-transform: translateY(-50%); /* child now centers itself relative to the  midline based on own contents */
		-moz-transform: translateY(-50%);
		-ms-transform: translateY(-50%);
		-ms-filter: 'progid:DXImageTransform.Microsoft.Matrix(M11=0.5, M12=0, M21=0, M22=0.5,  SizingMethod=\"auto expand\")'; /*IE8 */
		filter: progid:DXImageTransform.Microsoft.Matrix(M11=0.5, M12=0, M21=0, M22=0.5,  SizingMethod='auto expand'); /*IE6, IE7*/
		transform: translateY(-50%);");
	}
	
	static function web_frame($obj = array())
	{
		global $attr;
		if (empty($obj['scale']))
			$obj['scale'] = 1.0;
		$oldAttr = $attr;
		$attr = array('scale' => $attr['scale'] * floatval($obj['scale']), 'p0' => $obj['p0'] - 50, 'p1' => $obj['p1'] - 50, 'p2' => '0', 'posn' => $obj['posn']);
		pml($obj);
		$attr = $oldAttr;
	}
	
	static function web_quad($obj = array())
	{
		//echo (!empty($obj['url']) or !empty($obj['manialink']) ? '555'.(!empty($obj['url']) ? '<a href="'.$obj['manialink'].'">' : '<a href="'.MLLINK.$obj['manialink'].'">') : '1234');
		if (!empty($obj['url'])) {
			echo '<a href="'.$obj['url'].'">';
			self::readability();
		} elseif (!empty($obj['manialink'])) {
			echo '<a href="'.(strpos($obj['manialink'], '://') === false ? MLLINK : '').$obj['manialink'].'">';
			self::readability();
		}
		
		$bgcolor = false;
		$opacity = false;
		if (!empty($obj['bgcolor'])) {
			$bgcolor_spilt = str_split($obj['bgcolor']);//rgb(0, 0, 0)
			$bgcolor = ' rgb('. base_convert($bgcolor_spilt[0],16,10)*17 .','. base_convert($bgcolor_spilt[1],16,10)*17 .','.
			base_convert($bgcolor_spilt[2],16,10)*17 .')' ;
			if (!empty($bgcolor_spilt[3]) && $bgcolor_spilt[3] != 'F') {
				$opacity= substr(base_convert($bgcolor_spilt[3],16,10)/15 , 0, 5);
			}
		}
		
		if (strpos($obj['image'],'.webm') !== false) {
			echo '<video poster="star.png" autoplay loop controls width="'.$obj['s0'].'" height="'.$obj['s1'].'" style="left:'.($obj['p0']).';top:'.($obj['p1']).';z-index:'.($obj['p2'] * 10 ).';>
				<source src="'.$obj['image'].'" type=\'video/webm; codecs="vp8, vorbis"\' />
				</video>';
			
		} else {
		
			echo '<img alt="" '.
			(!empty($obj['image']) ? 'src="'.$obj['image'].'" ' : '').
			(!empty($obj['imagefocus']) ? 'onmouseover="this.src=\''.$obj['imagefocus'].'\';" onmouseout="this.src=\''.$obj['image'].'\';" ' : '').
			'width="'.$obj['s0'].'" height="'.$obj['s1'].'" style="left:'.($obj['p0']).';top:'.($obj['p1']).';z-index:'.$obj['p2'].';'.
			($bgcolor !== false ? 'background:'.$bgcolor.';' : '').
			($opacity !== false ? 'opacity:'.$opacity.';' : '').'">';
		
		}

		if (!empty($obj['url']) or !empty($obj['manialink'])) {
			self::readability();
			echo '</a>';
		}
	}
	
	static function web_label($obj)
	{
		global $colorPraser;
		
		if (empty($obj['text']) && empty($obj[0]))
			return;
		
		if (empty($obj['text']) && !empty($obj[0]))
			$obj['text']=$obj[0];
			
		if ($obj['textsize'] == false)
			$obj['textsize'] = 1;
			
		//if (!empty($obj['url']) && substr($obj['image'],0,4) != 'http')
			//$obj['url']=$_SERVER['SCRIPT_NAME'].'?load_file='.str_replace('./',$path,$obj['url']);
		//if (!empty($obj['manialink']) && substr($obj['manialink'],0,4) == 'http')
		//	$obj['manialink']=$_SERVER['SCRIPT_NAME'].'?load_file='.str_replace('&','%26',$obj['manialink']);	
		
		$textsize=((float)$obj['textsize'] + 2) * 0.33 * 16;
		//$textsize = ($obj['textsize'] / 3 + 0.66) * 16;
			
		/*echo '<div width="'.$obj['s0'].'" height="'.$obj['s1'].'" style="position:absolute;left:'.($obj['p0']).';top:'.($obj['p1']).';z-index:'.$obj['p2'].';
		overflow:hidden;font-size:'.($textsize).'px;" ><span style="'.
		(!empty($obj['textcolor']) ? 'color:#'.$obj['textcolor'].';' : '').
		(!empty($obj['halign']) ? 'text-align:'.$obj['halign'].';' : '' ).
		'">';*/
		
		echo '<div class="pa" style="left:'.($obj['p0']).';top:'.($obj['p1']).';z-index:'.$obj['p2'].';
		 width:'.$obj['s0'].'; height:'.$obj['s1'].';'. ($textsize != 15.84 ? 'font-size:'.$textsize : '').'">';
		
		
		if (!empty($obj['halign']) or !empty($obj['valign']))
			echo '<div class="';
			
		if ($obj['halign'] == 'right')
			echo 'la_hl_r ';
		
		if ($obj['halign'] == 'center')
			echo 'la_hl_c ';
			
		if ($obj['valign'] == 'center')
			echo 'la_vl_c';
		
		echo '" style="';
	
		if(!empty($obj['textcolor'])) echo 'color: #'.$obj['textcolor'].';';
	
		if (!empty($obj['halign']) or !empty($obj['valign']))
		echo '">';
		
		//<span style="position:absolute; left:964px; top:248px; z-index:1; width:27px; height:22px; font-size:18px; overflow:hidden;">
		if (!empty($obj["manialink"]) or !empty($obj["url"]) && $obj['send'] != 1) {
			echo '<a ';
			if (!empty($obj["url"])) {
				echo 'href="'.$obj["url"].'"';
			} elseif (!empty($obj['manialink'])) {
			echo 'href="'.(strpos($obj['manialink'], '://') === false ? MLLINK : '').$obj['manialink'].'';
			self::readability();
			}
			echo '">';
		}
		

		
		echo ''.$colorPraser->toHTML(str_ireplace('$o','',$obj['text'])).'';
		//echo $colorPraser->toHTML($obj['text']);
		
		if ($obj["manialink"] != false or $obj["url"] != false )
			if ($obj['send']!=1)echo '</a>';
		
		if (!empty($obj['halign']) or !empty($obj['valign']))
			echo '</div>';
			
		echo '</div>';
	}
	
	static $music;
	
	static function web_music($obj = array())
	{
		if (!empty($obj['data']) && self::$music != 1) {
			self::$music = 1;
			echo '<audio class="backgroundMusic pa" src="'.$obj['data'].'" volume="0.2" style="'.self::$settings->music->position[0].
			':0px;'.self::$settings->music->position[1].':0px" loop'.(self::$settings->music->controls ? ' controls' : '').'></audio>';
			addJs('$(".backgroundMusic").prop("volume", '.self::$settings->music->volume.');');
		}
	}

	static function web_unit($obj = array())
	{
		global $attr;
		$obj['s0'] = round((float)$obj['s0'] * self::$settings->unit->modification->x, 2) . self::$settings->unit->suffix;
		$obj['s1'] = round((float)$obj['s1'] * self::$settings->unit->modification->y, 2) . self::$settings->unit->suffix;
		$obj['p0'] = round(((float)$obj['p0'] + $attr['p0']) * self::$settings->unit->modification->x, 2) . self::$settings->unit->suffix;
		$obj['p1'] = round(((float)$obj['p1'] + $attr['p1']) * self::$settings->unit->modification->y, 2) . self::$settings->unit->suffix;
		$obj['p2'] = round(((float)$obj['p2'] + $attr['p2']) * self::$settings->unit->modification->z, 2) * 100;
	}
	
	static function web_ml2url($obj = array())
	{
		if(!empty($obj['manialink'])){
			$obj['url'] = '';
			$ml = strtolower(explode('?', $obj['manialink'])[0]);
			if(!empty(self::$manialinks->$ml) && !empty($ml)){
				$get = '';
				if(isset(explode('?', $obj['manialink'])[1]))
					$get = explode('?', $obj['manialink'])[1];
				if(self::$manialinks->$ml == self::$settings->href)
					$obj['url'] = '?web&amp;'.$get;
				else
					$obj['url'] = '?web&amp;origin='.self::$manialinks->$ml.'&amp;'.$get;
				$obj['manialink'] = '';
			}
		}
	}
	
	//----------WEB-END-------------
	
	static function readability($obj = array())
	{
			if (self::$settings->readability)
				echo "\n";
	}
	
	static function bikToWebm($obj = array())
	{
		if (OUTPUT == 'mp')
			$obj['image'] = str_replace('.bik', '.webm', $obj['image']);
		elseif (OUTPUT == 'web')
			$obj['image'] = str_replace('.bik', '.gif', $obj['image']);
	}
	
	static function enableImagefocus($obj = array())
	{
		if (!self::$settings->enableImagefocus)
				$obj['imagefocus'] = '';
	}
	
	static function urlControl($obj = array())
	{
		if (!empty($obj['url']) && substr($obj['url'], 0, 1) != '?' && strpos($obj['url'], 'http://') === FALSE && strpos($obj['url'], 'https://') === FALSE)
			$obj['url'] = 'http://'.$obj['url'];
	}

	static function label_fun($obj = array())
	{	//expects an label
			$obj['text']= '1';
	}

	static function xml_position($obj)
	{
		global $attr;
		//print_r($attr);
		
		if (INPUT == 'tm') {
			$max = array(64.0, 48.0);
			$max2 = array(128.0, 96.0);
			$n = 64.0;
			$outmax = array(160.0, 90.0);
			$outmax2 = array(320.0, 180.0);
		} else {
			$max = array(160.0, 90.0);
			$max2 = array(320.0, 180.0);
			$n = 100.0;
			$outmax = array(64.0, 48.0);
			$outmax2 = array(128.0, 96.0);
		}

		$obj->addAttribute('s0', 25.0 * $attr['scale']);	//size in %
		$obj->addAttribute('s1', 5.0 * $attr['scale']);

		if (!empty($obj['size']) && empty($obj['sizen'])) {
			$size = explode(' ', $obj['size']);
			$obj->addAttribute('sizen', (float)$size[0] * $n .' '. (float)$size[1] * $n);
		}

		if (!empty($obj['sizen'])) {
			$sizen = explode(' ', $obj['sizen']);
			$obj['s0'] = (float)$sizen[0] * 100 / $max2[0] * $attr['scale'];
			$obj['s1'] = (float)$sizen[1] * 100 / $max2[1] * $attr['scale'];
		}

		//posn

		$obj->addAttribute('p0', 50.0);	//position in %
		$obj->addAttribute('p1', 50.0);
		$obj->addAttribute('p2', 0.0);
		
		if (!empty($obj['pos']) or !empty($obj['posn'])) {

			if (!empty($obj['pos']) && empty($obj['posn'])) {
				$pos = explode(' ', $obj['pos']);
				$obj->addAttribute('posn', -1 * $pos[0] * $n .' '. $pos[1] * $n .' '. -1 * $pos[2] * $n);
			}
	
			
			$posn = explode(' ', $obj['posn']);
			$obj['p0'] = ((float)$posn[0] * $attr['scale'] + $max[0]) * 100 / $max2[0];
			$obj['p1'] = -((float)$posn[1] * $attr['scale'] - $max[1]) * 100 / $max2[1];
			if (isset($posn[2]))
				$obj['p2'] = (float)$posn[2] * 100 / 150;
			
	
			//calculate sizen & posn for converting tm -> mp or mp->tm || without scale
			if (OUTPUT != 'web' && INPUT != OUTPUT) {
				$sizen = explode(' ', $obj['sizen']);
				$posn = explode(' ', $obj['posn']);
				$s0 = floatval($sizen[0]) / $max2[0] * $outmax2[0];
				$s1 = 0;
				if (isset($sizen[1]))
					$s1 = floatval($sizen[1]) / $max2[1] * $outmax2[1];
				$p0 = (float)$posn[0] / $max2[0] * $outmax2[0];
				$p1 = (float)$posn[1]  / $max2[1] * $outmax2[1];
				
				$obj['sizen']= round((float)$s0, 2).' '.round((float)$s1, 2);
				$obj['posn']= round((float)$p0, 2).' '.round((float)$p1, 2).' '.round((float)$obj['p2'], 2);		
			}
		
		}
		
		if ($obj['xmlName'] != 'label') {
			if ($obj['halign'] != 'left') {
				if ($obj['halign'] == 'center')
					$obj['p0'] = (float)$obj['p0'] - (float)$obj['s0'] / 2.0;
				elseif ($obj['halign'] == 'right')
					$obj['p0'] = (float)$obj['p0'] - (float)$obj['s0'];
			}
				
			if ($obj['valign'] != 'top') {
				if ($obj['valign'] == 'center')
					$obj['p1'] = (float)$obj['p1'] - (float)$obj['s1'] / 2.0;
				elseif ($obj['valign'] == 'bottom')
					$obj['p1'] = (float)$obj['p1'] - (float)$obj['s1'];
			}
		} else {
			if ($obj['halign'] == 'center')
				$obj['p0'] = (float)$obj['p0'] - (float)$obj['s0'] / 2.0;
			elseif ($obj['halign'] == 'right')
				$obj['p0'] = (float)$obj['p0'] - (float)$obj['s0'];
		}
	}
}

parser::loadSettings();

if (OUTPUT == 'web') {
	parser::web_css();
	add_hook('xml_quad','parser::web_quad', 18);
	add_hook('xml_label','parser::web_label', 18);
	add_hook('xml_frame','parser::web_frame', 18);
	add_hook('xml_music','parser::web_music', 18);
	add_hook('xml_all','parser::web_ml2url', 3);
	add_hook('xml_all','parser::web_unit', 2);
} else {
	add_hook('xml_quad','parser::xml_convert', 18);
	add_hook('xml_label','parser::xml_convert', 18);
	add_hook('xml_audio','parser::xml_convert', 18);
	add_hook('xml_music','parser::xml_convert', 18);
	add_hook('xml_format','parser::xml_convert', 18);
	add_hook('xml_entry','parser::xml_convert', 18);
	add_hook('xml_fileentry','parser::xml_convert', 18);
	add_hook('xml_timeout','parser::xml_timeout', 18);
	add_hook('xml_frame','parser::xml_frame', 18);
}

if (INPUT != OUTPUT) {
	add_hook('xml_quad','parser::quad_style', 10);
	add_hook('xml_quad','parser::bikToWebm', 10);
}

//add_hook('xml_label','parser::label_fun', 0);
add_hook('xml_all','parser::href', 1);
add_hook('xml_all','parser::xml_position', 1);
add_hook('xml_all','parser::urlControl', 10);
add_hook('xml_quad','parser::enableImagefocus', 15);
add_hook('xml_all_end','parser::readability', 20);
