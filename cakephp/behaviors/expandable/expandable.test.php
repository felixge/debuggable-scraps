<?php

class FileTestModel extends CakeTestModel{
	var $name = 'FileTestModel';
	var $useTable = 'files';
	var $actsAs = array('Expandable');
	var $hasMany = array('FileFieldTestModel');
	var $cacheQueries = false;
}

class FileFieldTestModel extends CakeTestModel{
	var $name = 'FileFieldTestModel';
	var $useTable = 'file_fields';
	var $hasMany = array('FileTestModel' => array(
		'foreignKey' => 'file_id'
	));
	var $cacheQueries = false;
}

class ContainableBehaviorTest extends CakeTestCase {
	var $fixtures = array('file', 'file_field');
	function startCase() {
		$this->File =& ClassRegistry::init('FileTestModel');
	}

	function testAfterFind() {
		$r = $this->File->find('all');
		var_export($r);
		$this->assertTrue(Set::matches('/FileTestModel[1]/.[colors=255]', $r));
		$this->assertTrue(Set::matches('/FileTestModel[1]/.[page_id=1]', $r));
		$this->assertTrue(Set::matches('/FileTestModel[2]/.[comment=This is the Thumbnail version]', $r));
		
		$r = $this->File->find('first');
		$this->assertTrue(Set::matches('/FileTestModel[1]/.[colors=255]', $r));
		$this->assertTrue(Set::matches('/FileTestModel[1]/.[page_id=1]', $r));
	}

	function testSave() {
		$this->File->id = 1;
		$this->File->set('colors', 16);
		$this->File->save();

		$r = $this->File->find('first');
		$this->assertTrue(Set::matches('/FileTestModel[1]/.[colors=16]', $r));
		$this->assertTrue(Set::matches('/FileTestModel[1]/.[page_id=1]', $r));

		$this->File->id = 2;
		$this->File->set('width', 640);
		$this->File->set('height', 480);
		$this->File->save();
		$r = $this->File->find('first');
		$this->assertTrue(Set::matches('/FileTestModel[1]/.[width=640]', $r));
		$this->assertTrue(Set::matches('/FileTestModel[1]/.[height=480]', $r));
	}
}

?>