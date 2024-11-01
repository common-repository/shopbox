<?php
namespace ShopBox\Jobs;

use ShopBox\Integrators\CategoriesExporter;
use ShopBox\Integrators\ProductsExporter;
use WP_Background_Process;

class ProductsExporterJob extends WP_Background_Process {

    /**
     * @var string
     */
    protected $action = 'products_exporter_2';

    protected $settings;
    
    protected $api;

    /**
     * Task
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $item Queue item to iterate over
     *
     * @return mixed
     */
    protected function task( $item ) {
        // Actions to perform
        $productsExporter = new ProductsExporter($this->api, $this->settings);

        if (!empty($item['export_all'])) {
            $productsExporter->exportAllProducts($item['page']);
        } else {
            $productsExporter->exportUnsynchedProducts($item['page']);
        }

        return false;
    }

    /**
     * Complete
     *
     * Override if applicable, but ensure that the below actions are
     * performed, or, call parent::complete().
     */
    protected function complete() {
        parent::complete();

        // Show notice to user or perform some other arbitrary task...
    }

    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    public function setAPI($api)
    {
        $this->api = $api;
    }

    public function remainingJobsCount()
    {
        if ($this->is_queue_empty()) {
            return 0;
        }

        $batch = $this->get_batch();
        return count($batch->data);
    }
    
    /**
	 * Update queue
	 *
	 * @param string $key  Key.
	 * @param array  $data Data.
	 *
	 * @return $this
	 */
    public function update( $key, $data ) {
        if ($this->is_queue_empty()) {
            return $this;
        }

        parent::update($key, $data);

        return $this;
    }
}