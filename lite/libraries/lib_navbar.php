<?php
/**
 * This is a navbar generator given a XML file.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @package stdlib
 */

/**
 * Generates a navbar with very customizable setting given a XML file. 
 * Currently only supports flat navbars
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @package stdlib
 */
class LiteSTDLib_Navbar{
	protected $xml;
	protected $active;
	protected $textwrap;
	protected $linkwrap;
	protected $currentPage;
	protected $type;
	protected $linkstyle;
	/**
	 * Creates an new instance of the navbar.
	 * @param string $navbarfile The location to the navbar xml file.
	 * @param string $currentPage The current page name, so the navbar generator can figure out what the current page is and add the active.
	 */
	public function __construct($navbarfile, $currentPage){
		$this->currentPage = $currentPage;
		$this->xml = file_get_contents($navbarfile);
		$this->xml = new SimpleXMLElement($this->xml);
		$this->active = $this->decodeAttr($this->xml['active']);
		$this->textwrap = $this->decodeAttr($this->xml['textwrap']);
		$this->linkwrap = $this->decodeAttr($this->xml['linkwrap']);
		$this->type = $this->decodeAttr($this->xml['type']);
		$this->linkstyle = $this->decodeAttr($this->xml['linkstyle']);
	}
	
	/**
	 * Generates the HTML accordings to the xml file specifications.
	 * @return string The HTML of the navbar. 
	 */
	public function html(){
		$type = $this->type['tag'];
		if (!$type) $type = 'root';
		$html = new SimpleXMLElement("<$type></$type>");
		$this->addAttr($this->type['attr'], $html);
		
		foreach ($this->xml->link as $link){
			// Get the attributes
			$name = (string) $link->name;
			$text = (string) $link->text;
			$href = (string) $link->href;
			// Check if there's a link wrap.
			if ($this->linkwrap['tag']){
				$linkwrap = $html->addChild($this->linkwrap['tag']);
				$this->addAttr($this->linkwrap['attr'], $linkwrap);
			} else {
				// Get a reference of the $html as the linkwrap to avoid code duplication
				$linkwrap = &$html;
			}
			
			// $linkwrap could either be $html or the link wrap.
			if ($this->textwrap['tag']){
				$a = $linkwrap->addChild('a');
				$textwrap = $a->addChild($this->textwrap['tag'], $text);
				$this->addAttr($this->textwrap['attr'], $textwrap);
			} else {
				$a = $linkwrap->addChild('a', $text);
			}
			
			
			$this->addAttr($this->linkstyle['attr'], $a);
			$a['href'] = $href;
			
			if (isset($link->attributes)){
				$children = $link->attributes->children();
				foreach ($children as $attr){
					$a[$attr->getName()] = (string) $attr; 
				}
			}
			
			if ($this->currentPage == $name){
				// $var could, and should, either be 'a' or 'textwrap'
				$var = $this->active['tag'];
				$this->addAttr($this->active['attr'], $$var);
			}
			
		}
		
		$html = $html->asXML();
		if ($type == 'root'){
			// Kill the <root> and </root>
			$html = substr($html, 28, strlen($html) - 36);
		} else {
			$html = substr($html, 22);
		}
		return $html;
	}
	
	protected function addAttr($attr, &$node){
		foreach ($attr as $attr=>$value) $node[$attr] = $value;
	}
	
	protected function decodeAttr($value){
		$decoded = array();
		$value = explode(';', $value);
		if (count($value) > 0){
			$decoded['tag'] = array_shift($value);
			$decoded['attr'] = array();
			foreach ($value as $attr){
				$attrArray = explode(':', $attr);
				$decoded['attr'][$attrArray[0]] = $attrArray[1];
			}
		} else {
			$decoded['tag'] = '';
			$decoded['attr'] = array();
		}
		return $decoded;
	}
}

?>
