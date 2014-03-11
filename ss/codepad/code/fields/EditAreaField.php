<?php
class EditAreaField extends TextareaField {
	public function Field() {
		Requirements::javascript('http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js');
//		Requirements::javascript('http://c0377438.cdn2.cloudfiles.rackspacecloud.com/edit_area/edit_area_full.js');
		Requirements::javascript('codepad/thirdparty/edit_area/edit_area_full.js');
		
		$attributes = array(
			'id' => $this->id(),
			'class' => ($this->extraClass() ? $this->extraClass() : ''),
			'name' => $this->name,
			'rows' => $this->rows,
			'cols' => $this->cols
		);
		
		if($this->disabled) $attributes['disabled'] = 'disabled';
		
		return $this->createTag('textarea', $attributes, htmlentities($this->value, ENT_COMPAT, 'UTF-8')) . 
			   $this->renderWith('Includes/EditAreaField');
	}
}
