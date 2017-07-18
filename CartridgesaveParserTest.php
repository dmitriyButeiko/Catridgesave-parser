<?php 

	require_once "includes/CartridgesaveParser.php";

	$cartridgesaveParser = CartridgesaveParser::getParser();
	$allSubcategories = $cartridgesaveParser->getAllSubcategories();

	foreach($allSubcategories as $singleSubcategory)
	{
		$cartridgesaveParser->loadProductsByCategory($singleSubcategory);
	}
?>
