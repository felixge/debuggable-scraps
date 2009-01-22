<?php
ini_set('memory_limit', '512M');
require_once CAKE_TESTS_LIB.'cake_test_model.php';
require_once CAKE_TESTS_LIB.'cake_test_fixture.php';
class FixturesShell extends Shell {
/**
 * Truncates all tables and loads fixtures into db
 *
 * @return void
 * @access public
 */
	function main() {
		
		if (!empty($this->args)) {
			
			$fixtures = $this->args;
			foreach ($fixtures as $i => $fixture) {
				$fixtures[$i] = APP . 'tests/fixtures/' . $fixture . '_fixture.php';
			}
		} else {
			App::import('Folder');
			$Folder = new Folder(APP.'tests/fixtures');
			$fixtures = $Folder->findRecursive('.+_fixture\.php');
		}
		$datasource = 'default';
		if (isset($this->params['datasource'])){
			$datasource = $this->params['datasource'];
		}
		
		$list = ConnectionManager::enumConnectionObjects();
		if (!isset($list[$datasource])){
			$this->error("Data Source","Non-existent data source '{$datasource}'");
		}
		$db = ConnectionManager::getDataSource($datasource);
		
		$records = 0;
		foreach ($fixtures as $path) {
			require_once($path);
			$name = str_replace('_fixture.php', '', basename($path));
			$class = Inflector::camelize($name).'Fixture';
			$Fixture =& new $class($db);

			$this->out('-> Truncating table "'.$Fixture->table.'"');
			$db->truncate($Fixture->table);

			$Fixture->insert($db);
			$fixtureRecords = count($Fixture->records);
			$records += $fixtureRecords;
			$this->out('-> Inserting '.$fixtureRecords.' records for "'.$Fixture->table.'"');
		}
		$this->out(sprintf('-> Done inserting %d records for %d tables', $records, count($fixtures)));
	}
/**
 * Generates and outputs a list of $n (=10) UUIDs, useful for creating fixture records.
 *
 * @return void
 * @access public
 */
	function uuid() {
		$n = isset($this->args[0]) ? (int)$this->args[0] : 10;
		for ($i = 0; $i < $n; $i++) {
			$this->out(String::uuid());
		}
	}
}

?>