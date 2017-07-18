<?php 

	$currentWordkedDirectory = getcwd();
	include_once $currentWordkedDirectory . "/../includes/HttpHelper.php";
 
	class HttpHelperTest extends PHPUnit_Framework_TestCase
	{
		private $httpHelper;

		public function __construct()
		{
			$this->httpHelper = HttpHelper::getHelper();  
		}

		public function test_divideUrls()
		{
			$testUrlsToDivide = array(
				"url1"
			);

			$dividedUrls = $this->httpHelper->divideUrls($testUrlsToDivide, 3);

			var_dump($dividedUrls);
		}
	}
?>