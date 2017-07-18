<?php 
	ini_set('auto_detect_line_endings', true);

    class CsvHelper
    {
        private $categoriesFileName = "categories.csv";
        private $productsFileName = "products.csv";

        public function getCategoriesFileName()
        {
            return $this->categoriesFileName;
        }

        public function getProductsFileName()
        {
            return $this->productsFileName;
        }

    	public function generateCategoriesCsvFile($categoriesList)
    	{
    		$list = $this->generateListArray($categoriesList);

            $fp = fopen($this->categoriesFileName, 'a+');
            $this->addData($fp, $list);

            return $list;
    	}

        public function generateProductsCsvFile($productsList)
        {
            $list = $this->generateListArray($productsList);
            $fp = fopen($this->productsFileName, 'a+');
            $this->addData($fp, $list);

            return $list;
        }

        public function getParsedCategories()
        {
            $parsedCategories = array();

            $fp = fopen($this->categoriesFileName, 'a+');

            while ( ($data = fgetcsv($fp) ) !== FALSE ) {
                $parsedCategories[] = $data;
            }
            
            return $parsedCategories;
        }

        private function addData($fileDescr, $list)
        {
            foreach ($list as $line) {
                fputcsv($fileDescr, $line, ";");
            }

            fclose($fileDescr);
        }

        public function addProductsToCsvFile($productsList)
        {
            $list = $this->generateListArrayFromProductsList($categoriesList);
            $fp = fopen($this->productsFileName, 'a+');
            $this->addData($fp, $list);
        }

    	private function generateListArray($categoriesList)
    	{
    		$list = array();
    		$categoryIdCounter = 100;
    		$arrayCounter = 0;

    		foreach($categoriesList as $singleCategory)
    		{
                $list[$arrayCounter] = array();

                foreach($singleCategory as $key => $value)
                {
                    $list[$arrayCounter][] = $value;
                }

                if(!isset($singleCategory["id"]))
                {
                    $list[$arrayCounter][] = $categoryIdCounter;
                }

    			$categoryIdCounter++;
    			$arrayCounter++;
    		}

    		return $list;
    	}
    }

?>