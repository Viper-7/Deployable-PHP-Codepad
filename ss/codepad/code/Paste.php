<?php
class Paste_Attachment extends DataObject {
	public static $db = array(
		'Code' => 'Text',
		'Filename' => 'Varchar(50)',
	);
	
	public static $has_one = array(
		'Paste' => 'Paste',
	);
	
	public static $defaults = array(
		'Filename' => 'index.php',
	);
}

class Paste extends DataObject {
        public static $db = array(
			'Title' => 'Varchar',
			'Code' => 'Text',
			'Hits' => 'Int',
			'Filename' => 'Varchar(50)',
			
			'SeriesID' => 'Varchar(50)',
			'Version' => 'Int',
			
			'LastRenderTime' => 'Decimal',
        );

        public static $has_one = array(
			'PHPVersion' => 'PHPVersion',
			'Author' => 'Member',
        );
		
		public static $defaults = array(
			'Hits' => 0,
			'LastRenderTime' => 0,
			'Version' => 1,
			'Title' => 'untitled',
		);
		
		public function RecentPastes() {
			return DataObject::get('Paste', NULL, 'Created DESC', '', 10);
		}

		public function History() {
			return DataObject::get('Paste', 'SeriesID = \'' . Convert::raw2sql($this->SeriesID) . '\'', 'Version');
		}
		
		public function UserHistory() {
			return DataObject::get('Paste', 'AuthorID = ' . intval(Member::currentUserId()), 'Created DESC', '', 10);
		}
		
		public function PHPVersions() {
			return DataObject::get('PHPVersion', 'LastCompiled IS NOT NULL', 'Title DESC');
		}
		
		public function Hostname() {
			return $_SERVER['HTTP_HOST'];
		}

		public function RenderTime() {
			return "{$this->LastRenderTime} ms";
		}

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
		
		public function isCurrentPaste() {
			return Controller::curr()->getCurrentPaste()->Filename == $this->Filename;
		}
		
		public function Size() {
			$size = strlen($this->Code);
			
			return $size . ' b';
		}

        public function getFrontendActions() {
                return new FieldSet(new FormAction('submitPaste', 'Paste'));
        }

        public function ExecuteLink() {
			$args = array_diff_key($_GET, array('url' => ''));
            return $this->VerLink() . '?' . http_build_query($args);
        }
		
		public function VerLink() {
			return '/' . $this->Filename . '/' . $this->PHPVersion()->FuncName;
		}
		
		public function CurrentUser() {
			return Member::currentUser();
		}

        public function Link() {
                return '/' . $this->Filename;
        }
		
        public function onBeforeWrite() {
                parent::onBeforeWrite();

				if(!$this->Filename) {
					$filename = tempnam('/opt/codepad/code', '');
					unlink($filename);
					file_put_contents($filename, $this->Code);
					$this->Filename = basename($filename);
				}
				
				if(empty($this->SeriesID))
					$this->SeriesID = uniqid($this->Filename);
				
				if(empty($this->Version)) {
					$latest = DataObject::get_one('Paste', 'SeriesID=\'' . Convert::raw2sql($this->SeriesID) . '\'', 'Version DESC');
					if($latest) {
						$this->Version = $latest->Version + 1;
					} else {
						$this->Version = 1;
					}
				}
				
				$id = Member::currentUserID();
				if($id) {
					$this->AuthorID = $id;
				}
        }
		
		public function onAfterWrite() {
			parent::onAfterWrite();
			
			if(preg_match_all('§##\w+(?:\.\w+)\s*\n§', $this->Code, $matches)) {
				foreach($matches as $match) {
					$match = $match[0];
					
					preg_match("§{$match}(.*?)(?:$|##\w+)§", $this->Code, $content);
					
					$attach = new Paste_Attachment();
					$attach->PasteID = $this->ID;
					$attach->Code = $content[1];
					$attach->Filename = $match[0];
					$attach->write();
				}
			} else {
				$attach = new Paste_Attachment();
				$attach->PasteID = $this->ID;
				$attach->Code = $this->Code;
				$attach->write();
			}
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
		
		public function handleRequest($request) {
			$this->request = $request;
			$this->response = new SS_HTTPResponse();
			$this->pushCurrent();
			
			if($request->getUrl() == 'Paste') {
				$this->submitPaste($request->postVars());
			}
			
			$paste = $this->getCurrentPaste();

			$id = trim($this->request->getVar('url'), '/');
			if(strpos($id, '/') !== FALSE) {
				list($id, $func, $view) = explode('/', $id) + array('', '', '');
				
				$func = DataObject::get_one(
					'PHPVersion',
					'FuncName = \'' . Convert::raw2sql($func) . '\''
				);
			}
			
			if(!empty($func)) {
				$this->response->setBody($func->executePaste($paste->Filename, $view));
			} else {
				$this->response->setBody($paste->renderWith(array('Paste', 'Paste')));
			}
			
			ContentNegotiator::process($this->response);
			$this->popCurrent();
			return $this->response;
		}
		
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
                $paste->PHPVersionID = $data['PHPVersion'];
		if(!isset($data['hon']) || !empty($data['hon'])) {
			trigger_error('Invalid form submission', E_USER_FATAL);
			die();
		}

				$paste->Code = $data['code'];
				$paste->Title = $data['Title'];
				if(!empty($data['SeriesID']))
					$paste->SeriesID = $data['SeriesID'];
				$latest = DataObject::get('Paste', 'SeriesID = \'' . Convert::raw2sql($paste->SeriesID) . '\'', 'Version DESC', '', 1);
                if($latest)
					$paste->Version = $latest->First()->Version + 1;
				
				$paste->write();

                return Director::redirect($paste->Link());
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

						if(!$paste) {
							return FALSE;
						}
					}
				}

				if($id && $id != 'Paste_Controller') {
					$paste = DataObject::get_one(
						'Paste',
						'"Filename" = \'' . Convert::raw2sql($id) . '\''
					);
				}

				if(empty($paste)) {
					$paste = new Paste();
					$paste->PHPVersionID = DataObject::get_one('PHPVersion', 'IsDefault=1')->ID;
				} else {
					$paste->Hits++;
					$paste->write();
				}

				self::$current_paste = $paste;
			}

			return self::$current_paste;
        }
}
?>
