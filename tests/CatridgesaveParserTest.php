<?php 

	$currentWordkedDirectory = getcwd();
	include_once $currentWordkedDirectory . "/../includes/CartridgesaveParser.php";
 
	class CatridgesaveParserTest extends PHPUnit_Framework_TestCase
	{
		private $cartridgesaveParser;

		public function __construct()
		{
			$this->cartridgesaveParser = CartridgesaveParser::getParser();  
		}

		/*public function test_getAllSubcategories()
		{
			$allSubcategories = $this->cartridgesaveParser->getAllSubcategories();
		}*/

		public function test_processSubCategories()
		{
			$allSubcategories = $this->cartridgesaveParser->getAllSubcategories();
			$this->cartridgesaveParser->processSubCategories($allSubcategories);
		}
	}
?>