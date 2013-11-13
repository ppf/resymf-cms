<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 11/12/13
 * Time: 10:14 PM
 */

namespace ReSymf\Bundle\CmsBundle\Services;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

class ObjectMapper {

	private $mapping_table;

	public function __construct()
	{
		$yaml = new Parser();
		try {
			$value = $yaml->parse(file_get_contents(__DIR__.'/../Resources/config/admin/object-mapping.yml'));
		} catch (ParseException $e) {
			printf("Unable to parse the YAML string: %s", $e->getMessage());
		}

		$this->mapping_table = $value['mapping'];
		// TODO: Implement __construct() method. Load menu to array
	}

	public function getMappedObject($objectName)
	{
		foreach($this->mapping_table as $name => $nameWithNamespace) {
			if($name == $objectName) {
				return $nameWithNamespace;
			}
		}
		return false;
	}
} 