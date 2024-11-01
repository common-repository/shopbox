<?php
namespace ShopBox\Integrators;

use ShopBox\Repositories\CategoriesRepository;

class CategoriesExporter
{
    public function __construct($api, $settings)
    {
        $this->api = $api;
        $this->settings = $settings;
        $this->categoriesRepository = new CategoriesRepository();
    }

    public function exportUnsynchedCategories()
    {
        $categories = $this->categoriesRepository->getUnsynchedCategories();
        $this->exportCategories($categories);
    }


    public function exportAllCategories()
    {
        $categories = $this->categoriesRepository->getAllCategories();
        $this->exportCategories($categories);
    }

    public function exportCategories($categories)
    {
        if (!$categories) {
            return ;
        }

        $cashRegister = $this->api->getCashRegisterDetails($this->settings->getCashRegisterId());
        
        $names = array_map(function ($item) {
            return htmlspecialchars_decode($item->name);
        }, $categories);
        
        $tempCategories = [];
        $duplicatedCategories = [];
        foreach ($categories as $category) {
          $name = strtolower(htmlspecialchars_decode($category->name));
          if (!isset($duplicatedCategories[$name]) ) {
          	$duplicatedCategories[$name] = [];	
          }
          $duplicatedCategories[$name][] = $category;
          
          $tempCategories[$name] = $category;
          
          
        }

        $categories = $tempCategories;
        
        $tags = [];
        $nameChunks = array_chunk($names, 50);
        foreach ($nameChunks as $nameChunk) {
            $tempTags = $this->api->getTags(['names' => $nameChunk, 'per_page' => -1])->data;
            $tags = array_merge($tags, $tempTags);
        }

        $tagsMap = [];
        foreach ($tags as $tag) {
                $tagsMap[strtolower($tag->name)] = $tag;
        }

        foreach ($categories as $category) {
            $categoryName = htmlspecialchars_decode($category->name);

            if (isset($tagsMap[strtolower($categoryName)])) {
                $sbTag = $tagsMap[strtolower($categoryName)];
            } else {
                try {

                    $sbTag = $this->api->createTag([
                        'name' => $categoryName
                        ]);
                } catch (\Exception $e) {
                    try {
                        $logsPath = __DIR__.'/logs.php';
                        
                        $content = '';
                        if (file_exists($logsPath)) {
                            $content = file_get_contents($logsPath);
                        }
                        
                        $content .= "\n".$e->getMessage()."\n";
                        
                                        file_put_contents($logsPath, $content);
                      } catch (\Exception $e) {
                    }
                }
            }
                      
          foreach ($duplicatedCategories[strtolower($categoryName)] as $c) {
            update_term_meta($c->term_id, '_shopbox_id', $sbTag->uid);
          }
        }
    }
}
