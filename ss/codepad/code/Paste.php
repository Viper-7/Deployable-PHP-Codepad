<?php class Paste extends DataObject {
	public static $db = array(
		'Code' => 'Text',
		'Filename' => 'Varchar(50)',
		'Hits' => 'Int',
	);

	public static $has_one = array(
		'PHPVersion' => 'PHPVersion'
	);
	
	public function getFrontendFields() {
		if(empty($this->Code))
			$this->Code = "<?php\n\techo \"Hello, World!\";\n?>";
		
		if(empty($this->PHPVersionID))
			$this->PHPVersionID = DataObject::get_one('PHPVersion', 'IsDefault = 1')->ID;
		
		$fields = new FieldSet();
		
		// Code submission field
		$field = new EditAreaField(
			'EditArea',
			'',
			25,
			80,
			$this->Code
		);
		$fields->push($field);

		$versions = DataObject::get('PHPVersion', 'LastCompiled IS NOT NULL')->toDropdownMap('ID', 'Title');
		
		// PHP Version field
		$version = new DropdownField(
			'PHPVersion',
			'PHP Version',
			$versions,
			$this->PHPVersionID
		);
		$fields->push($version);
		
		return $fields;
	}
	
	public function getFrontendActions() {
		return new FieldSet(new FormAction('submitPaste', 'Paste'));
	}
	
	public function ExecuteLink() {
		return '/' . $this->Filename . '/' . $this->PHPVersion()->FuncName;
	}
	
	public function Link() {
		return '/' . $this->Filename;
	}

	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		$filename = tempnam('/opt/codepad/code', '');
		unlink($filename);
		file_put_contents($filename, $this->Code);
		$this->Filename = basename($filename);
	}
	
	public function PasteOutput() {
		Requirements::css('codepad/css/codepad.css');

		if($this->ID) {
			return '<iframe src="' . $this->ExecuteLink() . '" width="960" height="380"></iframe>';
		}
	}
}

class Paste_Controller extends Page_Controller {
	protected static $current_paste;
	
	public function PasteForm() {
		$paste = $this->getCurrentPaste();
		
		$fields = $paste->getFrontendFields();
		$actions = $paste->getFrontendActions();
		$validator = new RequiredFields();
		$form = new Form(
			$this,
			'PasteForm',
			$fields,
			$actions,
			$validator
		);
		return $form;
	}
	public function PasteOutput() {
		return $this->getCurrentPaste()->PasteOutput();
	}
	public function submitPaste($data) {
		$paste = new Paste();
		$paste->Code = $data['EditArea'];
		$paste->PHPVersionID = $data['PHPVersion'];
		$paste->write();
		Director::redirect($paste->Link());
	}
	public function getCurrentPaste() {
		if(empty(self::$current_paste)) {
			$id = trim($this->request->getVar('url'), '/');

			if(strpos($id, '/') !== FALSE) {
				list($id, $func) = explode('/', $id);
				
				if($id != 'Paste_Controller') {
				
					$paste = DataObject::get_one(
        	                                'Paste',
                	                        '"Filename" = \'' . Convert::raw2sql($id) . '\''
                        	        );

					$func = DataObject::get_one(
						'PHPVersion',
						'FuncName = \'' . Convert::raw2sql($func) . '\''
					);

					if(!$paste) { 
						return FALSE;
					}

					if(!$func) {
						$func = $paste->PHPVersion();
					}

					return $func->executePaste($paste->Filename);
				}
			}
			
			if($id && $id != 'Paste_Controller') {
				$paste = DataObject::get_one(
					'Paste',
					'"Filename" = \'' . Convert::raw2sql($id) . '\''
				);
			}
			
			if(empty($paste)) {
				$paste = singleton('Paste');
				$paste->PHPVersionID = DataObject::get_one('PHPVersion', 'IsDefault=1')->ID;
			}

			self::$current_paste = $paste;
		}
		
		return self::$current_paste;
	}
}
?>
