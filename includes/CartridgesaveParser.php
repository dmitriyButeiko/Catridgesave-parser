<?php 

	// Turn on errors and increase time execution time
	set_time_limit(99999999);
	ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('memory_limit', '999999999');
    error_reporting(E_ALL);

    // require all needed libraries
	require_once "HttpHelper.php";
	require_once "CsvHelper.php";
	require_once "SimpleHtmlDom.php";

	class CartridgesaveParser
	{
		private $siteUrl = "http://www.cartridgesave.co.uk";
		private $httpHelper;
		private $csvHelper;
		private $numberOfThreads = 300;
		private $subcategoriesDivideNumber = 5;

		public function setNumberOfThreads($numberOfThreads)
		{
			$this->numberOfThreads = $numberOfThreads;
		}

		public function loadProductsByCategoryUrl($categoryUrl)
		{
			$categoryHtml = $this->httpHelper->getHtml($categoryUrl);
		}

		public function loadProductsByCategory($categoryInfo)
		{
			$categoryHtml = $this->httpHelper->getHtml($categoryInfo["url"]);
			$productsInfo = array();
			$productsUrls = $this->parseCategoryProductsUrls($categoryHtml);

			$dividedProductsUrls = $this->httpHelper->divideUrls($productsUrls, $this->numberOfThreads);

			foreach($dividedProductsUrls as $singleDividedProductsUrlsSet)
			{
				$dividedProductsHtml = $this->httpHelper->multiRequest($singleDividedProductsUrlsSet);	

				foreach($dividedProductsHtml as $singleDividedProductHtml)
				{
					$productInfo = $this->parseProductInfo($singleDividedProductHtml["html"]);
					$productInfo["productCategory"] = $categoryInfo["name"];
					$productsInfo[] = $productInfo;
				}
			}


			var_dump($productsInfo);

			$this->csvHelper->generateProductsCsvFile($productsInfo);
		}


		public function loadProducts()
		{
			$allSubcategories = $this->getAllSubcategories($mainPageHtml);
            $this->processSubCategories($allSubcategories);
		}

		public static function getParser()
		{
			$instance = null;
			if($instance == null)
			{
				$instance = new CartridgesaveParser();
			}  
			return $instance;
		}

		public function getAllSubcategories()
		{
			$allSubcategories = array();

			$mainPageHtml = $this->httpHelper->getHtml($this->siteUrl);

			/*
			    Get categories from main page and fetch all their urls
			*/

			echo "Subcategories parsing..." . "\n";

			$categoriesFromMainPage = $this->getCategoriesFromMainPage($mainPageHtml);
			$categoriesUrlsFromMainPage = array();

			echo "Categories from main page fetched..." . "\n";

			foreach($categoriesFromMainPage as $singleCategoryFromMainPage)
			{
				$categoriesUrlsFromMainPage[] = $singleCategoryFromMainPage["url"];
			}

			/*
				Get subcategories from subcategories from main Page 
			*/


			$dividedCategoriesUrls = $this->httpHelper->divideUrls($categoriesUrlsFromMainPage, $this->numberOfThreads);


			$allSubcategoriesCounter = 0;


			foreach($dividedCategoriesUrls as $singleDividedSet)
			{
				$dividedCategoriesHtml = $this->httpHelper->multiRequest($singleDividedSet);


				foreach($dividedCategoriesHtml as $singleCategoryHtml)
				{
					$fetchedSubcategories = $this->getSubcategoriesFromSubcategories($singleCategoryHtml["html"]);

					foreach($fetchedSubcategories as $singleFetchedSubcategory)
					{
						$allSubcategories[$allSubcategoriesCounter] = $singleFetchedSubcategory;
						$allSubcategoriesCounter++;
					}
				}
			}

            echo "Subcategories fetched..." . "\n";

            return $allSubcategories;
		}

		public function processSubCategories($allSubcategories)
		{
			echo "Begin processing subcategories..." . "\n";

			$uniqueProductsUrls = array();

			echo "Dividing subcategories urls on several parts..." . "\n";
			// here we divide all subcategories urls on 
			$dividedSubcategoriesUrls = $this->httpHelper->divideUrls($allSubcategories, $this->subcategoriesDivideNumber);

			$subcategoriesSetCounter = 1;

			echo "Foreach through all subcategories..." . "\n";
			// foreach through all divided categories
			foreach($dividedSubcategoriesUrls as $singleDividedSubcategoriesSet)
			{

				echo "Handling " . $subcategoriesSetCounter . " counter" . "\n";
				// get categories urls from multi result
				$subcategoriesUrls = $this->httpHelper->fetchUrlsFromMultiResult($singleDividedSubcategoriesSet);

				// make request to get categories pages html code
				$subcategoriesMultiResult = $this->httpHelper->multiRequest($subcategoriesUrls);


				$subcategoriesList = array();

				$counter = 0;

				// foreach through all returned html code
				foreach($subcategoriesMultiResult as $singleMultiResult)
				{
					$currentCategoryUrl = $singleMultiResult["url"];

					$subcategoriesList[$counter] = array();
					$subcategoriesList[$counter]["url"] = $currentCategoryUrl;

					echo "Current subcategory url: " . $currentCategoryUrl . "\n";


					// get products urls from category page html
					$singleSubcategoryProductsUrls = $this->parseCategoryProductsUrls($singleMultiResult["html"]);
					


					$productsUrlsToAdd = array();

					foreach($singleSubcategoryProductsUrls as $singleSubcategorySingleProductUrl)
					{
						if(in_array($singleSubcategorySingleProductUrl, $uniqueProductsUrls))
						{
							continue;
						}

						$productsUrlsToAdd[] = $singleSubcategorySingleProductUrl;
					}

					$subcategoriesList[$counter]["products"] = $productsUrlsToAdd;

					$counter++;
				}

				$allProductsUrls = array();

				foreach($subcategoriesList as $singleSubcategoryList)
				{
					foreach($singleSubcategoryList["products"] as $singleSubcategoryProductUrl)
					{
						$allProductsUrls[] = $singleSubcategoryProductUrl;
					}
				}

				unset($subcategoriesMultiResult);

				$dividedProductsUrls = $this->httpHelper->divideUrls($allProductsUrls, $this->numberOfThreads);

				$productsInfo = array();

				$productsInfoCounter = 0;

				foreach($dividedProductsUrls as $singleDividedProductsUrlsSet)
				{
					$productsMultiResult = $this->httpHelper->multiRequest($singleDividedProductsUrlsSet);

					foreach($productsMultiResult as $singleMultiResult)
					{
						$parsedProductInfo = $this->parseProductInfo($singleMultiResult["html"]);
						$productsInfo[$productsInfoCounter] = array();
						$productsInfo[$productsInfoCounter]["productUrl"] = $singleMultiResult["url"];
						$productsInfo[$productsInfoCounter]["productInfo"] = $parsedProductInfo;
					}

					$productsInfoCounter++;
				}

				$overallProductsInfoToAdd = array();

				$overallInfoCounter = 0;


				/*foreach($productsInfo as $singleProductInfo)
				{
					$currentInfoUrl = $singleProductInfo["productUrl"];

					foreach()
					{
						
					}

					$overallInfoCounter++;
				}
*/
				$subcategoriesSetCounter++;
			}
		}

		private function parseCategoryProductsUrls($html)
		{
			$html = str_get_html($html);

			$productsUrls = array();

			foreach($html->find("#carts_list .results .result_group") as $singleLink)
			{
				$productsUrls[] = $this->siteUrl . $singleLink->find("a.item_link", 0)->href;
			}

			return $productsUrls;
		}

		private function getSubcategoriesFromSubcategories($html)
		{
			$subcategories = array();
			$html = str_get_html($html);

			$counter = 0;

			foreach($html->find(".family_printer_list li a") as $singleSubcategory)
			{
				$subcategories[$counter] = array();

				$subcategories[$counter]["url"] = $this->siteUrl . $singleSubcategory->href;
				$subcategories[$counter]["name"] = $singleSubcategory->innertext;

				$counter++;
			}

			return $subcategories;
		}

		private function getCategoriesFromMainPage($html)
		{
			$categoriesList = array();
			$html = str_get_html($html);
			$counter = 0;

			foreach($html->find(".menu li a") as $singleMenuItem)
			{
				$categoriesList[$counter] = array();
				$categoriesList[$counter]["url"] = $this->siteUrl . $singleMenuItem->href;
				$categoriesList[$counter]["name"] = $singleMenuItem->find("span", 0)->innertext;

				$counter++;
			}

			return $categoriesList;
		}

		private function __construct()
		{
			$this->httpHelper = HttpHelper::getHelper();
			$this->csvHelper = new CsvHelper();
		}

		private function decodeTextInGoodForm($text)
    	{
        	$text = strip_tags($text);
        	$text = html_entity_decode($text);
        	$text = trim($text);
        	$text = preg_replace( "/\r|\n/", "", $text);
        	$text = str_replace("   ", "", $text);
        	$text = str_replace("\"", "'", $text);
        	$text = preg_replace('/\s\s+/', ' ',  $text);
        	$text  = trim(preg_replace('/\s\s+/', ' ',  $text));
        
        	return $text;
    	}

		private function parseProductInfo($html)
		{
			$productInfo = array();

			$html = str_get_html($html);

			$productInfo["name"] = $this->decodeTextInGoodForm($html->find(".flypage_title h1", 0)->innertext);

			$productInfo["price"] = $this->decodeTextInGoodForm($html->find("div.buy div.pricing .ex_vat_price", 0)->innertext);

			$productInfo["description"] = $this->decodeTextInGoodForm($html->find("#description .article", 0)->innertext);

			$productInfo["photoUrl"] = $this->decodeTextInGoodForm($html->find(".product_image img", 0)->src);	

			return $productInfo;
		}
	}
?>