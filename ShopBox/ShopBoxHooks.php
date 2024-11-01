<?php
namespace ShopBox;

use ShopBox\Integrators\InventoryImporter;

class ShopBoxHooks
{
    protected $api;
    protected $settings;

    public function __construct($api, $settings)
    {
        $this->api = $api;
        $this->settings = $settings;
    }

    public function getHookId()
    {
        if (!$this->settings->hasToken()) {
            return false;
        }

        return md5($this->settings->getToken());
    }

    protected function isValidHookId($hookId)
    {
        $rightHookId = $this->getHookId();
        if ($rightHookId && $rightHookId == $hookId) {
            return true;
        }

        return false;
    }

    public function execute($shopboxHookId, $action)
    {
        if (!$this->isValidHookId($shopboxHookId)) {
            throw new \Exception('invalid shopbox hook id');
        }
        
        if (!method_exists($this, $action)) {
            throw new \Exception('action not found');
        }

        $this->{$action}();
    }

    public function updateInventory()
    {
        $inventoryArray = file_get_contents('php://input');
        $inventoryArray = json_decode($inventoryArray, true);

        $inventoryImporter = new InventoryImporter($this->api, $this->settings);
        $inventoryImporter->updateInventory($inventoryArray);
    }
}
